<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastemanagement";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    header("Location: cancelrequests.php");
    exit();
}

$request_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch the request details
$stmt = $conn->prepare("
    SELECT pr.*, a.area_name 
    FROM pickup_requests pr 
    JOIN areas a ON pr.area_id = a.area_id 
    WHERE pr.request_id = ? AND pr.user_id = ? AND pr.status = 'pending'
");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    header("Location: cancelrequests.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $area_id = $_POST['area_id'];
    $pickup_type = $_POST['pickup_type'];
    $description = $_POST['description'];
    
    // Update the request
    $update_stmt = $conn->prepare("
        UPDATE pickup_requests 
        SET area_id = ?, pickup_type = ?, description = ? 
        WHERE request_id = ? AND user_id = ?
    ");
    $update_stmt->bind_param("issii", $area_id, $pickup_type, $description, $request_id, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Request updated successfully!";
        header("Location: cancelrequests.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating request. Please try again.";
    }
}

// Fetch areas for dropdown
$areas_result = $conn->query("SELECT area_id, area_name FROM areas ORDER BY area_name");
$areas = $areas_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Pickup Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h3 class="text-center mb-4">Edit Pickup Request</h3>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="area_id" class="form-label">Area</label>
                <select class="form-select" id="area_id" name="area_id" required>
                    <option value="">Select Area</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?= $area['area_id'] ?>" <?= $area['area_id'] == $request['area_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($area['area_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="pickup_type" class="form-label">Pickup Type</label>
                <select class="form-select" id="pickup_type" name="pickup_type" required>
                    <option value="">Select Type</option>
                    <option value="recyclable" <?= $request['pickup_type'] == 'recyclable' ? 'selected' : '' ?>>Recyclable</option>
                    <option value="non-recyclable" <?= $request['pickup_type'] == 'non-recyclable' ? 'selected' : '' ?>>Non-Recyclable</option>
                    <option value="hazardous" <?= $request['pickup_type'] == 'hazardous' ? 'selected' : '' ?>>Hazardous</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($request['description']) ?></textarea>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary me-2">Update Request</button>
                <a href="cancelrequests.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 