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

// Handle the form submission to update profile
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $area_name = $_POST['area_name'];
    
    // Handle profile picture upload
    $profile_picture = $user['profile_picture']; // Default to the current picture

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        // Upload new profile picture
        $upload_dir = 'uploads/';
        $upload_file = $upload_dir . basename($_FILES['profile_picture']['name']);
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_file)) {
            $profile_picture = $_FILES['profile_picture']['name']; // Update profile picture
        }
    }

    // Always update the user information in the `users` table
    $sql_users = "UPDATE users SET 
        name = '" . $conn->real_escape_string($name) . "', 
        email = '" . $conn->real_escape_string($email) . "', 
        phone_number = '" . $conn->real_escape_string($phone_number) . "', 
        area_name = '" . $conn->real_escape_string($area_name) . "', 
        profile_picture = '" . $conn->real_escape_string($profile_picture) . "' 
    WHERE user_id = $user_id";

    if ($conn->query($sql_users) === TRUE) {
        // Update the profile table if relevant fields are changed
        $sql_profiles = "UPDATE users SET 
            name = '" . $conn->real_escape_string($name) . "', 
            phone_number = '" . $conn->real_escape_string($phone_number) . "', 
            area_name = '" . $conn->real_escape_string($area_name) . "' 
        WHERE user_id = $user_id";
        
        // Execute the profile update if needed
        if ($conn->query($sql_profiles) === TRUE) {
            // Redirect to dashboard after successful update
            header("Location: userdashboard.php");
            exit();
        } else {
            // If no profile data needs to be updated
            header("Location: userdashboard.php");
            exit();
        }
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
    if ($conn->query($sql_users) === TRUE) {
        // Set success message in session
        $_SESSION['update_success'] = "Profile updated successfully!";
    } else {
        $_SESSION['update_error'] = "Error updating profile: " . $conn->error;
    }

}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .update-profile-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        .profile-img {
            border-radius: 50%;
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .profile-img-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="update-profile-container">
    <h2 class="text-center mb-4">Update Profile</h2>

    <!-- Show error message if any -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Profile Update Form -->
    <form method="POST" enctype="multipart/form-data">
        <div class="profile-img-container">
            <!-- Display current profile picture -->
            <img src="<?php echo isset($user['profile_picture']) ? 'uploads/' . $user['profile_picture'] : 'default-profile.jpg'; ?>" alt="Profile Picture" class="profile-img">
            <div class="mt-3">
                <label for="profile_picture" class="form-label">Change Profile Picture</label>
                <input type="file" class="form-control" id="profile_picture" name="profile_picture">
            </div>
        </div>

        <div class="form-group">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="text" class="form-control" id="phone" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
        </div>

        <div class="form-group">
            <label for="address" class="form-label">Address</label>
            <select class="form-control" id="area_name" name="area_name" required>
                <option value="" disabled <?php echo empty($user['area_name']) ? 'selected' : ''; ?>>Select an area</option>
                <option value="Kamukuzi" <?php echo $user['area_name'] == 'Kamukuzi' ? 'selected' : ''; ?>>Kamukuzi</option>
                <option value="Kakira" <?php echo $user['area_name'] == 'Kakira' ? 'selected' : ''; ?>>Kakira</option>
                <option value="Mbarara Hill" <?php echo $user['area_name'] == 'Mbarara Hill' ? 'selected' : ''; ?>>Mbarara Hill</option>
                <option value="Bujaja" <?php echo $user['area_name'] == 'Bujaja' ? 'selected' : ''; ?>>Bujaja</option>
                <option value="Ruharo" <?php echo $user['area_name'] == 'Ruharo' ? 'selected' : ''; ?>>Ruharo</option>
                <option value="Rukungiri" <?php echo $user['area_name'] == 'Rukungiri' ? 'selected' : ''; ?>>Rukungiri</option>
                <option value="Kakoba" <?php echo $user['area_name'] == 'Kakoba' ? 'selected' : ''; ?>>Kakoba</option>
                <option value="Kakiika" <?php echo $user['area_name'] == 'Kakiika' ? 'selected' : ''; ?>>Kakiika</option>
                <option value="Nyakayojo" <?php echo $user['area_name'] == 'Nyakayojo' ? 'selected' : ''; ?>>Nyakayojo</option>
                <option value="Nyamitanga" <?php echo $user['area_name'] == 'Nyamitanga' ? 'selected' : ''; ?>>Nyamitanga</option>
                <option value="Buziba" <?php echo $user['area_name'] == 'Buziba' ? 'selected' : ''; ?>>Buziba</option>
                <option value="Rubindi" <?php echo $user['area_name'] == 'Rubindi' ? 'selected' : ''; ?>>Rubindi</option>
                <option value="Katojo" <?php echo $user['area_name'] == 'Katojo' ? 'selected' : ''; ?>>Katojo</option>
                <option value="Kijungu" <?php echo $user['area_name'] == 'Kijungu' ? 'selected' : ''; ?>>Kijungu</option>
                <option value="Rwentuha" <?php echo $user['area_name'] == 'Rwentuha' ? 'selected' : ''; ?>>Rwentuha</option>
                <option value="Rujumbura" <?php echo $user['area_name'] == 'Rujumbura' ? 'selected' : ''; ?>>Rujumbura</option>
                <option value="Kyenjojo" <?php echo $user['area_name'] == 'Kyenjojo' ? 'selected' : ''; ?>>Kyenjojo</option>
                <option value="Rubirizi" <?php echo $user['area_name'] == 'Rubirizi' ? 'selected' : ''; ?>>Rubirizi</option>
            </select>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 for the address dropdown without search functionality
        $('#address').select2({
            minimumResultsForSearch: Infinity  // Disable search functionality
        });
    });
</script>

</body>
</html>
