<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'municipal_council') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Initialize date range for filtering
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-01');
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-t');

// Get total companies and residents count
$companies = $conn->query("SELECT COUNT(*) AS total FROM users WHERE user_type='company' AND is_verified = 1");
$total_companies = $companies->fetch_assoc()['total'];

$residents = $conn->query("SELECT COUNT(*) AS total FROM users WHERE user_type='resident'");
$total_residents = $residents->fetch_assoc()['total'];

// Get monthly pickups for the filtered date range
$monthlyData = [];
$result = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
    FROM pickup_requests
    WHERE created_at BETWEEN '$start_date' AND '$end_date'
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
");
while ($row = $result->fetch_assoc()) {
    $monthlyData[] = $row;
}
$monthlyData = array_reverse($monthlyData); // Show from oldest to newest

// Get most popular pickup day for the filtered date range
$dayResult = $conn->query("
    SELECT DAYNAME(created_at) AS day, COUNT(*) AS count
    FROM pickup_requests
    WHERE created_at BETWEEN '$start_date' AND '$end_date'
    GROUP BY day
    ORDER BY count DESC
    LIMIT 1
");
$popular_day = $dayResult->fetch_assoc()['day'] ?? 'N/A';

// Close DB connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Municipal Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            display: flex;
            font-family: Arial, sans-serif;
            background-color: #f1f2f7;
        }
        .sidebar {
            width: 250px;
            background: #343a40;
            color: #fff;
            height: 100vh;
            padding: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 12px 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .sidebar a:hover {
            background: #007bff;
        }
        .content {
            flex: 1;
            padding: 30px;
        }
        .card {
            border-radius: 10px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h3 class="text-center">Municipal Dashboard</h3>
    <a href="assign_companies.php">🏠 Company and Areas</a>
    <a href="municipal_schedule.php">📅 Schedule Pickups</a>
    <a href="manage_company_area.php">📍 Manage Areas</a>
    <a href="councilreports.php">📊 Reports</a>
    <a href="location.php">⚙️ Settings</a>
    <a href="logout.php" class="btn btn-danger mt-3 w-100">Logout</a>
</div>

<!-- Content Area -->
<div class="content">
    <h2>Welcome to Municipal Council Dashboard</h2>

    <!-- Date Range Filter Form -->
    <form method="POST" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?= $start_date ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?= $end_date ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <!-- Dashboard Overview -->
    <div class="row">
        <div class="col-md-4">
            <div class="card p-3">
                <h5>Total Verified Companies</h5>
                <p class="fs-4"><?= $total_companies ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <h5>Total Residents</h5>
                <p class="fs-4"><?= $total_residents ?></p>
            </div>
        </div>
    </div>

    <!-- Dashboard Charts & Stats -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card p-3">
                <h5>Monthly Pickup Trends</h5>
                <canvas id="monthlyChart" height="200"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3">
                <h5>Most Popular Pickup Day</h5>
                <p class="fs-4"><?= htmlspecialchars($popular_day) ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script>
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($monthlyData, 'month')) ?>,
            datasets: [{
                label: 'Pickups',
                data: <?= json_encode(array_column($monthlyData, 'total')) ?>,
                backgroundColor: '#007bff'
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

</body>
</html>
