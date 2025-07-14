<?php
session_start();

// Check if the user is logged in and is a company user
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'company') {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastemanagement"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get company data from the session
$user_id = $_SESSION['user_id'];

// Query to fetch scheduled pickups based on the company
// Assuming 'pickup_areas' is the table that stores area details and 'scheduled_pickups' has area_id, waste_type, collection_day
$sql = "
    SELECT sp.area_id, pa.area_name, sp.waste_type, sp.collection_day
    FROM waste_collection_schedule sp
    INNER JOIN pickup_areas pa ON sp.area_id = pa.id
    WHERE sp.company_id = $user_id
    ORDER BY sp.collection_day ASC
";
$result = $conn->query($sql);

// Fetch scheduled pickups
$waste_collection_schedule = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $waste_collection_schedule[] = $row;
    }
} else {
    echo "No scheduled pickups found.";
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard</title>
      <!-- Bootstrap CSS -->
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .content {
            margin-top: 50px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .no-schedule {
            text-align: center;
            color: #6c757d;
            font-size: 18px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <h2 class="text-center mb-4">Your Scheduled Pickups</h2>

            <?php if (empty($waste_collection_schedule)): ?>
                <p class="no-schedule">No scheduled pickups found.</p>
            <?php else: ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Area</th>
                            <th>Waste Type</th>
                            <th>Collection Day</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($waste_collection_schedule as $pickup): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pickup['area_name']); ?></td>
                                <td><?php echo htmlspecialchars($pickup['waste_type']); ?></td>
                                <td><?php echo htmlspecialchars($pickup['collection_day']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="companydashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
