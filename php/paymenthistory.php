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

// Get company_id from the companies table
$company_q = $conn->prepare("SELECT company_id FROM companies WHERE user_id = ?");
$company_q->bind_param("i", $user_id);
$company_q->execute();
$result = $company_q->get_result();
if ($row = $result->fetch_assoc()) {
    $company_id = $row['company_id'];
} else {
    die("Company not found.");
}
$company_q->close();

// Fetch payments made to this company
$payments = [];
$stmt = $conn->prepare("
    SELECT u.name, p.plan, p.status, p.created_at 
    FROM payments p 
    JOIN users u ON p.user_id = u.user_id 
    WHERE p.company_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $payments[] = $row;
}
$stmt->close();
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

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-body">
            <h5 class="card-title mb-4">Payment History</h5>

            <?php if (empty($payments)): ?>
                <div class="alert alert-info">No payment records found for your company.</div>
            <?php else: ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Resident</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                            <tr>
                                <td><?= htmlspecialchars($pay['name']) ?></td>
                                <td><?= ucfirst($pay['plan']) ?></td>
                                <td><?= ucfirst($pay['status']) ?></td>
                                <td><?= date("Y-m-d", strtotime($pay['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <a href="companydashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>
