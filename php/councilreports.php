<?php
session_start();

// Check if user is logged in and is part of the municipal council
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'municipal_council') {
    header("Location: login.php");
    exit();
}

require 'vendor/autoload.php'; // Load Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

// DB connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Filters
$filters = [
    'company_id' => $_GET['company_id'] ?? null,
    'area_id' => $_GET['area_id'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
];

// Get list of companies
$companies = $conn->query("SELECT company_id, company_name FROM companies")->fetch_all(MYSQLI_ASSOC);

// Get list of areas
$areas = $conn->query("SELECT area_id, area_name FROM areas")->fetch_all(MYSQLI_ASSOC);

// Query for report data
$query = "SELECT 
            c.company_name, 
            a.area_name, 
            pr.pickup_type, 
            SUM(pr.weight) AS total_weight, 
            COUNT(pr.request_id) AS total_pickups, 
            DATE_FORMAT(pr.created_at, '%Y-%m-%d') AS pickup_date
          FROM pickup_requests pr
          JOIN companies c ON pr.company_id = c.company_id
          JOIN areas a ON pr.area_id = a.area_id
          WHERE pr.status = 'completed'";

$bindParams = [];
$bindTypes = "";

// Apply filters
if (!empty($filters['company_id'])) {
    $query .= " AND pr.company_id = ?";
    $bindParams[] = $filters['company_id'];
    $bindTypes .= "i";
}

if (!empty($filters['area_id'])) {
    $query .= " AND pr.area_id = ?";
    $bindParams[] = $filters['area_id'];
    $bindTypes .= "i";
}

if (!empty($filters['start_date'])) {
    $query .= " AND pr.created_at >= ?";
    $bindParams[] = $filters['start_date'];
    $bindTypes .= "s";
}

if (!empty($filters['end_date'])) {
    $query .= " AND pr.created_at <= ?";
    $bindParams[] = $filters['end_date'];
    $bindTypes .= "s";
}

$query .= " GROUP BY pr.company_id, pr.area_id, pr.pickup_type ORDER BY pr.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if ($bindParams) {
    $stmt->bind_param($bindTypes, ...$bindParams);
}
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total weight
$totalWeight = array_reduce($results, function ($carry, $item) {
    return $carry + $item['total_weight'];
}, 0);

$stmt->close();

// Handle PDF download
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    ob_start();

    echo "<h2>Pickup Data Report</h2>";
    echo "<p>Total Weight: " . htmlspecialchars($totalWeight) . " kg</p>";
    echo "<table border='1' cellpadding='10' cellspacing='0'><thead><tr>
            <th>Company</th>
            <th>Area</th>
            <th>Waste Type</th>
            <th>Total Pickups</th>
            <th>Total Weight (kg)</th>
            <th>Last Pickup Date</th>
          </tr></thead><tbody>";

    foreach ($results as $row) {
        echo "<tr>
                <td>" . htmlspecialchars($row['company_name']) . "</td>
                <td>" . htmlspecialchars($row['area_name']) . "</td>
                <td>" . htmlspecialchars($row['pickup_type']) . "</td>
                <td>" . htmlspecialchars($row['total_pickups']) . "</td>
                <td>" . htmlspecialchars($row['total_weight']) . "</td>
                <td>" . htmlspecialchars($row['pickup_date']) . "</td>
              </tr>";
    }

    echo "</tbody></table>";

    $html = ob_get_clean();
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("pickup_data_report.pdf", ["Attachment" => 1]);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Municipal Council - Pickup Data</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Municipal Council - Pickup Data</h2>

    <form method="GET" class="row mb-4">
        <div class="col-md-3">
            <label for="company_id" class="form-label">Company:</label>
            <select name="company_id" id="company_id" class="form-select">
                <option value="">All Companies</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?= htmlspecialchars($company['company_id']) ?>" <?= $filters['company_id'] == $company['company_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($company['company_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="area_id" class="form-label">Area:</label>
            <select name="area_id" id="area_id" class="form-select">
                <option value="">All Areas</option>
                <?php foreach ($areas as $area): ?>
                    <option value="<?= htmlspecialchars($area['area_id']) ?>" <?= $filters['area_id'] == $area['area_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($area['area_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="start_date" class="form-label">Start Date:</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date']) ?>">
        </div>
        <div class="col-md-2">
            <label for="end_date" class="form-label">End Date:</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date']) ?>">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <h4>Total Weight: <?= htmlspecialchars($totalWeight) ?> kg</h4>

    <table class="table table-bordered">
        <thead>
        <tr>
            <th>Company</th>
            <th>Area</th>
            <th>Waste Type</th>
            <th>Total Pickups</th>
            <th>Total Weight (kg)</th>
            <th>Last Pickup Date</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!empty($results)): ?>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['company_name']) ?></td>
                    <td><?= htmlspecialchars($row['area_name']) ?></td>
                    <td><?= htmlspecialchars($row['pickup_type']) ?></td>
                    <td><?= htmlspecialchars($row['total_pickups']) ?></td>
                    <td><?= htmlspecialchars($row['total_weight']) ?></td>
                    <td><?= htmlspecialchars($row['pickup_date']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">No data found.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <a href="council_reports.php?download=pdf" class="btn btn-primary">Download PDF Report</a>
    <a href="municipaldashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
</body>
</html>
