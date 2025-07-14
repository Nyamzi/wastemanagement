<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastemanagement";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$success_message = '';
$error_message = '';
$email_error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($password) || empty($user_type)) {
        $error_message = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $email_error = "Email is already registered.";
        } else {
            // Handle profile picture upload
            $profile_picture = null;
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $profile_picture = basename($_FILES['profile_picture']['name']);
                $upload_file = $upload_dir . $profile_picture;
                
                if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_file)) {
                    $error_message = "Error uploading profile picture.";
                }
            }
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Set verification status
            $is_verified = ($user_type === 'user') ? 1 : 0;
            
            // Get role-specific data
            $company_name = null;
            $phone_number = null;
            $area_name = null;
            $council_name = null;
            $address = null;
            
            if ($user_type === 'user') {
                $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
                $area_name = isset($_POST['area_name']) ? trim($_POST['area_name']) : null;
            } elseif ($user_type === 'company') {
                $company_name = isset($_POST['company_name']) ? trim($_POST['company_name']) : null;
                $address = isset($_POST['address']) ? trim($_POST['address']) : null;
            } elseif ($user_type === 'municipal_council') {
                $council_name = isset($_POST['council_name']) ? trim($_POST['council_name']) : null;
            }
            
            // Insert into users table
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, user_type, is_verified, profile_picture, 
                                  company_name, phone_number, area_name, council_name, address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt) {
                $stmt->bind_param("ssssissssss", $name, $email, $password_hash, $user_type, $is_verified,
                                $profile_picture, $company_name, $phone_number, $area_name, $council_name, $address);
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Create company record if needed
                    if ($user_type === 'company' && $company_name) {
                        $company_stmt = $conn->prepare("
                            INSERT INTO companies (user_id, company_name, address) 
                            VALUES (?, ?, ?)
                        ");
                        if ($company_stmt) {
                            $company_stmt->bind_param("iss", $user_id, $company_name, $address);
                            $company_stmt->execute();
                            $company_stmt->close();
                        }
                    }
                    
                    // Create municipal council record if needed
                    if ($user_type === 'municipal_council' && $council_name) {
                        $council_stmt = $conn->prepare("
                            INSERT INTO municipal_councils (user_id, council_name) 
                            VALUES (?, ?)
                        ");
                        if ($council_stmt) {
                            $council_stmt->bind_param("is", $user_id, $council_name);
                            $council_stmt->execute();
                            $council_stmt->close();
                        }
                    }
                    
                    $success_message = "Registration successful!";
                    header("Location: login.php");
                    exit();
                } else {
                    $error_message = "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Error preparing statement: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .registration-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .role-specific {
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="registration-container">
    <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>
    
    <h2 class="text-center mb-4">Create an Account</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control <?= $email_error ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            <?php if ($email_error): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($email_error) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>

        <div class="mb-3">
            <label for="user_type" class="form-label">Register as</label>
            <select class="form-select" id="user_type" name="user_type" onchange="toggleRoleFields()" required>
                <option value="">Select Role</option>
                <option value="user" <?= isset($_POST['user_type']) && $_POST['user_type'] === 'user' ? 'selected' : '' ?>>Resident</option>
                <option value="company" <?= isset($_POST['user_type']) && $_POST['user_type'] === 'company' ? 'selected' : '' ?>>Company</option>
                <option value="municipal_council" <?= isset($_POST['user_type']) && $_POST['user_type'] === 'municipal_council' ? 'selected' : '' ?>>Municipal Council</option>
            </select>
        </div>

        <!-- User Fields -->
        <div id="user_fields" class="role-specific" style="display:none;">
            <div class="mb-3">
                <label for="phone_number" class="form-label">Phone Number</label>
                <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?= isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : '' ?>">
            </div>
            <div class="mb-3">
                <label for="area_name" class="form-label">Select Area (for waste pickup)</label>
                <select class="form-control" id="area_name" name="area_name" required>
                    <option value="" disabled selected>Select an area</option>
                    <option value="Kamukuzi">Kamukuzi</option>
                    <option value="Kakira">Kakira</option>
                    <option value="Mbarara Hill">Mbarara Hill</option>
                    <option value="Bujaja">Bujaja</option>
                    <option value="Ruharo">Ruharo</option>
                    <option value="Rukungiri">Rukungiri</option>
                    <option value="Kakoba">Kakoba</option>
                    <option value="Kakiika">Kakiika</option>
                    <option value="Nyakayojo">Nyakayojo</option>
                    <option value="Nyamitanga">Nyamitanga</option>
                    <option value="Buziba">Buziba</option>
                    <option value="Rubindi">Rubindi</option>
                    <option value="Katojo">Katojo</option>
                    <option value="Kijungu">Kijungu</option>
                    <option value="Rwentuha">Rwentuha</option>
                    <option value="Rujumbura">Rujumbura</option>
                    <option value="Kyenjojo">Kyenjojo</option>
                </select>
            </div>
        </div>

        <!-- Company Fields -->
        <div id="company_fields" class="role-specific" style="display:none;">
            <div class="mb-3">
                <label for="company_name" class="form-label">Company Name</label>
                <input type="text" class="form-control" id="company_name" name="company_name" value="<?= isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : '' ?>" required>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Company Address</label>
                <textarea class="form-control" id="address" name="address" required><?= isset($_POST['address']) ? htmlspecialchars($_POST['address']) : '' ?></textarea>
            </div>
        </div>

        <!-- Council Fields -->
        <div id="council_fields" class="role-specific" style="display:none;">
            <div class="mb-3">
                <label for="council_name" class="form-label">Council Name</label>
                <input type="text" class="form-control" id="council_name" name="council_name" value="<?= isset($_POST['council_name']) ? htmlspecialchars($_POST['council_name']) : '' ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="profile_picture" class="form-label">Profile Picture</label>
            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
        </div>

        <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
</div>

<script>
function toggleRoleFields() {
    const userType = document.getElementById('user_type').value;
    document.getElementById('user_fields').style.display = (userType === 'user') ? 'block' : 'none';
    document.getElementById('company_fields').style.display = (userType === 'company') ? 'block' : 'none';
    document.getElementById('council_fields').style.display = (userType === 'municipal_council') ? 'block' : 'none';
}

// Initialize fields visibility on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleRoleFields();
});
</script>
</body>
</html>
