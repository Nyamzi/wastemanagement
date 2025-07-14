<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "wastemanagement";

$conn= new mysqli($servername,$username,$password,$dbname);
if($conn->connect_error){
    die("connection failed: " .$conn->connect_error);
}

// Add image_path column to waste_predictions table if it doesn't exist
$alter_table_sql = "ALTER TABLE waste_predictions ADD COLUMN IF NOT EXISTS image_path VARCHAR(255)";
$conn->query($alter_table_sql);

// Add pickup_time column to pickup_requests table if it doesn't exist
$alter_table_sql = "ALTER TABLE pickup_requests ADD COLUMN IF NOT EXISTS pickup_time TIME";
$conn->query($alter_table_sql);