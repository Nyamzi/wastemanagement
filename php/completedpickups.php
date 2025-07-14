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

$user_id = $_SESSION['user_id'];

// Step 1: Get actual company_id from the companies table
$company_id = null;
$company_result = $conn->query("SELECT company_id FROM companies WHERE user_id = $user_id");
if ($company_result && $row = $company_result->fetch_assoc()) {
    $company_id = $row['company_id'];
} else {
    die("Company not found.");
}

// Step 2: Get completed pickups for the company
$sql = "SELECT pr.request_id, pr.pickup_type, u.name AS user_name, u.email AS user_email, a.area_name 
        FROM pickup_requests pr
        JOIN users u ON pr.user_id = u.user_id
        JOIN areas a ON pr.area_id = a.area_id
        WHERE pr.status = 'completed' AND pr.company_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $company_id);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Pickups</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .content {
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4">Completed Pickup Requests</h3>

    <?php if ($result->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>User Name</th>
                    <th>User Email</th>
                    <th>Area</th>
                    <th>Waste Type</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['request_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['user_email']); ?></td>
                        <td><?php echo htmlspecialchars($row['area_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['pickup_type']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No completed pickups available at the moment.</p>
    <?php endif; ?>

    <a href="companydashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
