<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastemanagement";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT a.area_name, pr.pickup_type, pr.created_at, pr.status, pr.weight
    FROM pickup_requests pr
    INNER JOIN areas a ON pr.area_id = a.area_id
    WHERE pr.user_id = ? AND pr.status = 'completed'
    ORDER BY pr.created_at DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$past_requests = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $past_requests[] = $row;
    }
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Past Completed Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .content {
            margin-top: 50px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .no-requests {
            color: #777;
            text-align: center;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <h3 class="text-center mb-4">Your Completed Pickup Requests</h3>

        <div class="mb-4">
            <a href="generate_pdf.php" class="btn btn-primary">
                <i class="fas fa-download"></i> Download as PDF
            </a>
        </div>

        <?php if (empty($past_requests)): ?>
            <p class="no-requests">No completed pickup requests found.</p>
        <?php else: ?>
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Area</th>
                        <th>Pickup Type</th>
                        <th>Date Requested</th>
                        <th>Weight (kg)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($past_requests as $index => $request): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($request['area_name']) ?></td>
                            <td><?= htmlspecialchars($request['pickup_type']) ?></td>
                            <td><?= date("Y-m-d H:i", strtotime($request['created_at'])) ?></td>
                            <td><?= htmlspecialchars($request['weight']) ?> kg</td>
                            <td><span class="badge bg-success">Completed</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="userdashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Share via Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="send_email.php" method="POST">
                    <div class="mb-3">
                        <label for="recipientEmail" class="form-label">Recipient Email</label>
                        <input type="email" class="form-control" id="recipientEmail" name="recipientEmail" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message (optional)</label>
                        <textarea class="form-control" id="message" name="message" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function shareToWhatsApp() {
    const text = "Check out my waste pickup history from Waste Management System";
    const url = window.location.href;
    window.open(`https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`, '_blank');
}

function shareToFacebook() {
    const url = window.location.href;
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
}
</script>
</body>
</html>
