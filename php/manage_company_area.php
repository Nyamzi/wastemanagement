<?php
session_start();
// Only municipal councils allowed
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'municipal_council') {
    header("Location: ../login.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "wastemanagement");
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

$message = '';
$cid = null;

// Handle POST to save area assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company_id'])) {
    $cid = (int)$_POST['company_id'];

    // Clear previous
    $delete = $conn->prepare("DELETE FROM company_areas WHERE company_id = ?");
    $delete->bind_param("i", $cid);
    $delete->execute();
    $delete->close();

    // Add new
    if (!empty($_POST['areas'])) {
        $insert = $conn->prepare("INSERT INTO company_areas (company_id, area_id) VALUES (?, ?)");
        foreach ($_POST['areas'] as $aid) {
            $aid = (int)$aid;
            $insert->bind_param("ii", $cid, $aid);
            $insert->execute();
        }
        $insert->close();
    }

    $message = "Areas updated for the company.";
}

// Get company list
$companies = $conn->query("SELECT company_id, company_name FROM companies ORDER BY company_name");

// Check selected company from GET
if (isset($_GET['company_id'])) {
    $cid = (int)$_GET['company_id'];
}

$company_name = '';
$assigned = [];
$areas = [];

if ($cid) {
    // Get selected company name from companies table
    $q = $conn->prepare("SELECT company_name FROM companies WHERE company_id = ?");
    $q->bind_param("i", $cid);
    $q->execute();
    $q->bind_result($company_name);
    $q->fetch();
    $q->close();

    // Get assigned area ids
    $areas_res = $conn->query("SELECT area_id FROM company_areas WHERE company_id = $cid");
    $assigned = array_column($areas_res->fetch_all(MYSQLI_ASSOC), 'area_id');

    // All areas list
    $areas = $conn->query("SELECT area_id, area_name FROM areas ORDER BY area_name");
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manage Company Areas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="mb-4">Assign Operating Areas</h1>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!$cid): ?>
        <!-- Company List -->
        <table class="table bg-white">
            <thead><tr><th>#</th><th>Company</th><th>Action</th></tr></thead>
            <tbody>
            <?php $i = 1; while ($c = $companies->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($c['company_name']) ?></td>
                    <td>
                        <a href="?company_id=<?= $c['company_id'] ?>" class="btn btn-sm btn-primary">Manage Areas</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <a href="municipaldashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
    <?php else: ?>
        <a href="manage_company_area.php" class="btn btn-secondary mb-3">← Back to Companies</a>
        <h3>Assign Areas to “<?= htmlspecialchars($company_name) ?>”</h3>

        <form method="POST" class="bg-white p-4 rounded shadow-sm">
            <input type="hidden" name="company_id" value="<?= $cid ?>">
            <div class="row">
                <?php while ($a = $areas->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="areas[]" value="<?= $a['area_id'] ?>"
                                   id="area<?= $a['area_id'] ?>"
                                   <?= in_array($a['area_id'], $assigned) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="area<?= $a['area_id'] ?>">
                                <?= htmlspecialchars($a['area_name']) ?>
                            </label>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <button class="btn btn-success mt-3">Save Assignments</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
