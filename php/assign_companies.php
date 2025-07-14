<?php
session_start();

// Check if the logged-in user is a municipal council
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'municipal_council') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$municipal_id = $_SESSION['user_id'];

// Fetch company assignments under this municipal
$sql = "
    SELECT a.area_name, c.company_name AS company_name
    FROM company_areas ca
    JOIN areas a ON ca.area_id = a.area_id
    JOIN companies c ON ca.company_id = c.company_id

";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Company Assignments to Areas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f8;
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Companies Assigned to Areas</h2>
        <?php if ($result->num_rows > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Area</th>
                        <th>Company</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['area_name']) ?></td>
                            <td><?= htmlspecialchars($row['company_name']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
             
            </table>
            <a href="municipaldashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
        <?php else: ?>
            <p>No company assignments have been made yet.</p>
        <?php endif; ?>
    </div>
   
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
