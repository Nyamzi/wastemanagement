<?php
session_start();
require_once 'dbconnect.php';

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
    $area_name = $_POST['area_name'];
    $waste_type = $_POST['waste_type'];
    $days_ahead = $_POST['days_ahead'];

    // Prepare data to send to the API
    $data = array(
        "area_name" => $area_name,
        "waste_type" => $waste_type,
        "days_ahead" => $days_ahead
    );

    // Initialize cURL session
    $ch = curl_init("http://localhost:5000/future_predictions");

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

    if ($http_code == 200 && isset($response_data['predictions'])) {
        $predictions = $response_data['predictions'];
        $success = true;
    } else {
        $error = $response_data['error'] ?? "Unable to get predictions";
        $success = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Future Waste Predictions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .prediction-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .chart-container {
            margin-top: 2rem;
            height: 400px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="prediction-form">
            <h2 class="text-center mb-4">Future Waste Predictions</h2>
            
            <form method="POST" action="" class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
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
                    <div class="col-md-4 mb-3">
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
                    <div class="col-md-4 mb-3">
                        <label for="days_ahead" class="form-label">Days Ahead</label>
                        <input type="number" class="form-control" id="days_ahead" name="days_ahead" 
                               min="1" max="30" value="7" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Get Predictions</button>
            </form>

            <?php if (isset($predictions)): ?>
                <!-- Chart -->
                <div class="chart-container">
                    <canvas id="predictionChart"></canvas>
                </div>

                <!-- Table -->
                <div class="table-responsive mt-4">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day of Week</th>
                                <th>Predicted Weight (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($predictions as $prediction): ?>
                                <tr>
                                    <td><?php echo $prediction['date']; ?></td>
                                    <td><?php echo date('l', strtotime($prediction['date'])); ?></td>
                                    <td><?php echo round($prediction['predicted_weight'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    // Prepare data for chart
                    const dates = <?php echo json_encode(array_column($predictions, 'date')); ?>;
                    const weights = <?php echo json_encode(array_column($predictions, 'predicted_weight')); ?>;
                    
                    // Create chart
                    const ctx = document.getElementById('predictionChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: dates,
                            datasets: [{
                                label: 'Predicted Waste Weight (kg)',
                                data: weights,
                                borderColor: 'rgb(75, 192, 192)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Weight (kg)'
                                    }
                                },
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                }
                            }
                        }
                    });
                </script>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger mt-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 