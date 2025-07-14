<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter parameters
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Fetch completed pickup requests for the logged-in user
$user_id = $_SESSION['user_id'];
$sql = "SELECT pr.*, a.area_name, c.company_name 
        FROM pickup_requests pr 
        JOIN areas a ON pr.area_id = a.area_id 
        JOIN companies c ON pr.company_id = c.company_id 
        WHERE pr.user_id = ? 
        AND pr.status = 'completed' 
        AND MONTH(pr.created_at) = ? 
        AND YEAR(pr.created_at) = ?
        ORDER BY pr.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

// Calculate totals
$total_fees = 0;
$total_weight = 0;
$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
    $total_fees += $row['fee'];
    $total_weight += $row['weight'];
}

// Get available months and years for filter
$months_sql = "SELECT DISTINCT MONTH(created_at) as month, YEAR(created_at) as year 
               FROM pickup_requests 
               WHERE user_id = ? AND status = 'completed' 
               ORDER BY year DESC, month DESC";
$months_stmt = $conn->prepare($months_sql);
$months_stmt->bind_param("i", $user_id);
$months_stmt->execute();
$months_result = $months_stmt->get_result();
$available_months = [];
while ($row = $months_result->fetch_assoc()) {
    $available_months[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .payment-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table th {
            background-color: #f8f9fa;
        }
        .summary-card {
            background-color: #e8f5e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card payment-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Payment History</h4>
                        <div class="filter-section">
                            <form method="GET" class="d-flex gap-2">
                                <select name="month" class="form-select" onchange="this.form.submit()">
                                    <?php
                                    $months = [
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    ];
                                    foreach ($months as $num => $name) {
                                        $selected = $num == $month ? 'selected' : '';
                                        echo "<option value='$num' $selected>$name</option>";
                                    }
                                    ?>
                                </select>
                                <select name="year" class="form-select" onchange="this.form.submit()">
                                    <?php
                                    $current_year = date('Y');
                                    for ($y = $current_year; $y >= $current_year - 2; $y--) {
                                        $selected = $y == $year ? 'selected' : '';
                                        echo "<option value='$y' $selected>$y</option>";
                                    }
                                    ?>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($requests) > 0): ?>
                            <div class="summary-card">
                                <div class="summary-item">
                                    <span>Total Weight Collected:</span>
                                    <strong><?= number_format($total_weight, 2) ?> kg</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Total Fees:</span>
                                    <strong>UGX <?= number_format($total_fees) ?></strong>
                                </div>
                                <div class="summary-item">
                                    <span>Number of Pickups:</span>
                                    <strong><?= count($requests) ?></strong>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Area</th>
                                            <th>Company</th>
                                            <th>Waste Type</th>
                                            <th>Weight (kg)</th>
                                            <th>Fee (UGX)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $request): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($request['created_at'])) ?></td>
                                                <td><?= htmlspecialchars($request['area_name']) ?></td>
                                                <td><?= htmlspecialchars($request['company_name']) ?></td>
                                                <td><?= htmlspecialchars($request['pickup_type']) ?></td>
                                                <td><?= number_format($request['weight'], 2) ?></td>
                                                <td><?= number_format($request['fee']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No completed pickup requests found for the selected period.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$months_stmt->close();
$conn->close();
?> 