<?php
session_start();

// Check if the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header("Location: login.php");
    exit();
}

$company_user_id = $_SESSION['user_id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $company_name = $conn->real_escape_string($_POST["company_name"]);
    $phone = $conn->real_escape_string($_POST["phone"]);
    $email = $conn->real_escape_string($_POST["email"]);
    $address = $conn->real_escape_string($_POST["address"]);

    // Check if record already exists
    $check_sql = "SELECT * FROM companies WHERE user_id = $company_user_id";
    $check_result = $conn->query($check_sql);

    if ($check_result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE companies SET 
                        company_name = '$company_name', 
                        phone = '$phone', 
                        email = '$email', 
                        address = '$address' 
                       WHERE user_id = $company_user_id";
        $conn->query($update_sql);
        $message = "Company profile updated successfully!";
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO companies (user_id, company_name, phone, email, address) 
                       VALUES ($company_user_id, '$company_name', '$phone', '$email', '$address')";
        $conn->query($insert_sql);
        $message = "Company profile saved successfully!";
    }

    // Get the company_id just saved or updated
    $get_company_id = $conn->query("SELECT company_id FROM companies WHERE user_id = $company_user_id");
    if ($get_company_id->num_rows > 0) {
        $company_row = $get_company_id->fetch_assoc();
        $company_id = $company_row['company_id'];

        // Update users table with the company_id
        $conn->query("UPDATE users SET company_id = $company_id WHERE user_id = $company_user_id AND user_type = 'company'");
    }
}

// Fetch existing company data (if any)
$company_data = [
    "company_name" => "",
    "phone" => "",
    "email" => "",
    "address" => ""
];

$result = $conn->query("SELECT * FROM companies WHERE user_id = $company_user_id");
if ($result && $result->num_rows > 0) {
    $company_data = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Company Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Company Profile</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">Company Name</label>
            <input type="text" name="company_name" class="form-control" value="<?php echo $company_data['company_name']; ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo $company_data['phone']; ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo $company_data['email']; ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control"><?php echo $company_data['address']; ?></textarea>
        </div>
        
        <div class="d-flex justify-content-between">
            <button type="submit" class="btn btn-primary">Save Profile</button>
            <a href="companydashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </form>
</div>
</body>
</html>