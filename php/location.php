<?php
session_start();

// Only municipal council users allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'municipal_council') {
    header("Location: login.php");
    exit();
}

$municipal_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['area_id'], $_POST['company_id'])) {
    $area_id = $_POST['area_id'];
    $company_id = $_POST['company_id'];

    // Assign or update the company in the area
    $stmt = $conn->prepare("UPDATE areas SET company_id = ? WHERE area_id = ? AND municipal_id = ?");
    $stmt->bind_param("iii", $company_id, $area_id, $municipal_id);
    $stmt->execute();
}

// Get areas managed by this municipal council
$query = "
   SELECT a.area_id, a.area_name, c.company_name 
FROM areas a
LEFT JOIN company_areas ca ON a.area_id = ca.area_id
LEFT JOIN companies c ON ca.company_id = c.company_id

";
$stmt = $conn->prepare($query);
$areas_result = $stmt->get_result();

// Get all companies
$companies_result = $conn->query("SELECT company_id, name FROM companies");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Companies to Areas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h3 class="mb-4">Assign Companies to Areas You Manage</h3>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Area</th>
                <th>Current Company</th>
                <th>Assign New Company</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $areas_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['area_name']) ?></td>
                    <td><?= htmlspecialchars($row['company_name'] ?? 'Not Assigned') ?></td>
                    <td>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="area_id" value="<?= $row['area_id'] ?>">
                            <select name="company_id" class="form-select" required>
                                <option value="">Select Company</option>
                                <?php foreach ($companies_result as $company): ?>
                                    <option value="<?= $company['company_id'] ?>" <?= $row['company_id'] == $company['company_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($company['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Assign</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
