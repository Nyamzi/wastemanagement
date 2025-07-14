<?php
require_once 'dbconnect.php';

// First, check if areas table exists and has area_name column
$check_table = "SHOW TABLES LIKE 'areas'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    die("Error: The 'areas' table does not exist. Please create it first.");
}

$check_column = "SHOW COLUMNS FROM areas LIKE 'area_name'";
$result = $conn->query($check_column);

if ($result->num_rows == 0) {
    die("Error: The 'area_name' column does not exist in the 'areas' table. Please add it first.");
}

// SQL to create predictions table
$sql = "CREATE TABLE IF NOT EXISTS waste_predictions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    day_of_week INT(2) NOT NULL,
    week_of_year INT(2) NOT NULL,
    month INT(2) NOT NULL,
    area_name VARCHAR(255) NOT NULL,
    pickup_type_encoded INT(2) NOT NULL,
    predicted_weight DECIMAL(10,2) NOT NULL,
    prediction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (area_name) REFERENCES areas(area_name)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table waste_predictions created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 