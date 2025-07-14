<?php
session_start();
require 'vendor/autoload.php'; // Load Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: login.php");
    exit();
}

$company_user_id = $_SESSION['user_id'];

// DB connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get company_id
$company_id = null;
$result = $conn->query("SELECT company_id FROM companies WHERE user_id = $company_user_id");
if ($result && $row = $result->fetch_assoc()) {
    $company_id = $row['company_id'];
}

// Report Type Handling
$report_type = $_GET['report_type'] ?? 'wastetype';
$stats = [];

if ($company_id) {
    switch ($report_type) {
        case 'area_weight':
            $stmt = $conn->prepare("SELECT a.area_name, pr.pickup_type, COUNT(*) AS total_pickups, SUM(pr.weight) AS total_weight FROM pickup_requests pr JOIN areas a ON pr.area_id = a.area_id WHERE pr.company_id = ? AND pr.status = 'completed' GROUP BY pr.area_id, pr.pickup_type");
            break;

        case 'daily':
            $stmt = $conn->prepare("SELECT DATE(pr.created_at) AS date, SUM(pr.weight) AS total_weight FROM pickup_requests pr WHERE pr.company_id = ? AND pr.status = 'completed' GROUP BY DATE(pr.created_at) ORDER BY date DESC");
            break;

        case 'monthly':
            $stmt = $conn->prepare("SELECT DATE_FORMAT(pr.created_at, '%Y-%m') AS period, SUM(pr.weight) AS total_weight FROM pickup_requests pr WHERE pr.company_id = ? AND pr.status = 'completed' GROUP BY period ORDER BY period DESC");
            break;

        case 'quarterly':
            $stmt = $conn->prepare("SELECT CONCAT(YEAR(pr.created_at), '-Q', QUARTER(pr.created_at)) AS period, SUM(pr.weight) AS total_weight FROM pickup_requests pr WHERE pr.company_id = ? AND pr.status = 'completed' GROUP BY period ORDER BY period DESC");
            break;

        case 'yearly':
            $stmt = $conn->prepare("SELECT YEAR(pr.created_at) AS period, SUM(pr.weight) AS total_weight FROM pickup_requests pr WHERE pr.company_id = ? AND pr.status = 'completed' GROUP BY period ORDER BY period DESC");
            break;

        default:
            $stmt = $conn->prepare("SELECT pr.pickup_type, COUNT(*) AS total_pickups FROM pickup_requests pr WHERE pr.company_id = ? AND pr.status = 'completed' GROUP BY pr.pickup_type");
    }

    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $stats[] = $row;
    }
    $stmt->close();
}
$conn->close();

// Handle PDF download
if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    ob_start();

    echo "<h2>Pickup Report</h2>";
    echo "<table border='1' cellpadding='10' cellspacing='0'><thead><tr>";

    switch ($report_type) {
        case 'area_weight':
            echo "<th>Area</th><th>Waste Type</th><th>Total Pickups</th><th>Total Weight (kg)</th></tr></thead><tbody>";
            foreach ($stats as $row) {
                echo "<tr><td>" . htmlspecialchars($row['area_name']) . "</td><td>" . htmlspecialchars($row['pickup_type']) . "</td><td>" . htmlspecialchars($row['total_pickups']) . "</td><td>" . htmlspecialchars($row['total_weight']) . "</td></tr>";
            }
            break;

        case 'daily':
        case 'monthly':
        case 'quarterly':
        case 'yearly':
            echo "<th>Period</th><th>Total Weight (kg)</th></tr></thead><tbody>";
            foreach ($stats as $row) {
                echo "<tr><td>" . htmlspecialchars($row['date'] ?? $row['period']) . "</td><td>" . htmlspecialchars($row['total_weight']) . "</td></tr>";
            }
            break;

        default:
            echo "<th>Waste Type</th><th>Total Pickups</th></tr></thead><tbody>";
            foreach ($stats as $row) {
                echo "<tr><td>" . htmlspecialchars($row['pickup_type']) . "</td><td>" . htmlspecialchars($row['total_pickups']) . "</td></tr>";
            }
    }

    echo "</tbody></table>";

    $html = ob_get_clean();
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("pickup_report.pdf", ["Attachment" => 1]);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pickup Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2>Pickup Report</h2>

    <form method="GET" class="mb-4">
        <label for="report_type">Select Report Type:</label>
        <select name="report_type" id="report_type" onchange="this.form.submit()" class="form-select w-auto d-inline ms-2">
            <option value="wastetype" <?= $report_type === 'wastetype' ? 'selected' : '' ?>>Waste Type Report</option>
            <option value="area_weight" <?= $report_type === 'area_weight' ? 'selected' : '' ?>>Area & Weight Report</option>
            <option value="daily" <?= $report_type === 'daily' ? 'selected' : '' ?>>Daily Weight Report</option>
            <option value="monthly" <?= $report_type === 'monthly' ? 'selected' : '' ?>>Monthly Weight Report</option>
            <option value="quarterly" <?= $report_type === 'quarterly' ? 'selected' : '' ?>>Quarterly Weight Report</option>
            <option value="yearly" <?= $report_type === 'yearly' ? 'selected' : '' ?>>Yearly Weight Report</option>
        </select>
    </form>

    <?php if ($report_type === 'area_weight'): ?>
        <h4>Area & Weight Report</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Area</th>
                    <th>Waste Type</th>
                    <th>Total Pickups</th>
                    <th>Total Weight (kg)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['area_name']) ?></td>
                        <td><?= htmlspecialchars($row['pickup_type']) ?></td>
                        <td><?= htmlspecialchars($row['total_pickups']) ?></td>
                        <td><?= htmlspecialchars($row['total_weight']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif (in_array($report_type, ['daily', 'monthly', 'quarterly', 'yearly'])): ?>
        <h4><?= ucfirst($report_type) ?> Weight Report</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th><?= $report_type === 'daily' ? 'Date' : 'Period' ?></th>
                    <th>Total Weight (kg)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['date'] ?? $row['period']) ?></td>
                        <td><?= htmlspecialchars($row['total_weight']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php else: ?>
        <h4>Waste Type Report</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Waste Type</th>
                    <th>Total Pickups</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['pickup_type']) ?></td>
                        <td><?= htmlspecialchars($row['total_pickups']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="reports.php?download=pdf&report_type=<?= $report_type ?>" class="btn btn-primary">Download PDF Report</a>
    <a href="companydashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 for the report_type dropdown with no search
        $('#report_type').select2({
            placeholder: "Select Report Type",
            minimumResultsForSearch: -1  // This disables the search functionality
        });
    });
</script>
</body>
</html>
