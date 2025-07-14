<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success_message = $error_message = "";

// Handle Pickup Request Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $pickup_type = $_POST['pickup_type'];
    $area_id = $_POST['area_id'];
    $notes = $_POST['notes'];

    if (empty($pickup_type) || empty($area_id)) {
        $error_message = "Please provide all required fields.";
    } else {
        // Get company assigned to the selected area
        $stmt = $conn->prepare("SELECT company_id FROM areas WHERE area_id = ?");
        $stmt->bind_param("i", $area_id);
        $stmt->execute();
        $stmt->bind_result($company_id);
        $stmt->fetch();
        $stmt->close();

        if ($company_id) {
            $insert = $conn->prepare("INSERT INTO pickup_requests (user_id, pickup_type, area_id, notes, status, company_id) VALUES (?, ?, ?, ?, 'pending', ?)");
            $insert->bind_param("isisi", $user_id, $pickup_type, $area_id, $notes, $company_id);
            if ($insert->execute()) {
                $success_message = "Pickup request submitted!";
            } else {
                $error_message = "Error submitting request.";
            }
            $insert->close();
        } else {
            $error_message = "No company assigned to this area.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pickups</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .table-container {
            margin: 30px auto;
            max-width: 900px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="table-container">
        <h2 class="text-center">Previous Pickups</h2>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Pickup ID</th>
                    <th>Address</th>
                    <th>Pickup Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['id']); ?></td>
                            <td><?php echo htmlspecialchars($row['address']); ?></td>
                            <td><?php echo htmlspecialchars($row['pickup_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center">No previous pickups found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
         <!-- Button to go back to the Company Dashboard -->
    <a href="companydashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
    </div>
</div>
     
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
