<?php
session_start();

// DB connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastemanagement";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle verification
if (isset($_GET['verify_id'])) {
    $council_id = $_GET['verify_id'];

    $sql = "UPDATE users SET is_verified = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $council_id);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_municipals.php");
    exit();
}

// Get all municipal councils
$sql = "SELECT * FROM users WHERE user_type = 'municipal_council'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Municipal Councils</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { margin-top: 50px; }
        .btn-verify { background-color: #007bff; color: white; }
        .btn-verify:hover { background-color: #0056b3; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-4">Manage Municipal Councils</h2>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Council Name</th>
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
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <?php if ($row['is_verified'] == 1): ?>
                            <span class="badge bg-success">Verified</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Not Verified</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['is_verified'] == 0): ?>
                            <a href="manage_municipals.php?verify_id=<?php echo $row['user_id']; ?>" class="btn btn-verify btn-sm">Verify</a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled>Verified</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" class="text-center">No municipal councils found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="text-center">
        <a href="admindashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
