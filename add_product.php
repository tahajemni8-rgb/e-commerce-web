<?php
session_start();

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Check authentication
require_once 'admin_auth.php';
requireOwnerAccess();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $message = 'Security error. Please try again.';
        $messageType = 'error';
    } else {
        // Get and sanitize form data
        $productName = trim($_POST['product_name'] ?? '');
        $productDescription = trim($_POST['product_description'] ?? '');
        $productPrice = trim($_POST['product_price'] ?? '');
        $productCategory = trim($_POST['product_category'] ?? '');
        $productStock = trim($_POST['product_stock'] ?? '');
        $productImage = trim($_POST['product_image'] ?? '');

        // Validation
        $errors = [];

        if (empty($productName)) {
            $errors[] = 'Product name is required.';
        } elseif (strlen($productName) > 100) {
            $errors[] = 'Product name must be less than 100 characters.';
        }

        if (empty($productDescription)) {
            $errors[] = 'Product description is required.';
        } elseif (strlen($productDescription) > 1000) {
            $errors[] = 'Product description must be less than 1000 characters.';
        }

        if (empty($productPrice) || !is_numeric($productPrice) || $productPrice < 0) {
            $errors[] = 'Please enter a valid price.';
        } elseif ($productPrice > 99999.99) {
            $errors[] = 'Price cannot exceed $99,999.99.';
        }

        if (empty($productCategory)) {
            $errors[] = 'Product category is required.';
        } elseif (!in_array($productCategory, ['homme', 'femme', 'enfant', 'accessoires'])) {
            $errors[] = 'Please select a valid category.';
        }

        if (empty($productStock) || !is_numeric($productStock) || $productStock < 0) {
            $errors[] = 'Please enter a valid stock quantity.';
        } elseif ($productStock > 99999) {
            $errors[] = 'Stock quantity cannot exceed 99,999.';
        }

        if (empty($productImage)) {
            $errors[] = 'Product image URL is required.';
        } elseif (!filter_var($productImage)) {
            $errors[] = 'Please enter a valid image URL.';
        }

        if (empty($errors)) {
            // Save to database
            require_once 'db_config.php';

            try {
                // Get category ID
                $categoryRow = getSingleRow("SELECT id FROM categories WHERE name = ?", [$productCategory], "s");
                if (!$categoryRow) {
                    $errors[] = 'Invalid category selected.';
                } else {
                    $categoryId = $categoryRow['id'];

                    // Insert product
                    executeQuery(
                        "INSERT INTO products (category_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)",
                        [$categoryId, $productName, $productDescription, $productPrice, $productStock, $productImage],
                        "issdis"
                    );

                    $message = 'Product added successfully! The product is now visible in the store.';
                    $messageType = 'success';

                    // Log the action
                    logSecurityEvent('PRODUCT_ADDED', "Product: $productName, Category: $productCategory, Price: $$productPrice");

                    // Clear form data for success
                    $_POST = [];
                }
            } catch (Exception $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
            $_POST = [];
        } if (!empty($errors)) {
            $message = implode('<br>', $errors);
            $messageType = 'error';
        }
    }


// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Get categories for dropdown
require_once 'db_config.php';
try {
    $categoryRows = getMultipleRows("SELECT name FROM categories ORDER BY name");
    $categories = [];
    foreach ($categoryRows as $row) {
        $categories[$row['name']] = ucfirst($row['name']);
    }
} catch (Exception $e) {
    // Fallback to hardcoded categories if database error
    $categories = [
        'homme' => 'Men',
        'femme' => 'Women',
        'enfant' => 'Children',
        'accessoires' => 'Accessories'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product - Owner Dashboard</title>
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

        .nav-links {
            display: flex;
            gap: 15px;
        }

        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.2);
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 14px;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="text"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group input[type="text"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group select {
            cursor: pointer;
        }

        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            display: none;
            border: 2px solid #e1e5e9;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .required {
            color: #dc3545;
        }

        .price-input-group {
            display: flex;
            align-items: center;
        }

        .price-input-group input {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-right: none;
        }

        .price-symbol {
            padding: 12px 15px;
            background: #e9ecef;
            border: 2px solid #e1e5e9;
            border-left: none;
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            color: #495057;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1><i class="fas fa-plus"></i> Add New Product</h1>
            <div class="nav-links">
                <a href="owner_dashboard.php" class="nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="?logout=1" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="form-card">
            <div class="form-header">
                <h2><i class="fas fa-box"></i> Product Information</h2>
                <p>Fill in the details below to add a new product to your store</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="productForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="product_name">Product Name <span class="required">*</span></label>
                        <input type="text" id="product_name" name="product_name" required
                               maxlength="100" value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>">
                        <div class="help-text">Maximum 100 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="product_category">Category <span class="required">*</span></label>
                        <select id="product_category" name="product_category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?php echo $value; ?>"
                                        <?php echo (($_POST['product_category'] ?? '') === $value) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="product_price">Price <span class="required">*</span></label>
                        <div class="price-input-group">
                            <input type="number" id="product_price" name="product_price" required
                                   min="0" max="99999.99" step="0.01"
                                   value="<?php echo htmlspecialchars($_POST['product_price'] ?? ''); ?>">
                            <span class="price-symbol">DT</span>
                        </div>
                        <div class="help-text">Maximum $99,999.99</div>
                    </div>

                    <div class="form-group">
                        <label for="product_stock">Stock Quantity <span class="required">*</span></label>
                        <input type="number" id="product_stock" name="product_stock" required
                               min="0" max="99999" value="<?php echo htmlspecialchars($_POST['product_stock'] ?? ''); ?>">
                        <div class="help-text">Maximum 99,999 units</div>
                    </div>

                    <div class="form-group full-width">
                        <label for="product_image">Product Image <span class="required">*</span></label>
                        <input type="text" id="product_image" name="product_image" required
                               placeholder="assets/images/image.jpg"
                               value="<?php echo htmlspecialchars($_POST['product_image'] ?? ''); ?>">
                        <div class="help-text">Enter a valid image (JPG, PNG, GIF)</div>
                        <img id="imagePreview" class="image-preview" alt="Image preview">
                    </div>

                    <div class="form-group full-width">
                        <label for="product_description">Product Description <span class="required">*</span></label>
                        <textarea id="product_description" name="product_description" required
                                  maxlength="1000" rows="4"><?php echo htmlspecialchars($_POST['product_description'] ?? ''); ?></textarea>
                        <div class="help-text">Maximum 1000 characters. Describe the product features, materials, care instructions, etc.</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Add Product
                    </button>
                    <a href="owner_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
     /*   // Image preview functionality
        document.getElementById('product_image').addEventListener('input', function() {
            const url = this.value.trim();
            const preview = document.getElementById('imagePreview');

            if (url) {
                preview.src = url;
                preview.style.display = 'block';
                preview.onerror = function() {
                    this.style.display = 'none';
                    alert('Invalid image URL. Please check the link.');
                };
            } else {
                preview.style.display = 'none';
            }
        });*/

        // Form validation and submission
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            const originalText = submitBtn.innerHTML;

            // Basic client-side validation
            const name = document.getElementById('product_name').value.trim();
            const description = document.getElementById('product_description').value.trim();
            const price = document.getElementById('product_price').value;
            const stock = document.getElementById('product_stock').value;
            const image = document.getElementById('product_image').value.trim();

            if (!name || !description || !price || !stock || !image) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }

            if (name.length > 100) {
                e.preventDefault();
                alert('Product name must be less than 100 characters.');
                return;
            }

            if (description.length > 1000) {
                e.preventDefault();
                alert('Product description must be less than 1000 characters.');
                return;
            }

            if (parseFloat(price) <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0.');
                return;
            }

            if (parseInt(stock) < 0) {
                e.preventDefault();
                alert('Stock quantity cannot be negative.');
                return;
            }

            // Disable button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding Product...';

            // Re-enable after 10 seconds if no response
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 10000);
        });

        // Character counter for description
        document.getElementById('product_description').addEventListener('input', function() {
            const maxLength = 1000;
            const currentLength = this.value.length;

            if (currentLength > maxLength) {
                this.value = this.value.substring(0, maxLength);
            }
        });

        // Auto-focus first field
        document.getElementById('product_name').focus();

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>