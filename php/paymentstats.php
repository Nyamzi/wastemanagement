<?php
session_start();

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'company') {
    header("Location: login.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Get company_id
$company_q = $conn->query("SELECT company_id FROM companies WHERE user_id = $user_id");
if ($company_q && $row = $company_q->fetch_assoc()) {
    $company_id = $row['company_id'];
} else {
    die("Company not found.");
}

// Pie chart: payment breakdown
$payment_data = ['monthly' => 0, 'quarterly' => 0, 'yearly' => 0];
$stats = $conn->query("SELECT plan, COUNT(*) as total 
                       FROM payments 
                       WHERE company_id = $company_id AND status = 'paid' 
                       GROUP BY plan");
while ($row = $stats->fetch_assoc()) {
    $payment_data[$row['plan']] = $row['total'];
}

// Line chart: payments over time
$monthly_stats = [];
$sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
        FROM payments 
        WHERE company_id = $company_id AND status = 'paid'
        GROUP BY month 
        ORDER BY month";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $monthly_stats[$row['month']] = $row['count'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <h2 class="mb-4">Payment Statistics</h2>

    <!-- Pie Chart -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Payment Breakdown (Pie Chart)</h5>
            <canvas id="pieChart" style="max-height: 200px; max-width: 100%;"></canvas>
        </div>
    </div>

    <!-- Line Chart -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Payments Over Time (Line Chart)</h5>
            <canvas id="lineChart" height="50"></canvas>
        </div>
    </div>

    <a href="companydashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
</div>

<script>
const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: ['Monthly', 'Quarterly', 'Yearly'],
        datasets: [{
            data: [
                <?= $payment_data['monthly'] ?>,
                <?= $payment_data['quarterly'] ?>,
                <?= $payment_data['yearly'] ?>
            ],
            backgroundColor: ['#36A2EB', '#FFCE56', '#4BC0C0']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

const lineCtx = document.getElementById('lineChart').getContext('2d');
new Chart(lineCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($monthly_stats)) ?>,
        datasets: [{
            label: 'Payments',
            data: <?= json_encode(array_values($monthly_stats)) ?>,
            borderColor: '#007bff',
            fill: false,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        scales: {
            x: { title: { display: true, text: 'Month' } },
            y: { title: { display: true, text: 'Payments' }, beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
