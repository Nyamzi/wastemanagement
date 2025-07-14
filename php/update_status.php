<?php
session_start();

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'], $_GET['action'])) {
    $request_id = intval($_GET['id']);
    $action = $_GET['action'];

    // DB connection
    $conn = new mysqli("localhost", "root", "", "wastemanagement");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if ($action === 'accept') {
        // Update status to accepted
        $stmt = $conn->prepare("UPDATE pickup_requests SET status = 'accepted' WHERE request_id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_pickups.php");
    } elseif ($action === 'complete') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['weight'])) {
            $weight = floatval($_POST['weight']);
            
            // Calculate fee based on weight
            $fee = 0;
            if ($weight >= 1 && $weight <= 5) {
                $fee = 1000;
            } else if ($weight >= 6 && $weight <= 15) {
                $fee = 2000;
            } else if ($weight >= 16 && $weight <= 30) {
                $fee = 3000;
            } else if ($weight >= 31 && $weight <= 50) {
                $fee = 10000;
            } else if ($weight > 50) {
                $fee = 20000;
            }
            
            // First, check if fee column exists
            $check_column = $conn->query("SHOW COLUMNS FROM pickup_requests LIKE 'fee'");
            if ($check_column->num_rows == 0) {
                // Add fee column if it doesn't exist
                $conn->query("ALTER TABLE pickup_requests ADD COLUMN fee INT DEFAULT 0");
            }
            
            // Update the request with weight and fee
            $stmt = $conn->prepare("UPDATE pickup_requests SET status = 'completed', weight = ?, fee = ? WHERE request_id = ?");
            $stmt->bind_param("dii", $weight, $fee, $request_id);
            $stmt->execute();
            $stmt->close();
            
            // Show success message with weight and fee details
            echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Success</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
                <meta http-equiv="refresh" content="3;url=manage_pickups.php">
            </head>
            <body>
                <div class="container mt-5">
                    <div class="alert alert-success" role="alert">
                        <h4 class="alert-heading">Success!</h4>
                        <p>Pickup request has been completed successfully.</p>
                        <hr>
                        <div class="mb-2">
                            <strong>Weight:</strong> ' . number_format($weight, 2) . ' kg<br>
                            <strong>Fee:</strong> UGX ' . number_format($fee) . '
                        </div>
                        <p class="mb-0">Redirecting back to manage pickups...</p>
                    </div>
                </div>
            </body>
            </html>';
            exit();
        } else {
            echo '<!DOCTYPE html>
            <html>
            <head>
                <title>Complete Pickup</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    .fee-display {
                        font-size: 1.2em;
                        font-weight: bold;
                        color: #28a745;
                        margin-top: 10px;
                    }
                </style>
            </head>
            <body>
                <div class="container mt-5">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Enter Weight</h5>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="weight" class="form-label">Weight (kg)</label>
                                    <input type="number" step="0.01" class="form-control" name="weight" id="weight" required oninput="calculateFee()">
                                    <div class="fee-display" id="feeDisplay">Fee: UGX 0</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit</button>
                                <a href="manage_pickups.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                function calculateFee() {
                    const weight = parseFloat(document.getElementById("weight").value) || 0;
                    let fee = 0;
                    
                    if (weight >= 1 && weight <= 5) {
                        fee = 1000;
                    } else if (weight >= 6 && weight <= 15) {
                        fee = 2000;
                    } else if (weight >= 16 && weight <= 30) {
                        fee = 3000;
                    } else if (weight >= 31 && weight <= 50) {
                        fee = 10000;
                    } else if (weight > 50) {
                        fee = 20000;
                    }
                    
                    document.getElementById("feeDisplay").textContent = "Fee: UGX " + fee.toLocaleString();
                }
                </script>
            </body>
            </html>';
        }
    } elseif ($action === 'reject') {
        // Update status to rejected
        $stmt = $conn->prepare("UPDATE pickup_requests SET status = 'rejected' WHERE request_id = ?");
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $stmt->close();
        header("Location: manage_pickups.php");
    }

    $conn->close();
}
?>
