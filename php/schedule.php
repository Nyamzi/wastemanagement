<?php
session_start();

// Check if the user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'company') {
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

// Get the list of areas
$area_sql = "SELECT * FROM pickup_areas";
$area_result = $conn->query($area_sql);

// Handle the form submission for adding a schedule
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_id = $_SESSION['user_id'];
    $area_id = $_POST['area_id'];
    $waste_type = $_POST['waste_type'];
    $collection_day = $_POST['collection_day'];

    // Insert the new waste collection schedule
    $sql = "INSERT INTO waste_collection_schedule (company_id, area_id, waste_type, collection_day) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $company_id, $area_id, $waste_type, $collection_day);

    if ($stmt->execute()) {
        // Success message
        echo "<div class='alert alert-success'>Waste collection schedule added successfully!</div>";

        // Reset the form fields after successful submission
        echo "<script>document.getElementById('scheduleForm').reset();</script>";
    } else {
        // Error message
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Waste Collection Schedule</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>

<div class="container mt-5">
    <h2>Add Waste Collection Schedule</h2>

    <!-- Form for adding waste collection schedule -->
    <form method="POST" action="" id="scheduleForm">
        <div class="mb-3">
            <label for="area_id" class="form-label">Select Area:</label>
            <select name="area_id" id="area_id" class="form-select" required>
                <option value="" disabled selected>Select Area</option>
                <?php while ($row = $area_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['area_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="waste_type" class="form-label">Waste Type:</label>
            <select name="waste_type" id="waste_type" class="form-select" required>
                <option value="" disabled selected>Select Waste Type</option>
                <option value="Plastic">Plastic</option>
                <option value="Organic">Organic</option>
                <option value="Electronic">Electronic</option>
                <option value="Glass">Glass</option>
                <option value="Metal">Metal</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="collection_day" class="form-label">Collection Day:</label>
            <select name="collection_day" id="collection_day" class="form-select" required>
                <option value="" disabled selected>Select Collection Day</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
                <option value="Saturday">Saturday</option>
                <option value="Sunday">Sunday</option>
            </select>
        </div>

        <!-- Submit button -->
        <button type="submit" class="btn btn-primary">Add Schedule</button>
    </form>

    <!-- Button to go back to the Company Dashboard -->
    <a href="companydashboard.php" class="btn btn-primary mt-3">Back to Dashboard</a>
</div>

<!-- Bootstrap JS (for tooltips and other interactivity) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
