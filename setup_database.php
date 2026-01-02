<?php
// Database setup script
$host = 'localhost';
$user = 'root';
$pass = '';
$db_name = 'ecommerce_admin';

// Create connection without database
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.\n";
} else {
    echo "Error creating database: " . $conn->error . "\n";
}

// Select the database
$conn->select_db($db_name);

// Read and execute the SQL file
$sql_file = 'ecommerce_admin.sql';
if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));

    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            if ($conn->query($statement) === TRUE) {
                echo "Executed: " . substr($statement, 0, 50) . "...\n";
            } else {
                echo "Error executing statement: " . $conn->error . "\n";
                echo "Statement: " . $statement . "\n";
            }
        }
    }

    echo "Database setup completed!\n";
} else {
    echo "SQL file not found: $sql_file\n";
}

$conn->close();
?>