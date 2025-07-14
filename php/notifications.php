<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add viewed column if it doesn't exist
$check_column = "SHOW COLUMNS FROM pickup_requests LIKE 'viewed'";
$result = $conn->query($check_column);
if ($result->num_rows == 0) {
    $add_column = "ALTER TABLE pickup_requests ADD COLUMN viewed TINYINT(1) DEFAULT 0";
    if (!$conn->query($add_column)) {
        die("Error adding viewed column: " . $conn->error);
    }
}

// Fetch completed pickup requests for the logged-in user
$user_id = $_SESSION['user_id'];
$sql = "SELECT pr.*, a.area_name, c.company_name 
        FROM pickup_requests pr 
        JOIN areas a ON pr.area_id = a.area_id 
        JOIN companies c ON pr.company_id = c.company_id 
        WHERE pr.user_id = ? 
        AND pr.status = 'completed' 
        ORDER BY pr.created_at DESC 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .notification-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 15px;
            transition: transform 0.2s, background-color 0.3s;
        }
        .notification-card:hover {
            transform: translateY(-2px);
        }
        .notification-card.unviewed {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        .notification-header {
            border-radius: 10px 10px 0 0;
            padding: 10px 15px;
        }
        .notification-header.unviewed {
            background-color: #bbdefb;
        }
        .notification-header.viewed {
            background-color: #e8f5e9;
        }
        .notification-body {
            padding: 15px;
        }
        .notification-item {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        .notification-label {
            font-weight: 600;
            color: #495057;
        }
        .notification-value {
            color: #212529;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .unviewed-badge {
            background-color: #2196f3;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Notifications</h2>
                </div>

                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?= $notification['viewed'] ? 'viewed' : 'unviewed' ?>">
                            <div class="notification-header <?= $notification['viewed'] ? 'viewed' : 'unviewed' ?>">
                                <h5 class="mb-0">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    Pickup Completed
                                    <?php if (!$notification['viewed']): ?>
                                        <span class="unviewed-badge">New</span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                            <div class="notification-body">
                                <div class="notification-item">
                                    <span class="notification-label">Area:</span>
                                    <span class="notification-value"><?= htmlspecialchars($notification['area_name']) ?></span>
                                </div>
                                <div class="notification-item">
                                    <span class="notification-label">Company:</span>
                                    <span class="notification-value"><?= htmlspecialchars($notification['company_name']) ?></span>
                                </div>
                                <div class="notification-item">
                                    <span class="notification-label">Waste Type:</span>
                                    <span class="notification-value"><?= htmlspecialchars($notification['pickup_type']) ?></span>
                                </div>
                                <div class="notification-item">
                                    <span class="notification-label">Weight:</span>
                                    <span class="notification-value"><?= number_format($notification['weight'], 2) ?> kg</span>
                                </div>
                                <div class="notification-item">
                                    <span class="notification-label">Fee:</span>
                                    <span class="notification-value">UGX <?= number_format($notification['fee']) ?></span>
                                </div>
                                <div class="notification-item">
                                    <span class="notification-label">Date:</span>
                                    <span class="notification-value"><?= date('M d, Y', strtotime($notification['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-bell-slash"></i>
                        <h4>No Notifications</h4>
                        <p>You don't have any completed pickup notifications yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to mark notifications as viewed
        function markNotificationsAsViewed() {
            fetch('mark_notifications_viewed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=<?= $user_id ?>'
            });
        }

        // Mark notifications as viewed when user leaves the page
        window.addEventListener('beforeunload', markNotificationsAsViewed);

        // Also mark as viewed when clicking on links
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', markNotificationsAsViewed);
        });
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
