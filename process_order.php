<?php
// Disable error output to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unwanted output
ob_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require_once 'db_config.php';

// Load email configuration
$config = require 'email_config.php';

$admin_email = $config['admin_email'];
$smtp_host = $config['smtp']['host'];
$smtp_username = $config['smtp']['username'];
$smtp_password = $config['smtp']['password'];
$smtp_port = $config['smtp']['port'];
$encryption = $config['smtp']['encryption'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any previous output
    ob_clean();

    $rawInput = file_get_contents('php://input');
    error_log('Raw input received: ' . $rawInput);
    error_log('Input length: ' . strlen($rawInput));

    $input = json_decode($rawInput, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg());
        echo json_encode(['success' => false, 'message' => 'JSON decode error: ' . json_last_error_msg()]);
        exit;
    }

    if (!$input) {
        error_log('Input is empty or null after decode');
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data.']);
        exit;
    }

    $full_name = htmlspecialchars($input['full_name']);
    $phone = htmlspecialchars($input['phone']);
    $email = htmlspecialchars($input['email']);
    $location = htmlspecialchars($input['location']);
    $cart_items = $input['cart_items'];
    $subtotal = floatval($input['subtotal']);
    $delivery_fee = floatval($input['delivery_fee']);
    $total = floatval($input['total']);

    // Validate inputs
    if (empty($full_name) || empty($phone) || empty($email) || empty($location) || empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        exit;
    }

    // Group cart items by product and count quantities
    $grouped_items = [];
    foreach ($cart_items as $item) {
        $key = $item['id'] ?? $item['name']; // Use id if available, otherwise name
        if (!isset($grouped_items[$key])) {
            $grouped_items[$key] = [
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => 0
            ];
        }
        $grouped_items[$key]['quantity']++;
    }

    // Generate order details
    $order_details = "Order Details:\n\n";
    $order_details .= "Customer Information:\n";
    $order_details .= "Name: {$full_name}\n";
    $order_details .= "Phone: {$phone}\n";
    $order_details .= "Email: {$email}\n";
    $order_details .= "Delivery Address: {$location}\n\n";

    $order_details .= "Items Ordered:\n";
    foreach ($grouped_items as $item) {
        $order_details .= "- {$item['name']} (x{$item['quantity']}) - " . number_format($item['price'] * $item['quantity'], 2) . " DT\n";
    }

    $order_details .= "Total: " . number_format($total, 2) . " DT\n";

    // Save order to database
    try {
        $conn = getDBConnection();

        // Start transaction
        $conn->begin_transaction();

        // Generate order code
        $order_code = 'ORD-' . time() . '-' . rand(100, 999);

        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (order_code, customer , total_amount, status, created_at ) VALUES (?, ? , ?, 'pending', CURDATE() )");
        $stmt->bind_param("ssd", $order_code, $full_name , $total);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        // Insert order items
        foreach ($grouped_items as $item) {
            // Get product ID from name (this is a simplified approach - in production you'd pass product IDs)
            $product_name = $item['name'];
            $product_row = getSingleRow("SELECT id FROM products WHERE name = ?", [$product_name], "s");

            if ($product_row) {
                $product_id = $product_row['id'];
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $product_id, $item['quantity'], $item['price']);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Insert payment record
        $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, payment_method, status) VALUES (?, ?, 'cash_on_delivery', 'unpaid')");
        $stmt->bind_param("ds", $order_id, $total);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        // Update order details with order code
        $order_details = "Order #" . $order_code . "\n\n" . $order_details;

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        error_log('Database error: ' . $e->getMessage());
        // Continue with email sending even if database save fails
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtp_port;

        // Recipients
        $mail->setFrom($email, $full_name);
        $mail->addAddress($admin_email, 'Admin');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New Order Received - ' . $order_code;
        $mail->Body = "
            <h2>New Order Received</h2>
            <h3>Customer Information</h3>
            <p><strong>Name:</strong> {$full_name}</p>
            <p><strong>Phone:</strong> {$phone}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p><strong>Delivery Address:</strong> {$location}</p>

            <h3>Order Details</h3>
            <table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($grouped_items as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $mail->Body .= "
                    <tr>
                        <td>{$item['name']}</td>
                        <td>{$item['quantity']}</td>
                        <td>" . number_format($item['price'], 2) . " DT</td>
                        <td>" . number_format($item_total, 2) . " DT</td>
                    </tr>";
        }

        $mail->Body .= "
                </tbody>
            </table>

            <h3>Order Summary</h3>
            <p><strong>Subtotal:</strong> " . number_format($subtotal, 2) . " DT</p>
            <p><strong>Delivery Fee:</strong> " . number_format($delivery_fee, 2) . " DT</p>
            <p><strong>Total:</strong> " . number_format($total, 2) . " DT</p>

            <hr>
            <p><small>Order placed from your website</small></p>
        ";
        $mail->AltBody = $order_details;

        // Send email and ensure no output
        $emailSent = false;
        try {
            $emailSent = $mail->send();
        } catch (Exception $e) {
            error_log('PHPMailer send error: ' . $e->getMessage());
        }
        
        if ($emailSent) {
            // Clean output buffer and send JSON response
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Order placed successfully! We will contact you soon.']);
            exit;
        } else {
            // Clean output buffer and send error response
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to send email. Order may not have been processed.']);
            exit;
        }

    } catch (Exception $e) {
        // Clean output buffer and send error response
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Order could not be processed. Error: ' . $mail->ErrorInfo]);
        exit;
    }
} else {
    // Clean output buffer and send error response
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
?></content>
<parameter name="filePath">c:\wamp64\www\ecomerce\process_order.php