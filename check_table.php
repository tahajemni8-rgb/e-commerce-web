<?php
require_once 'db_config.php';
$conn = getDBConnection();
$result = $conn->query("DESCRIBE orders");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == 'status') {
            echo "Status column: " . $row['Type'] . "\n";
        }
    }
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>