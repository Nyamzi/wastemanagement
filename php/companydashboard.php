<?php
session_start();

// Check if the user is logged in and if their user_type is 'company'
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

// Fetch user info
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $company = $result->fetch_assoc();
} else {
    die("Company not found.");
}

// --- STATISTICS LOGIC ---
$company_id = null;
$company_q = $conn->query("SELECT company_id FROM companies WHERE user_id = $user_id");
if ($company_q && $row = $company_q->fetch_assoc()) {
    $company_id = $row['company_id'];
}

$stats_total = 0;
$stats_area = 'N/A';
$stats_day = 'N/A';
$stats_day_count = 0;

$payment_data = ['monthly' => 0, 'quarterly' => 0, 'yearly' => 0];

if ($company_id) {
    // Total pickups
    $sql = "SELECT COUNT(*) as total 
            FROM pickup_requests 
            WHERE area_id IN (
                SELECT area_id FROM company_areas WHERE company_id = $company_id
            )";
    $res = $conn->query($sql);
    if ($res && $r = $res->fetch_assoc()) $stats_total = $r['total'];

    // Top area
    $sql = "SELECT a.area_name, COUNT(*) as count 
            FROM pickup_requests p
            JOIN areas a ON p.area_id = a.area_id
            WHERE p.area_id IN (
                SELECT area_id FROM company_areas WHERE company_id = $company_id
            )
            GROUP BY p.area_id 
            ORDER BY count DESC LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $r = $res->fetch_assoc()) $stats_area = $r['area_name'];

    // Busiest day
    $sql = "SELECT DATE(created_at) as day, COUNT(*) as count 
            FROM pickup_requests
            WHERE area_id IN (
                SELECT area_id FROM company_areas WHERE company_id = $company_id
            )
            GROUP BY day
            ORDER BY count DESC LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $r = $res->fetch_assoc()) {
        $stats_day = $r['day'];
        $stats_day_count = $r['count'];
    }

    // Payment breakdown for chart
    $stats = $conn->query("SELECT plan AS plan_type, COUNT(*) as total 
                           FROM payments 
                           WHERE user_id IN (
                               SELECT user_id 
                               FROM company_areas 
                               WHERE company_id = $company_id
                           ) AND status = 'paid' 
                           GROUP BY plan");
    while ($row = $stats->fetch_assoc()) {
        $payment_data[strtolower($row['plan_type'])] = $row['total'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            padding: 20px;
            height: 100vh;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px;
            display: block;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .sidebar a:hover {
            background-color: #007bff;
        }
        .content {
            flex: 1;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-left: 20px;
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <h3 class="text-center">Company Dashboard</h3>
        <a href="companydashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="manage_pickups.php"><i class="fas fa-truck"></i> Pickups</a>
        <a href="completedpickups.php"><i class="fas fa-check"></i> Completed Pickups</a>
        <a href="approve_payments.php"><i class="fas fa-credit-card"></i> Payments</a>
        <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
        <a href="companyprofile.php"><i class="fas fa-cogs"></i> Company Settings</a>
        <hr>
        <a href="logout.php" class="btn btn-danger w-100">Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="row row-cols-1 row-cols-md-3 g-4">

            <!-- Pickup Statistics -->
            <div class="col">
                <div class="card h-100 bg-dark text-white shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <h5 class="card-title">Pickup Statistics</h5>
                        <p class="card-text">Total Requests: <strong><?= $stats_total ?></strong></p>
                        <p class="card-text">Top Area: <strong><?= $stats_area ?></strong></p>
                        <p class="card-text">Busiest Day: <strong><?= $stats_day ?></strong> (<?= $stats_day_count ?> pickups)</p>
                        <a href="stats.php" class="btn btn-light text-dark">View Detailed Stats</a>
                    </div>
                </div>
            </div>

            <!-- Payment Subscriptions -->
            <div class="col">
                <div class="card h-100 bg-success text-white shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-coins fa-3x mb-3"></i>
                        <h5 class="card-title">Payment Subscriptions</h5>
                        <p class="card-text">Monthly: <strong><?= $payment_data['monthly'] ?? 0 ?></strong></p>
                        <p class="card-text">Quarterly: <strong><?= $payment_data['quarterly'] ?? 0 ?></strong></p>
                        <p class="card-text">Yearly: <strong><?= $payment_data['yearly'] ?? 0 ?></strong></p>
                        <a href="paymentstats.php" class="btn btn-light text-success">View Payment Stats</a>
                    </div>
                </div>
            </div>

            <!-- Payments -->
            <div class="col">
                <div class="card h-100 bg-warning text-dark shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-wallet fa-3x mb-3"></i>
                        <h5 class="card-title">Payments Received</h5>
                        <p class="card-text">You’ve received in payments.</p>
                        <a href="paymenthistory.php" class="btn btn-light text-warning">View Payments</a>
                    </div>
                </div>
            </div>

 


           

        </div>
    </div>

</div>

</body>
</html>
