<?php
session_start();

// Check if the user is logged in, if not redirect to login page
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

// Get user data from the session
$user_id = $_SESSION['user_id'];

// Query to fetch user details based on the session user ID
$sql = "SELECT * FROM users WHERE user_id = $user_id";
$result = $conn->query($sql);

// Fetch user data
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("User not found.");
}

// Fetch company details if assigned
$company_details = null;
if ($user['user_type'] == 'user' && isset($user['company_id'])) {
    $company_id = $user['company_id'];
    $company_sql = "SELECT * FROM companies WHERE company_id = $company_id";
    $company_result = $conn->query($company_sql);

    if ($company_result->num_rows > 0) {
        $company_details = $company_result->fetch_assoc();
    } else {
        die("Company not found.");
    }
} else {
    die("No company assigned to this user.");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Company Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .navbar {
            background-color: #343a40;
            padding: 10px;
        }
        .company-card {
            background: #007bff;
            color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }
        .company-card h3 {
            margin-bottom: 20px;
        }
        .company-card p {
            font-size: 1.1rem;
        }
        .btn-back {
            margin-top: 20px;
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn-back:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>

<div class="container mt-5">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="dashboard.php">Waste Management</a>
    </nav>

    <div class="company-card">
        <h3>Assigned Company: <?php echo htmlspecialchars($company_details['company_name']); ?></h3>
        <p><strong>Company Address:</strong> <?php echo htmlspecialchars($company_details['address']); ?></p>
        <p><strong>Contact:</strong> <?php echo htmlspecialchars($company_details['phone']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($company_details['email']); ?></p>
        
    </div>

    <a href="userdashboard.php" class="btn-back">Back to Dashboard</a>  
</div>


<!-- Bootstrap JS (for tooltips and other interactivity) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
