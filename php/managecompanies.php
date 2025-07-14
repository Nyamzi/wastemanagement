<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastemanagement"; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle verification of company
if (isset($_GET['verify_id'])) {
    $company_id = $_GET['verify_id'];

    // Update company to verified
    $sql = "UPDATE users SET is_verified = 1 WHERE user_id = $company_id";
    if ($conn->query($sql) === TRUE) {
        echo "Company verified successfully.";
    } else {
        echo "Error verifying company: " . $conn->error;
    }
}

// Query to get all companies (verified and not verified)
$sql = "SELECT * FROM users WHERE user_type = 'company'"; // Only get companies
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Companies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            margin-top: 50px;
        }
        .btn-verify {
            background-color: #28a745;
            color: white;
        }
        .btn-verify:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-4">Manage Companies</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['user_id']; ?></td>
                        <td><?php echo $row['name']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td>
                            <?php 
                                if ($row['is_verified'] == 1) {
                                    echo '<span class="badge bg-success">Verified</span>';
                                } else {
                                    echo '<span class="badge bg-danger">Not Verified</span>';
                                }
                            ?>
                        </td>
                        <td>
                            <?php if ($row['is_verified'] == 0): ?>
                                <a href="managecompanies.php?verify_id=<?php echo $row['user_id']; ?>" class="btn btn-verify">Verify</a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Verified</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No companies found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
          <!-- Back to Dashboard Button -->
    </table>
    <div class="d-grid gap-2 col-6 mx-auto btn-back">
        <a href="admindashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

<!-- Bootstrap JS (for tooltips and other interactivity) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
// Close the database connection
$conn->close();