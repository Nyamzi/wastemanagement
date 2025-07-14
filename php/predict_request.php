<?php
session_start();
require_once 'dbconnect.php';

// URL of the Flask API endpoint
$api_url = "http://localhost:5000/predict";

// Fetch areas for the dropdown
$areas = array();
$sql = "SELECT area_name FROM areas ORDER BY area_name";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $areas[] = $row['area_name'];
    }
}

// Waste types
$waste_types = [
    "Plastics", "Organic", "Glass", "Metals", "Paper", "Textiles",
    "Electronics", "Batteries", "Chemicals", "Rubber", "Wood",
    "Food Waste", "Garden Waste", "Medical Waste", "Construction Debris",
    "Oil and Grease", "Expired Goods", "Others"
];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $prediction_date = $_POST['prediction_date'];
    $day_of_week = $_POST['day_of_week'];
    $week_of_year = $_POST['week_of_year'];
    $month = $_POST['month'];
    $area_name = $_POST['area_name'];
    $waste_type = $_POST['waste_type'];

    // Get area_id from areas table
    $stmt = $conn->prepare("SELECT area_id FROM areas WHERE area_name = ?");
    $stmt->bind_param("s", $area_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $area_data = $result->fetch_assoc();
    $stmt->close();

    if (!$area_data) {
        $error = "Area not found in database";
    } else {
        $area_id = $area_data['area_id'];

        // Prepare data to send to the API
        $data = array(
            "day_of_week" => intval($day_of_week),
            "week_of_year" => intval($week_of_year),
            "month" => intval($month),
            "area_id" => $area_id,
            "waste_type" => $waste_type,
            "pickup_type_encoded" => 1
        );

        // Initialize cURL session
        $ch = curl_init("http://localhost:5000/predict");

        // Set the cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        // Execute the cURL request
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL session
        curl_close($ch);

        // Decode the response
        $response_data = json_decode($response, true);

        if ($http_code == 200 && isset($response_data['predicted_weight'])) {
            $predicted_weight = $response_data['predicted_weight'];
            $prediction_result = "Predicted Waste Weight: " . round($predicted_weight, 2) . " kg";
            $success = true;

            // Store prediction in database
            $stmt = $conn->prepare("INSERT INTO waste_predictions (day_of_week, week_of_year, month, area_id, waste_type, predicted_weight) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiisd", $day_of_week, $week_of_year, $month, $area_id, $waste_type, $predicted_weight);
            
            if ($stmt->execute()) {
                $db_success = true;
            } else {
                $db_success = false;
                $db_error = $stmt->error;
            }
            $stmt->close();
        } else {
            $prediction_result = "Error: " . ($response_data['error'] ?? "Unable to get prediction");
            $success = false;
        }
    }
}

// Fetch recent predictions
$recent_predictions = array();
$sql = "SELECT id, day_of_week, week_of_year, month, area_name, waste_type, predicted_weight, prediction_date 
        FROM waste_predictions 
        ORDER BY prediction_date DESC LIMIT 5";
$result = $conn->query($sql);
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
    <title>Waste Prediction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .prediction-form {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .result-box {
            margin-top: 2rem;
            padding: 1rem;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .recent-predictions {
            margin-top: 2rem;
        }
        .prediction-table {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="prediction-form">
            <h2 class="text-center mb-4">Waste Collection Prediction</h2>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="prediction_date" class="form-label">Select Date</label>
                    <input type="date" class="form-control" id="prediction_date" name="prediction_date" required>
                    <input type="hidden" id="day_of_week" name="day_of_week">
                    <input type="hidden" id="week_of_year" name="week_of_year">
                    <input type="hidden" id="month" name="month">
                </div>

                <div class="mb-3">
                    <label for="area_name" class="form-label">Area</label>
                    <select class="form-select" id="area_name" name="area_name" required>
                        <option value="">Select Area</option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?php echo htmlspecialchars($area); ?>">
                                <?php echo htmlspecialchars($area); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="waste_type" class="form-label">Waste Type</label>
                    <select class="form-select" id="waste_type" name="waste_type" required>
                        <option value="">Select Waste Type</option>
                        <?php foreach ($waste_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>">
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100">Get Prediction</button>
            </form>

            <?php if (isset($prediction_result)): ?>
                <div class="result-box <?php echo $success ? 'success' : 'error'; ?>">
                    <h3 class="text-center"><?php echo $prediction_result; ?></h3>
                    <?php if (isset($db_success) && !$db_success): ?>
                        <p class="text-center">Error saving prediction: <?php echo $db_error; ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="recent_predictions.php" class="btn btn-info">View Recent Predictions</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('prediction_date').addEventListener('change', function() {
            const date = new Date(this.value);
            
            // Get day of week (1-7)
            const dayOfWeek = date.getDay() || 7; // Convert 0 (Sunday) to 7
            
            // Get week of year
            const start = new Date(date.getFullYear(), 0, 1);
            const diff = date - start;
            const oneDay = 1000 * 60 * 60 * 24;
            const weekOfYear = Math.ceil((diff / oneDay + start.getDay() + 1) / 7);
            
            // Get month (1-12)
            const month = date.getMonth() + 1;
            
            // Set hidden input values
            document.getElementById('day_of_week').value = dayOfWeek;
            document.getElementById('week_of_year').value = weekOfYear;
            document.getElementById('month').value = month;
        });
    </script>
</body>
</html>
