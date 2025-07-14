<?php
session_start();
require_once 'dbconnect.php';

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
    <title>Recent Predictions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .predictions-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .back-button {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="predictions-container">
            <a href="predict_request.php" class="btn btn-secondary back-button">← Back to Prediction Form</a>
            <h2 class="text-center mb-4">Recent Predictions</h2>
            
            <?php if (!empty($recent_predictions)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Area</th>
                                <th>Waste Type</th>
                                <th>Predicted Weight (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_predictions as $prediction): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i', strtotime($prediction['prediction_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($prediction['area_name']); ?></td>
                                    <td><?php echo htmlspecialchars($prediction['waste_type']); ?></td>
                                    <td><?php echo round($prediction['predicted_weight'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">No predictions found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 