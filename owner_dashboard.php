<?php
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Check authentication
require_once 'admin_auth.php';
requireOwnerAccess();

// Auto-logout after 2 hours of inactivity
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
    session_destroy();
    header('Location: dashboard_login.php?expired=1');
    exit();
}

// Update last activity
$_SESSION['login_time'] = time();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dashboard_login.php?logout=1');
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
        exit();
    }
    header('Content-Type: application/json');
    try {
        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['new_status'];
        if (in_array($new_status, ['pending', 'confirmed', 'delivered'])) {
            require_once 'db_config.php';
            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("si", $new_status, $order_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
        exit();
    }
    header('Content-Type: application/json');
    try {
        $order_id = (int)$_POST['order_id'];
        require_once 'db_config.php';
        $conn = getDBConnection();
        // Delete order items first
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        // Then delete order
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Handle order search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_orders'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
        exit();
    }
    header('Content-Type: application/json');
    try {
        $query = trim($_POST['query']);
        require_once 'db_config.php';
        $conn = getDBConnection();
        $sql = "
            SELECT o.id, o.order_code, o.customer, o.total_amount, o.status, o.created_at,
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.order_code LIKE ? OR o.customer LIKE ? OR o.status LIKE ? OR o.created_at LIKE ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ";
        $searchTerm = '%' . $query . '%';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Handle load orders for pagination
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_orders'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
        exit();
    }
    header('Content-Type: application/json');
    try {
        $page = (int)($_POST['page'] ?? 1);
        $perPage = 5;
        $offset = ($page - 1) * $perPage;
        require_once 'db_config.php';
        $conn = getDBConnection();
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM orders";
        $countResult = $conn->query($countSql);
        $total = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($total / $perPage);
        
        // Get orders
        $sql = "
            SELECT o.id, o.order_code, o.customer, o.total_amount, o.status, o.created_at,
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $stmt->close();
        echo json_encode(['success' => true, 'orders' => $orders, 'total_pages' => $totalPages, 'current_page' => $page]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Get dashboard statistics
function getDashboardStats() {
    require_once 'db_config.php';

    $stats = [
        'total_orders' => 0,
        'total_revenue' => 0.00,
        'pending_orders' => 0,
        'completed_orders' => 0,
        'total_products' => 0,
        'low_stock_items' => 0,
        'recent_orders' => [],
        'monthly_revenue' => []
    ];

    try {
        // Get order statistics
        $orderStats = getSingleRow("
            SELECT
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(total_amount) as total_revenue
            FROM orders
        ");

        if ($orderStats) {
            $stats['total_orders'] = (int)$orderStats['total_orders'];
            $stats['pending_orders'] = (int)$orderStats['pending_orders'];
            $stats['completed_orders'] = (int)$orderStats['completed_orders'];
            $stats['total_revenue'] = (float)$orderStats['total_revenue'];
        }

        // Get product statistics
        $productStats = getSingleRow("
            SELECT
                COUNT(*) as total_products,
                SUM(CASE WHEN stock < 10 THEN 1 ELSE 0 END) as low_stock_items
            FROM products
        ");

        if ($productStats) {
            $stats['total_products'] = (int)$productStats['total_products'];
            $stats['low_stock_items'] = (int)$productStats['low_stock_items'];
        }

        // Get recent orders
        $stats['recent_orders'] = getMultipleRows("
            SELECT o.id, o.order_code,o.customer, o.total_amount, o.status, o.created_at,
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 5
        ");

    } catch (Exception $e) {
        // If database error, keep default values
        error_log('Dashboard stats error: ' . $e->getMessage());
    }

    return $stats;
}

$stats = getDashboardStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - E-Commerce Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 14px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            text-decoration: none;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 12px;
            color: #28a745;
        }

        .stat-change.negative {
            color: #dc3545;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .main-content {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recent-orders {
            margin-bottom: 30px;
        }

        .search-container {
            margin-bottom: 15px;
        }

        #order-search {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        #order-search:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            overflow: visible;
        }

        .orders-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }

        .order-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            position: relative;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-confirmed {
            background: #cce5ff;
            color: #004085;
        }

        .status-delivered {
            background: #d4edda;
            color: #155724;
        }

        .order-status.clickable {
            cursor: pointer;
        }

        .status-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 4px 8px;
            margin: 2px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .status-btn:hover {
            background: #0056b3;
            transform: scale(1.05);
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s ease;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .pagination button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .pagination button:hover:not(:disabled) {
            background: #0056b3;
        }

        .pagination button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .status-options {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
            padding: 5px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }

        .sidebar-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .quick-actions {
            display: grid;
            gap: 10px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .revenue-chart {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: end;
            justify-content: space-around;
            padding: 20px;
            margin-top: 15px;
        }

        .chart-bar {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .bar {
            width: 30px;
            background: rgba(255,255,255,0.8);
            border-radius: 4px 4px 0 0;
            transition: all 0.3s ease;
        }

        .bar:hover {
            background: white;
        }

        .bar-label {
            font-size: 12px;
            color: white;
            font-weight: 500;
        }

        .bar-value {
            font-size: 10px;
            color: rgba(255,255,255,0.8);
            margin-top: 5px;
        }

        .alerts {
            margin-top: 15px;
        }

        .alert {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .alert-info {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b3d7ff;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1><i class="fas fa-tachometer-alt"></i> Owner Dashboard</h1>
            <div class="header-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['owner_username']); ?></span>
                <span>Last login: <?php echo date('M j, Y H:i', $_SESSION['login_time']); ?></span>
                <a href="?logout=1" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-shopping-cart"></i> Total Orders</h3>
                <div class="stat-value"><?php echo number_format($stats['total_orders'] ?? 0); ?></div>
                <div class="stat-change">+12% from last month</div>
            </div>

            <div class="stat-card">
                <h3><i class="fas fa-dollar-sign"></i> Total Revenue</h3>
                <div class="stat-value">$<?php echo number_format($stats['total_revenue'] ?? 0, 2); ?></div>
                <div class="stat-change">+8% from last month</div>
            </div>

            <div class="stat-card">
                <h3><i class="fas fa-clock"></i> Pending Orders</h3>
                <div class="stat-value"><?php echo $stats['pending_orders'] ?? 0; ?></div>
                <div class="stat-change negative">-2 from yesterday</div>
            </div>

            <div class="stat-card">
                <h3><i class="fas fa-box"></i> Total Products</h3>
                <div class="stat-value"><?php echo $stats['total_products'] ?? 0; ?></div>
                <div class="stat-change">+5 new this month</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-grid">
            <div class="main-content">
                <!-- Recent Orders -->
                <div class="recent-orders">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i> Recent Orders
                    </h2>
                    <div class="search-container">
                        <input type="text" id="order-search" placeholder="Search orders by ID, customer, amount, status, or date...">
                    </div>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_orders'] as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_code'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer'] ?? 'N/A'); ?></td>
                                    <td>$<?php echo number_format((float)($order['total_amount'] ?? 0), 2); ?></td>
                                    <td>
                                        <span class="order-status status-<?php echo htmlspecialchars($order['status'] ?? 'unknown'); ?> clickable" data-order-id="<?php echo htmlspecialchars($order['id']); ?>">
                                            <?php echo ucfirst($order['status'] ?? 'unknown'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['created_at'] ?? 'N/A'); ?></td>
                                    <td><button class="delete-btn" data-order-id="<?php echo htmlspecialchars($order['id']); ?>">Delete</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination">
                        <button id="prev-page" disabled>« Previous</button>
                        <span id="page-info">1 / 1</span>
                        <button id="next-page" disabled>Next »</button>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="revenue-chart-section">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i> Monthly Revenue
                    </h2>
                    <div class="revenue-chart">
                        <?php
                        $monthlyData = $stats['monthly_revenue'] ?? [];
                        if (!empty($monthlyData)):
                            $maxRevenue = max(array_column($monthlyData, 'revenue'));
                            foreach ($monthlyData as $month):
                                $height = $maxRevenue > 0 ? (($month['revenue'] ?? 0) / $maxRevenue) * 100 : 0;
                        ?>
                            <div class="chart-bar">
                                <div class="bar" style="height: <?php echo $height; ?>%"></div>
                                <div class="bar-label"><?php echo htmlspecialchars($month['month'] ?? 'N/A'); ?></div>
                                <div class="bar-value">$<?php echo number_format($month['revenue'] ?? 0); ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: #666; padding: 40px;">
                                <i class="fas fa-chart-line" style="font-size: 48px; margin-bottom: 10px;"></i>
                                <p>No revenue data available</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions">
                        <a href="add_product.php" class="action-btn">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                        <a href="delete_product.php" class="action-btn">
                            <i class="fas fa-trash"></i> Delete Products
                        </a>
                        <a href="edit_product.php" class="action-btn">
                            <i class="fas fa-pen"></i> Change Product
                        </a>
                        <a href="#" class="action-btn">
                            <i class="fas fa-chart-line"></i> User
                        </a>
                        <a href="#" class="action-btn">
                            <i class="fas fa-envelope"></i> Send Newsletter
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-bell"></i> Alerts</h3>
                    <div class="alerts">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            5 products are low on stock
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Server maintenance scheduled for Jan 15
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-server"></i> System Status</h3>
                    <div class="status-info">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Server Load:</span>
                            <span style="color: #28a745;">Normal</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Database:</span>
                            <span style="color: #28a745;">Connected</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Email Service:</span>
                            <span style="color: #28a745;">Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';
        // Auto-refresh functionality (optional)
        let refreshInterval;

        function startAutoRefresh() {
            refreshInterval = setInterval(() => {
                // In a real application, you might refresh specific data
                console.log('Dashboard data refresh would happen here');
            }, 300000); // 5 minutes
        }

        function stopAutoRefresh() {
            clearInterval(refreshInterval);
        }

        // Start auto-refresh when page loads
        startAutoRefresh();

        // Stop auto-refresh when page is not visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoRefresh();
            } else {
                startAutoRefresh();
            }
        });

        // Add some interactive features
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Only prevent default for buttons without href or with href="#"
                if (!this.href || this.href === '#' || this.href.endsWith('#')) {
                    e.preventDefault();
                    alert('This feature is coming soon! 🚀');
                }
                // Let normal navigation happen for actual links
            });
        });

        // Handle status change for all orders
        document.querySelectorAll('.order-status.clickable').forEach(span => {
            span.addEventListener('click', function() {
                if (this.querySelector('.status-options')) return; // already shown
                const orderId = this.getAttribute('data-order-id');
                const currentStatus = this.textContent.toLowerCase();
                let buttons = '';
                if (currentStatus === 'pending') {
                    buttons = `<button class="status-btn" data-status="confirmed">Confirmed</button>
                               <button class="status-btn" data-status="delivered">Delivered</button>`;
                } else if (currentStatus === 'confirmed') {
                    buttons = `<button class="status-btn" data-status="delivered">Delivered</button>
                               <button class="status-btn" data-status="pending">Pending</button>`;
                } else if (currentStatus === 'delivered') {
                    buttons = `<button class="status-btn" data-status="confirmed">Confirmed</button>
                               <button class="status-btn" data-status="pending">Pending</button>`;
                } else {
                    buttons = `<button class="status-btn" data-status="confirmed">Confirmed</button>
                               <button class="status-btn" data-status="delivered">Delivered</button>`;
                }
                const optionsDiv = document.createElement('div');
                optionsDiv.className = 'status-options';
                optionsDiv.innerHTML = buttons;
                this.appendChild(optionsDiv);
                // Add event listeners to buttons
                optionsDiv.querySelectorAll('.status-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const newStatus = this.getAttribute('data-status');
                        updateOrderStatus(orderId, newStatus);
                    });
                });
            });
        });

        function updateOrderStatus(orderId, newStatus) {
            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('order_id', orderId);
            formData.append('new_status', newStatus);
            formData.append('csrf_token', csrfToken);
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Error updating status: ' + (data.error || 'Unknown error'));
                }
            }).catch(error => {
                console.error('Error:', error);
                alert('Error updating status');
            });
        }

        // Handle order deletion
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this order?')) {
                    const orderId = this.getAttribute('data-order-id');
                    const formData = new FormData();
                    formData.append('delete_order', '1');
                    formData.append('order_id', orderId);
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    }).then(response => response.json()).then(data => {
                        if (data.success) {
                            location.reload(); // Refresh to show updated list
                        } else {
                            alert('Error deleting order: ' + (data.error || 'Unknown error'));
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting order');
                    });
                }
            });
        });

        // Handle order search and pagination
        const searchInput = document.getElementById('order-search');
        let originalRows = document.querySelector('.orders-table tbody').innerHTML;
        let currentPage = 1;
        let totalPages = 1;
        let currentQuery = '';

        function loadOrders(page) {
            const formData = new FormData();
            formData.append('load_orders', '1');
            formData.append('page', page);
            formData.append('csrf_token', csrfToken);
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    currentPage = data.current_page;
                    totalPages = data.total_pages;
                    updateTableWithResults(data.orders);
                    updatePagination();
                } else {
                    console.error('Load error:', data.error);
                }
            }).catch(error => {
                console.error('Error:', error);
            });
        }

        function loadSearch(query) {
            const formData = new FormData();
            formData.append('search_orders', '1');
            formData.append('query', query);
            formData.append('csrf_token', csrfToken);
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    updateTableWithResults(data.orders);
                    // Disable pagination for search
                    totalPages = 1;
                    currentPage = 1;
                    updatePagination();
                } else {
                    console.error('Search error:', data.error);
                }
            }).catch(error => {
                console.error('Error:', error);
            });
        }

        function updatePagination() {
            document.getElementById('page-info').textContent = `${currentPage} / ${totalPages}`;
            document.getElementById('prev-page').disabled = currentPage <= 1;
            document.getElementById('next-page').disabled = currentPage >= totalPages;
        }

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            currentQuery = query;
            if (query === '') {
                // Load page 1
                loadOrders(1);
                return;
            }
            loadSearch(currentQuery);
        });

        document.getElementById('prev-page').addEventListener('click', function() {
            if (currentPage > 1 && currentQuery === '') {
                currentPage--;
                loadOrders(currentPage);
            }
        });

        document.getElementById('next-page').addEventListener('click', function() {
            if (currentPage < totalPages && currentQuery === '') {
                currentPage++;
                loadOrders(currentPage);
            }
        });

        function updateTableWithResults(orders) {
            const tbody = document.querySelector('.orders-table tbody');
            tbody.innerHTML = '';
            orders.forEach(order => {
                const row = `
                    <tr>
                        <td>${order.order_code || 'N/A'}</td>
                        <td>${order.customer || 'N/A'}</td>
                        <td>$${parseFloat(order.total_amount || 0).toFixed(2)}</td>
                        <td>
                            <span class="order-status status-${order.status || 'unknown'} clickable" data-order-id="${order.id}">
                                ${(order.status || 'unknown').charAt(0).toUpperCase() + (order.status || 'unknown').slice(1)}
                            </span>
                        </td>
                        <td>${order.created_at || 'N/A'}</td>
                        <td><button class="delete-btn" data-order-id="${order.id}">Delete</button></td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
            attachEventListeners();
        }

        function updateTableWithResults(orders) {
            const tbody = document.querySelector('.orders-table tbody');
            tbody.innerHTML = '';
            orders.forEach(order => {
                const row = `
                    <tr>
                        <td>${order.order_code || 'N/A'}</td>
                        <td>${order.customer || 'N/A'}</td>
                        <td>$${parseFloat(order.total_amount || 0).toFixed(2)}</td>
                        <td>
                            <span class="order-status status-${order.status || 'unknown'} clickable" data-order-id="${order.id}">
                                ${(order.status || 'unknown').charAt(0).toUpperCase() + (order.status || 'unknown').slice(1)}
                            </span>
                        </td>
                        <td>${order.created_at || 'N/A'}</td>
                        <td><button class="delete-btn" data-order-id="${order.id}">Delete</button></td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
            // Attach event listeners to new elements
            attachEventListeners();
        }

        function attachEventListeners() {
            // Status change listeners
            document.querySelectorAll('.order-status.clickable').forEach(span => {
                span.addEventListener('click', function() {
                    if (this.querySelector('.status-options')) return;
                    const orderId = this.getAttribute('data-order-id');
                    const currentStatus = this.textContent.toLowerCase();
                    let buttons = '';
                    if (currentStatus === 'pending') {
                        buttons = `<button class="status-btn" data-status="confirmed">Confirmed</button>
                                   <button class="status-btn" data-status="delivered">Delivered</button>`;
                    } else if (currentStatus === 'confirmed') {
                        buttons = `<button class="status-btn" data-status="delivered">Delivered</button>
                                   <button class="status-btn" data-status="pending">Pending</button>`;
                    } else if (currentStatus === 'delivered') {
                        buttons = `<button class="status-btn" data-status="confirmed">Confirmed</button>
                                   <button class="status-btn" data-status="pending">Pending</button>`;
                    } else {
                        buttons = `<button class="status-btn" data-status="confirmed">Confirmed</button>
                                   <button class="status-btn" data-status="delivered">Delivered</button>`;
                    }
                    const optionsDiv = document.createElement('div');
                    optionsDiv.className = 'status-options';
                    optionsDiv.innerHTML = buttons;
                    this.appendChild(optionsDiv);
                    optionsDiv.querySelectorAll('.status-btn').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            const newStatus = this.getAttribute('data-status');
                            updateOrderStatus(orderId, newStatus);
                        });
                    });
                });
            });

            // Delete listeners
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this order?')) {
                        const orderId = this.getAttribute('data-order-id');
                        const formData = new FormData();
                        formData.append('delete_order', '1');
                        formData.append('order_id', orderId);
                        formData.append('csrf_token', csrfToken);
                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        }).then(response => response.json()).then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Error deleting order: ' + (data.error || 'Unknown error'));
                            }
                        }).catch(error => {
                            console.error('Error:', error);
                            alert('Error deleting order');
                        });
                    }
                });
            });
        }

        // Initialize pagination
        updatePagination();
        loadOrders(1); // Load to get correct totalPages
    </script>
</body>
</html>