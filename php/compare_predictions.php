<?php
session_start();
require_once 'dbconnect.php';

// Function to make prediction for a specific date
function makePrediction($conn, $area_name, $waste_type, $date) {
    // Prepare data for prediction
    $data = array(
        'area_id' => $area_name,  // Using area_name as area_id since that's what the API expects
        'waste_type' => $waste_type,
        'date' => $date
    );

    // Call the prediction API
    $api_url = 'http://localhost:5000/predict';
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $result = json_decode($response, true);
        // Check if the response has the expected structure
        if (isset($result['predicted_weight'])) {
            return $result['predicted_weight'];
        } elseif (isset($result['prediction'])) {
            return $result['prediction'];
        } else {
            error_log("Unexpected API response format: " . $response);
            return null;
        }
    } else {
        error_log("API request failed with status code: " . $http_code);
        error_log("Response: " . $response);
        return null;
    }
}

// Function to make prediction from image
function makePredictionFromImage($image_path) {
    // Prepare data for prediction
    $data = array(
        'image_path' => $image_path
    );

    // Call the prediction API
    $api_url = 'http://localhost:5000/predict_image';
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $result = json_decode($response, true);
        if (isset($result['prediction'])) {
            return $result['prediction'];
        }
    }
    return null;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_date = $_POST['selected_date'];
    
    // Get all areas and waste types
    $areas = array();
    $waste_types = array('organic', 'recyclable', 'hazardous');
    
    $area_query = "SELECT area_name FROM areas";
    $area_result = $conn->query($area_query);
    if ($area_result) {
        while ($row = $area_result->fetch_assoc()) {
            $areas[] = $row['area_name'];
        }
    }

    // Make predictions for each area and waste type
    $predictions_made = 0;
    foreach ($areas as $area) {
        foreach ($waste_types as $waste_type) {
            $prediction = makePrediction($conn, $area, $waste_type, $selected_date);
            if ($prediction !== null) {
                // Save prediction to database
                $stmt = $conn->prepare("INSERT INTO waste_predictions (area_name, waste_type, predicted_weight, prediction_date) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $area, $waste_type, $prediction, $selected_date);
                if ($stmt->execute()) {
                    $predictions_made++;
                }
            }
        }
    }

    // Add success message if predictions were made
    if ($predictions_made > 0) {
        $_SESSION['success_message'] = "Successfully made $predictions_made predictions for " . date('F j, Y', strtotime($selected_date));
    } else {
        $_SESSION['error_message'] = "Failed to make predictions. Please check if the prediction API is running.";
    }
}

// Handle image upload and prediction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['waste_image'])) {
    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = $_FILES['waste_image']['name'];
    $file_tmp = $_FILES['waste_image']['tmp_name'];
    $file_path = $upload_dir . basename($file_name);

    // Move uploaded file
    if (move_uploaded_file($file_tmp, $file_path)) {
        // Make prediction
        $prediction = makePredictionFromImage($file_path);
        
        if ($prediction !== null) {
            // Save prediction to database
            $stmt = $conn->prepare("INSERT INTO waste_predictions (area_name, waste_type, predicted_weight, prediction_date, image_path) VALUES (?, ?, ?, ?, ?)");
            $area_name = "Image Upload"; // Default area for image predictions
            $waste_type = $prediction['waste_type'];
            $predicted_weight = $prediction['weight'];
            $current_date = date('Y-m-d');
            $stmt->bind_param("ssdss", $area_name, $waste_type, $predicted_weight, $current_date, $file_path);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Successfully predicted waste type: " . $waste_type . " with weight: " . $predicted_weight . " kg";
            } else {
                $_SESSION['error_message'] = "Failed to save prediction to database.";
            }
        } else {
            $_SESSION['error_message'] = "Failed to make prediction from image.";
        }
    } else {
        $_SESSION['error_message'] = "Failed to upload image.";
    }
}

// Fetch predictions and actual data
$comparison_data = array();
$sql = "SELECT 
            p.area_name,
            p.waste_type,
            SUM(p.predicted_weight) as total_predicted_weight,
            COALESCE(SUM(pr.weight), 0) as total_actual_weight,
            DATE(p.prediction_date) as collection_date
        FROM waste_predictions p
        LEFT JOIN pickup_requests pr ON 
            p.area_name = (SELECT area_name FROM areas WHERE area_id = pr.area_id) AND 
            p.waste_type = pr.pickup_type AND
            DATE(p.prediction_date) = DATE(pr.created_at) AND
            pr.status = 'completed'
        WHERE DATE(p.prediction_date) = DATE(?)
        GROUP BY p.area_name, p.waste_type, DATE(p.prediction_date)
        ORDER BY p.area_name, p.waste_type";

// Add date parameter from form
$selected_date = isset($_POST['selected_date']) ? $_POST['selected_date'] : date('Y-m-d');
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selected_date);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $comparison_data[] = $row;
    }
}

// Fetch recent predictions
$sql = "SELECT * FROM waste_predictions WHERE image_path IS NOT NULL ORDER BY prediction_date DESC LIMIT 10";
$result = $conn->query($sql);
$recent_predictions = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $recent_predictions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prediction Comparison</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .comparison-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .back-button {
            margin-bottom: 1rem;
        }
        .accuracy {
            font-weight: bold;
        }
        .good-accuracy {
            color: #28a745;
        }
        .medium-accuracy {
            color: #ffc107;
        }
        .low-accuracy {
            color: #dc3545;
        }
        .summary-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .prediction-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .image-preview {
            max-width: 300px;
            margin: 1rem 0;
        }
        .prediction-result {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="comparison-container">
            <a href="predict_request.php" class="btn btn-secondary back-button">← Back to Prediction Form</a>
            <h2 class="text-center mb-4">Daily Collection Comparison</h2>
            
            <!-- Prediction Form -->
            <div class="prediction-form">
                <h4>Make New Prediction</h4>
                <form method="POST" class="mb-4">
                    <div class="row justify-content-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="date" class="form-control" name="selected_date" value="<?php echo $selected_date; ?>" required>
                                <button type="submit" class="btn btn-primary">Make Prediction</button>
                            </div>
                            <small class="text-muted">Select any date (past or future) to make predictions and compare with actual data.</small>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Add messages display after the form -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($comparison_data)): ?>
                <!-- Summary Statistics -->
                <div class="summary-box">
                    <h4>Summary for <?php echo date('F j, Y', strtotime($selected_date)); ?></h4>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Total Predicted Weight:</strong> <?php echo round(array_sum(array_column($comparison_data, 'total_predicted_weight')), 2); ?> kg</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Actual Weight:</strong> <?php echo round(array_sum(array_column($comparison_data, 'total_actual_weight')), 2); ?> kg</p>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Area</th>
                                <th>Waste Type</th>
                                <th>Predicted Weight (kg)</th>
                                <th>Actual Weight (kg)</th>
                                <th>Accuracy</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comparison_data as $data): ?>
                                <?php
                                $accuracy = '';
                                $accuracy_class = '';
                                if ($data['total_actual_weight'] > 0) {
                                    $difference = abs($data['total_predicted_weight'] - $data['total_actual_weight']);
                                    $accuracy_percentage = (1 - ($difference / $data['total_actual_weight'])) * 100;
                                    $accuracy = round($accuracy_percentage, 1) . '%';
                                    
                                    if ($accuracy_percentage >= 80) {
                                        $accuracy_class = 'good-accuracy';
                                    } elseif ($accuracy_percentage >= 60) {
                                        $accuracy_class = 'medium-accuracy';
                                    } else {
                                        $accuracy_class = 'low-accuracy';
                                    }
                                } else {
                                    $accuracy = 'N/A';
                                    $accuracy_class = 'text-muted';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($data['area_name']); ?></td>
                                    <td><?php echo htmlspecialchars($data['waste_type']); ?></td>
                                    <td><?php echo round($data['total_predicted_weight'], 2); ?></td>
                                    <td><?php echo round($data['total_actual_weight'], 2); ?></td>
                                    <td class="accuracy <?php echo $accuracy_class; ?>">
                                        <?php echo $accuracy; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">No comparison data found for <?php echo date('F j, Y', strtotime($selected_date)); ?>.</p>
            <?php endif; ?>

            <!-- Image Upload Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <h4>Upload Waste Image</h4>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="waste_image" class="form-label">Select waste image</label>
                            <input type="file" class="form-control" id="waste_image" name="waste_image" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Predict Waste Type</button>
                    </form>
                </div>
            </div>

            <!-- Recent Predictions -->
            <?php if (!empty($recent_predictions)): ?>
                <div class="card">
                    <div class="card-body">
                        <h4>Recent Image Predictions</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Waste Type</th>
                                        <th>Predicted Weight (kg)</th>
                                        <th>Image</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_predictions as $prediction): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d', strtotime($prediction['prediction_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($prediction['waste_type']); ?></td>
                                            <td><?php echo round($prediction['predicted_weight'], 2); ?></td>
                                            <td>
                                                <?php if (file_exists($prediction['image_path'])): ?>
                                                    <img src="<?php echo htmlspecialchars($prediction['image_path']); ?>" 
                                                         alt="Waste image" 
                                                         style="max-width: 100px; max-height: 100px;">
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 