<?php
session_start();

// Check if user is logged in
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

$edit_mode = false;
$request_data = null;

// Check if we're in edit mode
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $request_id = $_GET['edit_id'];
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
    $request_data = $result->fetch_assoc();
    
    if (!$request_data) {
        header("Location: cancelrequests.php");
        exit();
    }
}

// Handle Pickup Request Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $pickup_type = $_POST['pickup_type'];
    $area_id = $_POST['area_id'];
    $pickup_time = $_POST['pickup_time'];
    $weight = null;

    if (empty($pickup_type) || empty($area_id) || empty($pickup_time)) {
        $error_message = "Please provide all the required fields!";
    } else {
        if ($edit_mode) {
            // Update existing request
            $update_sql = "UPDATE pickup_requests 
                          SET pickup_type = ?, area_id = ?, pickup_time = ? 
                          WHERE request_id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sissi", $pickup_type, $area_id, $pickup_time, $request_id, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Request updated successfully!";
                header("Location: cancelrequests.php");
                exit();
            } else {
                $error_message = "Error updating request: " . $stmt->error;
            }
        } else {
            // Find company responsible for the selected area
            $company_sql = "SELECT company_id FROM company_areas WHERE area_id = ? LIMIT 1";
            $company_stmt = $conn->prepare($company_sql);
            $company_stmt->bind_param("i", $area_id);
            $company_stmt->execute();
            $company_result = $company_stmt->get_result();

            if ($company_row = $company_result->fetch_assoc()) {
                $company_id = $company_row['company_id'];

                // Insert new request
                $sql = "INSERT INTO pickup_requests (user_id, pickup_type, area_id, weight, pickup_time, status, company_id)
                        VALUES (?, ?, ?, ?, ?, 'pending', ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isissi", $user_id, $pickup_type, $area_id, $weight, $pickup_time, $company_id);
                
                if ($stmt->execute()) {
                    $success_message = "Pickup request submitted successfully!";
                    header("Location: userdashboard.php");
                    exit();
                } else {
                    $error_message = "Error submitting pickup request: " . $stmt->error;
                }
            } else {
                $error_message = "No company is assigned to the selected area.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $edit_mode ? 'Edit' : 'New' ?> Pickup Request</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4"><?= $edit_mode ? 'Edit' : 'New' ?> Pickup Request</h2>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- Waste Type -->
        <div class="mb-3">
            <label for="pickup_type" class="form-label">Type of Waste</label>
            <select class="form-control select2" id="pickup_type" name="pickup_type" required>
                <option value="">--Select Waste Type--</option>
                <option value="Plastics" <?= ($edit_mode && $request_data['pickup_type'] == 'Plastics') ? 'selected' : '' ?>>Plastics</option>
                <option value="Organic" <?= ($edit_mode && $request_data['pickup_type'] == 'Organic') ? 'selected' : '' ?>>Organic</option>
                <option value="Glass" <?= ($edit_mode && $request_data['pickup_type'] == 'Glass') ? 'selected' : '' ?>>Glass</option>
                <option value="Metals" <?= ($edit_mode && $request_data['pickup_type'] == 'Metals') ? 'selected' : '' ?>>Metals</option>
                <option value="Paper" <?= ($edit_mode && $request_data['pickup_type'] == 'Paper') ? 'selected' : '' ?>>Paper</option>
                <option value="Textiles" <?= ($edit_mode && $request_data['pickup_type'] == 'Textiles') ? 'selected' : '' ?>>Textiles</option>
                <option value="Electronics" <?= ($edit_mode && $request_data['pickup_type'] == 'Electronics') ? 'selected' : '' ?>>Electronics</option>
                <option value="Batteries" <?= ($edit_mode && $request_data['pickup_type'] == 'Batteries') ? 'selected' : '' ?>>Batteries</option>
                <option value="Chemicals" <?= ($edit_mode && $request_data['pickup_type'] == 'Chemicals') ? 'selected' : '' ?>>Chemicals</option>
                <option value="Rubber" <?= ($edit_mode && $request_data['pickup_type'] == 'Rubber') ? 'selected' : '' ?>>Rubber</option>
                <option value="Wood" <?= ($edit_mode && $request_data['pickup_type'] == 'Wood') ? 'selected' : '' ?>>Wood</option>
                <option value="Food Waste" <?= ($edit_mode && $request_data['pickup_type'] == 'Food Waste') ? 'selected' : '' ?>>Food Waste</option>
                <option value="Garden Waste" <?= ($edit_mode && $request_data['pickup_type'] == 'Garden Waste') ? 'selected' : '' ?>>Garden Waste</option>
                <option value="Medical Waste" <?= ($edit_mode && $request_data['pickup_type'] == 'Medical Waste') ? 'selected' : '' ?>>Medical Waste</option>
                <option value="Construction Debris" <?= ($edit_mode && $request_data['pickup_type'] == 'Construction Debris') ? 'selected' : '' ?>>Construction Debris</option>
                <option value="Oil and Grease" <?= ($edit_mode && $request_data['pickup_type'] == 'Oil and Grease') ? 'selected' : '' ?>>Oil and Grease</option>
                <option value="Expired Goods" <?= ($edit_mode && $request_data['pickup_type'] == 'Expired Goods') ? 'selected' : '' ?>>Expired Goods</option>
                <option value="Others" <?= ($edit_mode && $request_data['pickup_type'] == 'Others') ? 'selected' : '' ?>>Others</option>
            </select>
        </div>

        <!-- Area Selection -->
        <div class="mb-3">
            <label for="area_id" class="form-label">Select Your Area</label>
            <select class="form-control select2" id="area_id" name="area_id" required>
                <option value="">--Select Area--</option>
                <option value="1" <?= ($edit_mode && $request_data['area_id'] == 1) ? 'selected' : '' ?>>Kamukuzi</option>
                <option value="2" <?= ($edit_mode && $request_data['area_id'] == 2) ? 'selected' : '' ?>>Kakira</option>
                <option value="3" <?= ($edit_mode && $request_data['area_id'] == 3) ? 'selected' : '' ?>>Mbarara Hill</option>
                <option value="4" <?= ($edit_mode && $request_data['area_id'] == 4) ? 'selected' : '' ?>>Bujaja</option>
                <option value="5" <?= ($edit_mode && $request_data['area_id'] == 5) ? 'selected' : '' ?>>Ruharo</option>
                <option value="6" <?= ($edit_mode && $request_data['area_id'] == 6) ? 'selected' : '' ?>>Rukungiri</option>
                <option value="7" <?= ($edit_mode && $request_data['area_id'] == 7) ? 'selected' : '' ?>>Kakoba</option>
                <option value="8" <?= ($edit_mode && $request_data['area_id'] == 8) ? 'selected' : '' ?>>Kakiika</option>
                <option value="9" <?= ($edit_mode && $request_data['area_id'] == 9) ? 'selected' : '' ?>>Nyakayojo</option>
                <option value="10" <?= ($edit_mode && $request_data['area_id'] == 10) ? 'selected' : '' ?>>Nyamitanga</option>
                <option value="11" <?= ($edit_mode && $request_data['area_id'] == 11) ? 'selected' : '' ?>>Buziba</option>
                <option value="12" <?= ($edit_mode && $request_data['area_id'] == 12) ? 'selected' : '' ?>>Rubindi</option>
                <option value="13" <?= ($edit_mode && $request_data['area_id'] == 13) ? 'selected' : '' ?>>Katojo</option>
                <option value="14" <?= ($edit_mode && $request_data['area_id'] == 14) ? 'selected' : '' ?>>Kijungu</option>
                <option value="15" <?= ($edit_mode && $request_data['area_id'] == 15) ? 'selected' : '' ?>>Rwetuha</option>
                <option value="16" <?= ($edit_mode && $request_data['area_id'] == 16) ? 'selected' : '' ?>>Rujumbura</option>
                <option value="17" <?= ($edit_mode && $request_data['area_id'] == 17) ? 'selected' : '' ?>>Kyenjojo</option>
                <option value="18" <?= ($edit_mode && $request_data['area_id'] == 18) ? 'selected' : '' ?>>Ruburizi</option>
            </select>
        </div>

        <!-- Pickup Time -->
        <div class="mb-3">
            <label for="pickup_time" class="form-label">Preferred Pickup Time</label>
            <input type="time" class="form-control" id="pickup_time" name="pickup_time" 
                   value="<?= $edit_mode ? date('H:i', strtotime($request_data['pickup_time'])) : '' ?>" required>
            <small class="text-muted">Select your preferred time for waste pickup</small>
        </div>

        <!-- Submit Button -->
        <div class="d-grid">
            <button type="submit" class="btn btn-primary"><?= $edit_mode ? 'Update' : 'Submit' ?> Request</button>
        </div>
    </form>

    <a href="<?= $edit_mode ? 'cancelrequests.php' : 'userdashboard.php' ?>" class="btn btn-secondary mt-4">Back to <?= $edit_mode ? 'Requests' : 'Dashboard' ?></a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 for all dropdowns
        $('.select2').select2({
            placeholder: "--Select an Option--",
            allowClear: true
        });
    });
</script>
</body>
</html>
