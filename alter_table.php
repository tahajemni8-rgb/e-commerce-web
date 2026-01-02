<?php
require_once 'db_config.php';
$conn = getDBConnection();
$query = "ALTER TABLE orders MODIFY COLUMN status ENUM('pending','processing','completed','cancelled','confirmed','delivered') DEFAULT 'pending'";
if ($conn->query($query) === TRUE) {
    echo "Table updated successfully";
} else {
    echo "Error updating table: " . $conn->error;
}
$conn->close();
?>