<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "wastemanagement";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = "SELECT area_id, pickup_type, weight, DATE(created_at) as pickup_date FROM pickup_requests WHERE status = 'completed'";
$result = $conn->query($sql);

$fp = fopen("../ml/pickup_data.csv", "w");
fputcsv($fp, ['area_id', 'waste_type', 'weight', 'pickup_date']);

while ($row = $result->fetch_assoc()) {
    fputcsv($fp, $row);
}

fclose($fp);
echo "Exported successfully!";
$conn->close();
?>
