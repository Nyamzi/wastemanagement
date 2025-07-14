<?php
session_start(); // Start the session

// Check if the user is logged in and if their user_type is 'admin'
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    // If not logged in or not an admin, redirect to login page
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

// Fetch stats for dashboard
$sql_stats = "SELECT 
    (SELECT COUNT(*) FROM users WHERE user_type = 'company') as total_companies,
    (SELECT COUNT(*) FROM users WHERE user_type = 'municipal_council') as total_municipals,
    (SELECT COUNT(*) FROM pickup_requests WHERE status = 'pending') as pending_pickups,
    (SELECT COUNT(*) FROM areas) as total_areas";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

// Fetch unverified companies
$sql_companies = "SELECT * FROM users WHERE user_type = 'company' AND is_verified = 0";
$result_companies = $conn->query($sql_companies);

// Fetch unverified municipal councils
$sql_municipals = "SELECT * FROM users WHERE user_type = 'municipal_council' AND is_verified = 0";
$result_municipals = $conn->query($sql_municipals);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Waste Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"> <!-- Font Awesome CDN -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Roboto', sans-serif;
        }
        .navbar {
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
            position: fixed;
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
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            flex: 1;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stats-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .verify-btn {
            background-color: #28a745;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 5px;
        }
        .verify-btn:hover {
            background-color: #218838;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
            <h3 class="mb-4">Admin Panel</h3>
            <a href="admindashboard.php" class="active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="managecompanies.php">
                <i class="fas fa-building"></i> Manage Companies
            </a>
            <a href="manage_municipals.php">
                <i class="fas fa-city"></i> Manage Municipal Councils
            </a>
            <a href="reports.php">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a href="predict_request.php">
                <i class="fas fa-chart-line"></i> Quick Predictions
            </a>
            <a href="future_predictions.php">
                <i class="fas fa-calendar-alt"></i> Future Predictions
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
    </div>

        <!-- Main Content -->
        <div class="main-content">
            <h2 class="mb-4">Dashboard Overview</h2>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon text-primary">
                            <i class="fas fa-building"></i>
                        </div>
                        <h4><?php echo $stats['total_companies']; ?></h4>
                        <p>Total Companies</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon text-success">
                            <i class="fas fa-city"></i>
                        </div>
                        <h4><?php echo $stats['total_municipals']; ?></h4>
                        <p>Municipal Councils</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon text-warning">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h4><?php echo $stats['pending_pickups']; ?></h4>
                        <p>Pending Pickups</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-icon text-info">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4><?php echo $stats['total_areas']; ?></h4>
                        <p>Service Areas</p>
                    </div>
                </div>
            </div>

            <!-- Waste Prediction Card -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Waste Prediction System</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="stats-icon text-primary me-3">
                                    <i class="fas fa-chart-line fa-2x"></i>
                                </div>
                                <div>
                                    <h4>AI-Powered Predictions</h4>
                                    <p>Get accurate waste collection predictions</p>
                                </div>
                            </div>
                            <a href="predict_request.php" class="btn btn-primary w-100">View Predictions</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Verifications -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Pending Company Verifications</h5>
                        </div>
                        <div class="card-body">
                    <?php if ($result_companies->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Email</th>
                                                <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                            <?php while($company = $result_companies->fetch_assoc()): ?>
                                    <tr>
                                                    <td><?php echo htmlspecialchars($company['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($company['email']); ?></td>
                                        <td>
                                                        <a href="managecompanies.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-primary">Verify</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                                </div>
                    <?php else: ?>
                                <p>No pending company verifications.</p>
                    <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Pending Municipal Council Verifications</h5>
            </div>
                        <div class="card-body">
                    <?php if ($result_municipals->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                            <thead>
                                <tr>
                                    <th>Council Name</th>
                                    <th>Email</th>
                                                <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                            <?php while($municipal = $result_municipals->fetch_assoc()): ?>
                                    <tr>
                                                    <td><?php echo htmlspecialchars($municipal['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($municipal['email']); ?></td>
                                        <td>
                                                        <a href="manage_municipals.php?id=<?php echo $municipal['id']; ?>" class="btn btn-sm btn-primary">Verify</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                                </div>
                    <?php else: ?>
                                <p>No pending municipal council verifications.</p>
                    <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection at the very end
$conn->close();
?>

