<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$company_user_id = $_SESSION['user_id'];

// Get the actual company_id from the companies table using the logged-in user's user_id
$company_query = $conn->prepare("SELECT company_id FROM companies WHERE user_id = ?");
$company_query->bind_param("i", $company_user_id);
$company_query->execute();
$company_result = $company_query->get_result();

if ($company_row = $company_result->fetch_assoc()) {
    $company_id = $company_row['company_id'];
} else {
    die("No company associated with this account.");
}

// Approve payment if requested
if (isset($_GET['approve'])) {
    $payment_id = $_GET['approve'];

    // Approve the payment
    $stmt = $conn->prepare("UPDATE payments SET status='paid', approved_by=?, approved_at=NOW() WHERE payment_id=?");
    $stmt->bind_param("ii", $company_user_id, $payment_id);
    if ($stmt->execute()) {
        // Fetch the user_id for the approved payment
        $get_user = $conn->prepare("SELECT user_id FROM payments WHERE payment_id = ?");
        $get_user->bind_param("i", $payment_id);
        $get_user->execute();
        $get_result = $get_user->get_result();

        if ($row = $get_result->fetch_assoc()) {
            $user_id = $row['user_id'];

            // Add notification
            $msg = "Your payment has been approved.";
            $insert = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $insert->bind_param("is", $user_id, $msg);
            $insert->execute();
        }
    }
}

// Fetch pending payments for this company
$stmt = $conn->prepare("
    SELECT p.*, u.name 
    FROM payments p 
    JOIN users u ON p.user_id = u.user_id 
    WHERE p.status = 'pending' AND p.company_id = ?
");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Approve Payments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5 bg-light">
    <h2>Pending Payments</h2>

    <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-info">No pending payments found.</div>
    <?php else: ?>
        <table class="table table-bordered bg-white shadow">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Transaction ID</th>
                    <th>Notes</th>
                    <th>Approve</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= ucfirst($row['plan']) ?></td>
                    <td><?= htmlspecialchars($row['amount']) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                    <td><?= htmlspecialchars($row['transaction_id']) ?></td>
                    <td><?= htmlspecialchars($row['notes']) ?></td>
                    <td><a href="?approve=<?= $row['payment_id'] ?>" class="btn btn-success btn-sm">Approve</a></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <a href="companydashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
</body>
</html>
