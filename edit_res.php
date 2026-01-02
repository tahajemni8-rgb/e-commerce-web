<?php

session_start();

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Check authentication
require_once 'admin_auth.php';
require_once 'db_config.php';
requireOwnerAccess();





   $id          = (int)$_POST['id'];
$name        = trim($_POST['name']);
$price       = (float)$_POST['price'];
$category    = trim($_POST['category']);
$image       = trim($_POST['image']);
$description = trim($_POST['description']);

$categoryRow = getSingleRow("SELECT id FROM categories WHERE name = ?", [$category], "s");
$categoryId = $categoryRow['id'] ?? null;

executeQuery("
    UPDATE products
    SET name = ?, price = ?, image = ?, description = ?, category_id = ?
    WHERE id = ?
", [$name, $price, $image, $description, $categoryId, $id], "sdssii");

    try {
    $product = getSingleRow("
        SELECT p.id, p.name AS name, p.price AS price, p.image AS image, p.description AS description, p.stock AS stock, c.name as category
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE  p.id=$id
      
    ");
} catch (Exception $e) {
    $product = [];
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
            border: 2px solid #b2f9c2ff;
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
        .pruducts-table {
            width: 100%;
            border-collapse: collapse;
        }

        .pruducts-table th,
        .pruducts-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .pruducts-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        .checkout-btn {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #b91010ff 0%, #a52121ff 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.checkout-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
}

.checkout-btn:disabled {
    background: #9ca3af;
    cursor: not-allowed;
    transform: none;
}


    </style>
</head>
<body>
    <header >
        
        
    </header>

    <div class="container">
        <div class="form-card">
            <div class="form-header">
                <h2><i class="fas fa-box"></i> The new infos</h2>
                <p>those are the new Pruduct info</p>
            </div>

            <div>
               
            
                <div class="form-group">
                    <label for="full-name">Name</label>
                    <input type="text" id="name" value="<?php echo $product['name']?>" name="name" disabled>
                </div>

                <div class="form-group">
                    <label for="phone">Price</label>
                    <input type="text" id="price" value="<?php echo $product['price']?>" name="price" disabled>
                </div>

                <div class="form-group">
                    <label for="email">Category</label>
                    <input type="text" id="category" name="category" value="<?php echo $product['category']?>" disabled>
                </div>
                <div class="form-group">
                    <label for="email">Image</label>
                    <input type="text" id="image" name="image" value="<?php echo $product['image']?>" disabled>
                </div>

                <div class="form-group">
                    <label for="location">Description</label>
                    <textarea name="description" rows="3" disabled required><?php echo htmlspecialchars($product['description'] ?? '')  ?></textarea>
                </div>
                <a href="edit_product.php">
                <button type="submit" class="checkout-btn" name="update_product" >Back</button></a>
                </div>

         
            </div>        
      
       
        </div>
    </div>
    
    </body>
</html>