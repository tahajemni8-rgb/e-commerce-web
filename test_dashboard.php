<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock session for testing
$_SESSION = [
    'owner_username' => 'test_admin',
    'login_time' => time()
];

ob_start();
include 'owner_dashboard.php';
$output = ob_get_clean();

if (strpos($output, 'Undefined array key') !== false) {
    echo "❌ Still has undefined array key errors\n";
} else {
    echo "✅ No undefined array key errors\n";
}

if (strpos($output, 'htmlspecialchars(): Passing null') !== false) {
    echo "❌ Still has htmlspecialchars null errors\n";
} else {
    echo "✅ No htmlspecialchars null errors\n";
}

if (strpos($output, 'number_format(): Passing null') !== false) {
    echo "❌ Still has number_format null errors\n";
} else {
    echo "✅ No number_format null errors\n";
}

echo "Test completed.\n";
?>