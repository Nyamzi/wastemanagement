<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastemanagement";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Cancel Pickup Request Logic
if (isset($_GET['cancel_request_id'])) {
    $cancel_request_id = $_GET['cancel_request_id'];

    // Update the request status to 'canceled'
    $cancel_sql = "UPDATE pickup_requests SET status = 'canceled' WHERE request_id = ? AND user_id = ?";
    $cancel_stmt = $conn->prepare($cancel_sql);
    $cancel_stmt->bind_param("ii", $cancel_request_id, $user_id);

    if ($cancel_stmt->execute()) {
        $success_message = "Your pickup request has been canceled successfully.";
    } else {
        $error_message = "Error canceling the pickup request: " . $cancel_stmt->error;
    }

    $cancel_stmt->close();
}

// Fetch Pending Pickup Requests
$sql = "
    SELECT pr.request_id, a.area_name, pr.pickup_type, pr.created_at, pr.status
    FROM pickup_requests pr
    INNER JOIN areas a ON pr.area_id = a.area_id
    WHERE pr.user_id = ? AND pr.status = 'pending'
    ORDER BY pr.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$pending_requests = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pending_requests[] = $row;
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Pending Pickup Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .content {
            margin-top: 50px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .no-requests {
            color: #777;
            text-align: center;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h3 class="text-center mb-4">Your Pending Pickup Requests</h3>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (empty($pending_requests)): ?>
            <p class="no-requests">You have no pending pickup requests.</p>
        <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Area</th>
                        <th>Pickup Type</th>
                        <th>Date Requested</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_requests as $index => $request): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($request['area_name']) ?></td>
                            <td><?= htmlspecialchars($request['pickup_type']) ?></td>
                            <td><?= date("Y-m-d H:i", strtotime($request['created_at'])) ?></td>
                            <td><span class="badge bg-warning">Pending</span></td>
                            <td>
                                <a href="pickup.php?edit_id=<?= $request['request_id'] ?>" class="btn btn-sm btn-primary me-2">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="cancel_request.php?id=<?= $request['request_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this request?')">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="userdashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
