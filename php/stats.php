<?php
session_start();

// Ensure the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: login.php");
    exit();
}

$company_user_id = $_SESSION['user_id'];

// Connect to database
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get company_id
$company_id = null;
if ($res = $conn->query("SELECT company_id FROM companies WHERE user_id = $company_user_id")) {
    if ($row = $res->fetch_assoc()) {
        $company_id = $row['company_id'];
    }
}

// Gather stats
$stats = [
    'total_requests'       => 0,
    'total_weight'         => 0,
    'most_active_area'     => 'N/A',
    'day_with_most'        => 'N/A',
    'status_distribution'  => ['pending'=>0, 'accepted'=>0, 'completed'=>0, 'rejected'=>0],
    'top_waste_type'       => 'N/A',
];

if ($company_id) {
    // build placeholders
    $areas_q = $conn->prepare("SELECT area_id FROM company_areas WHERE company_id = ?");
    $areas_q->bind_param("i",$company_id);
    $areas_q->execute();
    $areas_res = $areas_q->get_result();
    $assigned = [];
    while($r=$areas_res->fetch_assoc()) $assigned[] = $r['area_id'];
    $areas_q->close();

    if (count($assigned)) {
        $ph = implode(',', array_fill(0,count($assigned),'?'));
        $types = str_repeat('i',count($assigned));

        // Total requests
        $q = $conn->prepare("SELECT COUNT(*) AS c FROM pickup_requests WHERE area_id IN ($ph)");
        $q->bind_param($types, ...$assigned);
        $q->execute(); $r = $q->get_result()->fetch_assoc();
        $stats['total_requests'] = $r['c']; $q->close();

        // Total weight (completed only)
        $q = $conn->prepare("SELECT SUM(weight) AS w FROM pickup_requests WHERE area_id IN ($ph) AND status='completed'");
        $q->bind_param($types, ...$assigned);
        $q->execute(); $r = $q->get_result()->fetch_assoc();
        $stats['total_weight'] = $r['w'] ?: 0; $q->close();

        // Most active area
        $q = $conn->prepare("
            SELECT a.area_name, COUNT(*) AS cnt
            FROM pickup_requests p
            JOIN areas a ON p.area_id=a.area_id
            WHERE p.area_id IN ($ph)
            GROUP BY p.area_id
            ORDER BY cnt DESC
            LIMIT 1
        ");
        $q->bind_param($types, ...$assigned);
        $q->execute(); if($r=$q->get_result()->fetch_assoc()) {
            $stats['most_active_area'] = $r['area_name'];
        }
        $q->close();

        // Busiest day
        $q = $conn->prepare("
            SELECT DATE(created_at) AS d, COUNT(*) AS cnt
            FROM pickup_requests
            WHERE area_id IN ($ph)
            GROUP BY d
            ORDER BY cnt DESC
            LIMIT 1
        ");
        $q->bind_param($types, ...$assigned);
        $q->execute(); if($r=$q->get_result()->fetch_assoc()) {
            $stats['day_with_most'] = "{$r['d']} ({$r['cnt']} requests)";
        }
        $q->close();

        // Status breakdown
        $q = $conn->prepare("
            SELECT status, COUNT(*) AS cnt
            FROM pickup_requests
            WHERE area_id IN ($ph)
            GROUP BY status
        ");
        $q->bind_param($types, ...$assigned);
        $q->execute(); $res = $q->get_result();
        while($r=$res->fetch_assoc()) {
            $stats['status_distribution'][$r['status']] = $r['cnt'];
        }
        $q->close();

        // Top waste type
        $q = $conn->prepare("
            SELECT pickup_type, COUNT(*) AS cnt
            FROM pickup_requests
            WHERE area_id IN ($ph)
            GROUP BY pickup_type
            ORDER BY cnt DESC
            LIMIT 1
        ");
        $q->bind_param($types, ...$assigned);
        $q->execute(); if($r=$q->get_result()->fetch_assoc()) {
            $stats['top_waste_type'] = "{$r['pickup_type']} ({$r['cnt']})";
        }
        $q->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Pickup Statistics</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h2 class="mb-4 text-center">Company Pickup Statistics</h2>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="card border-primary">
          <div class="card-body text-center">
            <h5 class="card-title">Total Requests</h5>
            <p class="display-4"><?= $stats['total_requests'] ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-dark">
          <div class="card-body text-center">
            <h5 class="card-title">Total Weight Collected</h5>
            <p class="display-4"><?= $stats['total_weight'] ?> kg</p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-success">
          <div class="card-body text-center">
            <h5 class="card-title">Top Area</h5>
            <p class="h4"><?= htmlspecialchars($stats['most_active_area']) ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-warning">
          <div class="card-body text-center">
            <h5 class="card-title">Busiest Day</h5>
            <p class="h5"><?= htmlspecialchars($stats['day_with_most']) ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-info">
          <div class="card-body">
            <h5 class="card-title text-center">Status Breakdown</h5>
            <ul class="list-group">
              <li class="list-group-item">Pending: <?= $stats['status_distribution']['pending'] ?></li>
              <li class="list-group-item">Accepted: <?= $stats['status_distribution']['accepted'] ?></li>
              <li class="list-group-item">Completed: <?= $stats['status_distribution']['completed'] ?></li>
              <li class="list-group-item">Rejected: <?= $stats['status_distribution']['rejected'] ?></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card border-secondary">
          <div class="card-body text-center">
            <h5 class="card-title">Top Waste Type</h5>
            <p class="h4"><?= htmlspecialchars($stats['top_waste_type']) ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center mt-5">
      <a href="companydashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
  </div>
</body>
</html>