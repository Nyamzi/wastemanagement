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

// Fetch count of unviewed completed pickup requests
$notif_query = $conn->prepare("SELECT COUNT(*) as count FROM pickup_requests WHERE user_id = ? AND status = 'completed' AND viewed = 0");
$notif_query->bind_param("i", $user_id);
$notif_query->execute();
$notif_result = $notif_query->get_result();
$new_notifications = $notif_result->fetch_assoc()['count'] ?? 0;
$notif_query->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            background-image: url('background.jpg');
            background-size: cover;
            background-position: center center;
            font-family: 'Roboto', sans-serif;
        }
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #343a40;
            padding: 10px;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background-color: #343a40;
            color: white;
            padding: 20px;
            height: 100vh;
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px;
            display: block;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .sidebar a:hover {
            background-color: #007bff;
            transition: all 0.3s ease;
        }
        .content {
            flex: 1;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-left: 20px;
        }
        .feature-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .feature-card {
            background: linear-gradient(135deg, #007bff, #6c757d);
            color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease-in-out;
        }
        .feature-card h4 {
            margin: 0;
            font-size: 1.2rem;
        }
        .feature-card p {
            margin: 5px 0 0;
        }
        .profile-img {
            border-radius: 50%;
            width: 100px;
            height: 100px;
            object-fit: cover;
            transition: all 0.3s ease;
        }
        .profile-img:hover {
            transform: scale(1.1);
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px;
        }
        .navbar a:hover {
            background-color: #007bff;
            transition: all 0.3s ease;
        }
        .sidebar a:hover {
            background-color: #007bff;
            color: white;
        }
        #loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 5px 8px;
            font-size: 12px;
            font-weight: bold;
        }
        .notification-icon {
            position: relative;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <div class="sidebar">
        <h3 class="text-center">Dashboard</h3>

        <!-- Display profile picture and name in sidebar -->
        <div class="text-center mb-4">
            <img src="<?php echo $user['profile_picture'] ? 'uploads/' . $user['profile_picture'] : 'default-profile.jpg'; ?>" 
                 alt="Profile Picture" class="profile-img">
            <h5 class="mt-2"><?php echo htmlspecialchars($user['name']); ?></h5>
        </div>

        <!-- Navigation Links with Icons -->
        <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="updateprofile.php"><i class="fas fa-user-edit"></i> Update Profile</a>
        <a href="payment.php"><i class="fas fa-credit-card"></i>Payment History</a>
        <a href="pickup.php"><i class="fas fa-truck"></i> Request Pickup</a>
        <a href="pastrequests.php"><i class="fas fa-history"></i> View Past Requests</a>
        <?php if ($user['user_type'] == 'admin'): ?>
            <a href="admin_panel.php"><i class="fas fa-cogs"></i> Admin Panel</a>
        <?php endif; ?>
        <hr>
        <a href="logout.php" class="btn btn-danger w-100">Logout</a>
    </div>

    <!-- Content -->
    <div class="content">
        <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
        <p>This is your personalized dashboard. Use the links on the left to navigate through the system.</p>
        <hr>

        <!-- Feature Cards -->
        <div class="row g-4">
            <!-- Notifications Card -->
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow-sm">
                    <div class="card-body text-center">
                        <div class="notification-icon">
                            <i class="fas fa-bell fa-3x mb-3"></i>
                            <?php if ($new_notifications > 0): ?>
                                <span class="notification-badge"><?= $new_notifications ?></span>
                            <?php endif; ?>
                        </div>
                        <h5 class="card-title">Notifications</h5>
                        <p class="card-text">You have <?= $new_notifications ?> new completed pickup<?= $new_notifications != 1 ? 's' : '' ?>.</p>
                        <a href="notifications.php" class="btn btn-light text-primary">View Notifications</a>
                    </div>
                </div>
            </div>

            <!-- Pending Pickups Card -->
            <div class="col-md-4">
                <div class="card bg-success text-white shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-truck fa-3x mb-3"></i>
                        <h5 class="card-title">Upcoming Pickups</h5>
                        <p class="card-text">You can view scheduled upcoming pickups.</p>
                        <a href="cancelrequests.php" class="btn btn-light text-success">View Pickups</a>
                    </div>
                </div>
            </div>

            <!-- Payment Status Card -->
            <div class="col-md-4">
                <div class="card bg-warning text-dark shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-wallet fa-3x mb-3"></i>
                        <h5 class="card-title">Assigned Company</h5>
                        <p class="card-text">View the company you have been assigned.</p>
                        <a href="assignedcompany.php" class="btn btn-dark">View Company</a>
                    </div>
                </div>
            </div>

            <!-- Points and Discounts Card -->
            <div class="col-md-4">
                <div class="card bg-info text-white shadow-sm">
                    <div class="card-body text-center">
                        <i class="fas fa-gift fa-3x mb-3"></i>
                        <h5 class="card-title">Earn Points, Get Discounts</h5>
                        <p class="card-text"> Redeem them for discounts on your next service.</p>
                        <a href="rewards.php" class="btn btn-light text-info">View Rewards</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS (for tooltips and other interactivity) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Enable tooltips for all links and buttons
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>

</body>
</html>
