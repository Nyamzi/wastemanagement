<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch reward points for the logged-in user
$reward_stmt = $conn->prepare("SELECT points FROM user_rewards WHERE user_id = ?");
$reward_stmt->bind_param("i", $_SESSION['user_id']);
$reward_stmt->execute();
$reward_result = $reward_stmt->get_result();
$reward_points = $reward_result->fetch_assoc()['points'] ?? 0;

$reward_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Your Rewards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .rewards-card {
            margin-top: 80px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        .rewards-card h2 {
            margin-bottom: 20px;
        }
        .reward-number {
            font-size: 48px;
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="rewards-card">
        <h2>Your Reward Points</h2>
        <div class="reward-number"><?= $reward_points ?> pts</div>
        <p>Keep requesting pickups and earn more points!</p>
        <a href="userdashboard.php" class="btn btn-primary mt-4">Back to Dashboard</a>
    </div>
</div>
</body>
</html>
