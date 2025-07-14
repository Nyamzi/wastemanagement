<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    http_response_code(500);
    exit();
}

// Get user ID from POST data
$user_id = $_POST['user_id'] ?? $_SESSION['user_id'];

// Mark notifications as viewed
$update_viewed = $conn->prepare("UPDATE pickup_requests SET viewed = 1 WHERE user_id = ? AND status = 'completed' AND viewed = 0");
$update_viewed->bind_param("i", $user_id);
$update_viewed->execute();

$update_viewed->close();
$conn->close();
?> 