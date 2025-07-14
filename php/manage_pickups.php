<?php
session_start();

// Check if user is logged in and is a company
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

// Step 1: Get actual company_id from the companies table (not just user_id)
$company_id = null;
$result = $conn->query("SELECT company_id FROM companies WHERE user_id = $company_user_id");
if ($result && $row = $result->fetch_assoc()) {
    $company_id = $row['company_id'];
}

// Step 2: Get assigned areas for that company
$assigned_areas = [];
if ($company_id) {
    $stmt_areas = $conn->prepare("SELECT area_id FROM company_areas WHERE company_id = ?");
    $stmt_areas->bind_param("i", $company_id);
    $stmt_areas->execute();
    $result_areas = $stmt_areas->get_result();
    while ($row = $result_areas->fetch_assoc()) {
        $assigned_areas[] = $row['area_id'];
    }
    $stmt_areas->close();
}

// Step 3: Fetch pickup requests for the assigned areas with status 'pending' or 'accepted'
$pickup_requests = [];
if (!empty($assigned_areas)) {
    $placeholders = implode(',', array_fill(0, count($assigned_areas), '?'));
    $types = str_repeat('i', count($assigned_areas));
    $sql = "
    SELECT p.*, u.name AS user_name, a.area_name
    FROM pickup_requests p
    JOIN users u ON p.user_id = u.user_id
    JOIN areas a ON p.area_id = a.area_id
    WHERE p.area_id IN ($placeholders) AND p.status IN ('pending', 'accepted')
    ORDER BY p.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$assigned_areas);
    $stmt->execute();
    $pickup_requests = $stmt->get_result();
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Pickups</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">Pickup Requests for Your Assigned Areas</h2>

    <?php if (!empty($pickup_requests) && $pickup_requests->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Area</th>
                    <th>Waste Type</th>
                    <th>Weight (kg)</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $pickup_requests->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['area_name']) ?></td>
                        <td><?= htmlspecialchars($row['pickup_type']) ?></td>
                        <td>
                            <?= htmlspecialchars($row['weight']) ?: 'Not Set' ?> kg
                        </td>
                        <td>
                            <?php if ($row['weight'] && $row['status'] === 'accepted'): ?>
                                <span class="badge bg-success">Weight Added</span>
                            <?php else: ?>
                                <span class="badge bg-primary"><?= ucfirst(htmlspecialchars($row['status'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['created_at']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <a href="update_status.php?id=<?= htmlspecialchars($row['request_id']) ?>&action=accept" class="btn btn-sm btn-success">Accept</a>
                                <a href="update_status.php?id=<?= htmlspecialchars($row['request_id']) ?>&action=reject" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this request?')">Reject</a>
                            <?php elseif ($row['status'] === 'accepted' && !$row['weight']): ?>
                                <a href="update_status.php?id=<?= htmlspecialchars($row['request_id']) ?>&action=complete" class="btn btn-sm btn-primary">Complete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No pickup requests found for your assigned areas.</div>
    <?php endif; ?>

    <a href="companydashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
</div>
</body>
</html>
