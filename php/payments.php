<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch companies
$company_query = $conn->query("SELECT company_id, company_name FROM companies");
$companies = [];
while ($row = $company_query->fetch_assoc()) {
    $companies[] = $row;
}

// Handle form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $plan = $_POST['plan'];
    $notes = $_POST['notes'];
    $payment_method = $_POST['payment_method'];
    $company_id = $_POST['company_id'];

    $amount_map = [
        'monthly' => 30000,
        'quarterly' => 75000,
        'yearly' => 220000
    ];

    if (!isset($amount_map[$plan])) {
        $error_message = "Invalid plan selected.";
    } elseif (empty($company_id)) {
        $error_message = "Please select a company.";
    } else {
        $amount = $amount_map[$plan];
        $transaction_id = uniqid('txn_');

        $stmt = $conn->prepare("INSERT INTO payments (user_id, company_id, amount, plan, payment_method, transaction_id, notes) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidssss", $user_id, $company_id, $amount, $plan, $payment_method, $transaction_id, $notes);

        if ($stmt->execute()) {
            $success_message = "Payment submitted! Awaiting approval.";
        } else {
            $error_message = "Payment failed: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Make a Payment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .custom-dropdown select {
            appearance: none;
            background: white url('data:image/svg+xml;utf8,<svg fill="black" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 10px center;
            background-size: 15px;
            padding-right: 35px;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card p-4 shadow">
        <h2>Make a Payment</h2>

        <?php if (!empty($success_message)) echo "<div class='alert alert-success'>$success_message</div>"; ?>
        <?php if (!empty($error_message)) echo "<div class='alert alert-danger'>$error_message</div>"; ?>

        <form method="POST" action="">
            <div class="mb-3 custom-dropdown">
                <label class="form-label">Select Plan</label>
                <select name="plan" class="form-control" required>
                    <option value="">-- Select Plan --</option>
                    <option value="monthly">Daily - 10,000</option>
                    <option value="monthly">Monthly - 30,000</option>
                    <option value="quarterly">Quarterly - 75,000</option>
                    <option value="yearly">Yearly - 220,000</option>
                </select>
            </div>

            <div class="mb-3 custom-dropdown">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-control" required>
                    <option value="">-- Select Method --</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Mobile Money">Mobile Money</option>
                </select>
            </div>

            <div class="mb-3 custom-dropdown">
                <label class="form-label">Select Company</label>
                <select name="company_id" class="form-control" required>
                    <option value="">-- Select Company --</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= $company['company_id'] ?>">
                            <?= htmlspecialchars($company['company_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes (optional)</label>
                <textarea name="notes" class="form-control" placeholder="Add any notes..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Submit Payment</button>
            <a href="userdashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
        </form>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2 for the dropdowns
        $('#address').select2({
            placeholder: "Select Your Area"
        });
    });
</script>
</body>
</html>
