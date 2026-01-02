<?php
session_start();
require_once 'db_config.php';
// Get product ID from URL
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Products data (same as in product.php for consistency)
try {
    $products = getMultipleRows("
        SELECT p.id, p.name, p.price, p.image, p.description, p.stock, c.name as category
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.stock >= 0
        ORDER BY p.created_at DESC
    ");
} catch (Exception $e) {
    $products = [];
}
// Find the product by ID
$product = null;
foreach ($products as $p) {
    if ($p['id'] == $productId) {
        $product = $p;
        break;
    }
}

// If product not found, redirect to products page
if (!$product) {
    header('Location: product.php');
    exit();
}

// Get related products (same category, excluding current product)
$relatedProducts = array_filter($products, function($p) use ($product) {
    return $p['id'] != $product['id'] && $p['category'] == $product['category'];
});
$relatedProducts = array_slice($relatedProducts, 0, 4); // Limit to 4 related products
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - MyShop</title>
    <link rel="stylesheet" href="assets/css/style2.css">
    <style>
        .product-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        .product-image {
            text-align: center;
        }

        .product-image img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .product-info-detail {
            padding: 20px;
        }

        .product-info-detail h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #1f2937;
        }

        .product-category {
            color: #6b7280;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .product-price-detail {
            font-size: 2rem;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 30px;
        }

        .product-description {
            line-height: 1.6;
            margin-bottom: 30px;
            color: #4b5563;
        }

        .product-specs {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .product-specs h3 {
            margin-bottom: 15px;
            color: #1f2937;
        }

        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .spec-item:last-child {
            border-bottom: none;
        }

        .spec-label {
            font-weight: 600;
            color: #374151;
        }

        .spec-value {
            color: #6b7280;
        }

        .stock-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .stock-in {
            background: #d1fae5;
            color: #065f46;
        }

        .stock-low {
            background: #fef3c7;
            color: #92400e;
        }

        .stock-out {
            background: #fee2e2;
            color: #991b1b;
        }

        .add-to-cart-section {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .add-to-cart-btn-detail {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn-detail:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .add-to-cart-btn-detail:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .back-btn {
            background: #6b7280;
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .related-products {
            margin-top: 60px;
        }

        .related-products h2 {
            text-align: center;
            margin-bottom: 40px;
            color: #1f2937;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .related-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .related-card:hover {
            transform: translateY(-5px);
        }

        .related-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .related-card-info {
            padding: 15px;
        }

        .related-card h4 {
            margin: 0 0 8px 0;
            font-size: 1.1rem;
        }

        .related-card .price {
            color: #10b981;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .related-card .category {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .breadcrumb {
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: #6b7280;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: #10b981;
        }

        .breadcrumb span {
            color: #9ca3af;
            margin: 0 8px;
        }

        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .product-info-detail h1 {
                font-size: 2rem;
            }

            .add-to-cart-section {
                flex-direction: column;
                align-items: stretch;
            }

            .related-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>

    <style>
/* Spam blocker styles */
button:disabled,
.add-to-cart-btn-detail:disabled {
    opacity: 0.5 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}

#spam-countdown {
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
</head>
<body>
    <header>
        <nav class="container">
            <img src="assets/images/logo.png" alt="" class="logo-img">

            <form class="search-form" action="search.php" method="get" autocomplete="off">
                <input type="text" id="search-input" name="search" placeholder="Search products...">
                <button type="submit">Search</button>
            </form>
            <a href="#" class="cart-link">
                <img src="assets/images/cart-logo.png" alt="Cart" class="cart-icon">
                <span class="cart-text">Cart</span>
                <span class="cart-count empty">(0)</span>
            </a>
        </nav>
        <div class="nav-links-container">
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="nav-links" id="nav-links">
                <?php
                $current_page = basename($_SERVER['PHP_SELF']);
                $nav_links = [
                    'index.php' => 'Home',
                    'product.php' => 'Products',
                    'about.php' => 'About',
                    'contact.php' => 'Contact',

                ];

                foreach ($nav_links as $page => $label) {
                    $active_class = ($current_page === $page) ? 'active' : '';
                    echo "<li><a href=\"$page\" class=\"$active_class\">$label</a></li>";
                }
                ?>
            </ul>
        </div>
    </header>

    <main>
        <div class="product-detail-container">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php">Accueil</a>
                <span>></span>
                <a href="product.php">Produits</a>
                <span>></span>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </div>

            <!-- Product Detail -->
            <div class="product-detail">
                <div class="product-image">
                    <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                </div>

                <div class="product-info-detail">
                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="product-category"><?php echo ucfirst($product['category']); ?></p>
                    <p class="product-price-detail"><?php echo number_format($product['price'], 2); ?> DT</p>

                    <div class="stock-status <?php
                        if ($product['stock'] > 20) echo 'stock-in';
                        elseif ($product['stock'] > 0) echo 'stock-low';
                        else echo 'stock-out';
                    ?>">
                        <?php
                        if ($product['stock'] > 20) echo 'En stock';
                        elseif ($product['stock'] > 0) echo 'Stock faible (' . $product['stock'] . ')';
                        else echo 'Rupture de stock';
                        ?>
                    </div>

                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>

                    <div class="product-specs">
                        <h3>Spécifications</h3>
                        <div class="spec-item">
                            <span class="spec-label"><?php echo $product['description']; ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Stock disponible:</span>
                            <span class="spec-value"><?php echo $product['stock']; ?> unités</span>
                        </div>
                    </div>

                    <div class="add-to-cart-section">
                        <button class="add-to-cart-btn-detail"
                                onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                            <?php echo $product['stock'] <= 0 ? 'Rupture de stock' : 'Ajouter au panier'; ?>
                        </button>
                        <a href="product.php" class="back-btn">Retour aux produits</a>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <?php if (!empty($relatedProducts)): ?>
            <section class="related-products">
                <h2>Produits similaires</h2>
                <div class="related-grid">
                    <?php foreach ($relatedProducts as $related): ?>
                    <div class="related-card">
                        <a href="product_view.php?id=<?php echo $related['id']; ?>">
                            <img src="<?php echo $related['image']; ?>" alt="<?php echo $related['name']; ?>">
                        </a>
                        <div class="related-card-info">
                            <h4><?php echo htmlspecialchars($related['name']); ?></h4>
                            <p class="price"><?php echo number_format($related['price'], 2); ?> DT</p>
                            <p class="category"><?php echo ucfirst($related['category']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 MyShop. All rights reserved.</p>
    </footer>

    <script>
        // Update cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            updateCart(cart.length);
        });

        // Update cart count function
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const count = cart.length;
            updateCart(count);
        }

        // Update cart display function (matches other pages)
        window.updateCart = function(newCount) {
            const cartLink = document.querySelector('.cart-link');
            const cartCount = document.querySelector('.cart-count');

            if (newCount > 0) {
                cartCount.textContent = `(${newCount})`;
                cartCount.classList.remove('empty');

                // Trigger bounce animation
                cartLink.classList.add('added');
                setTimeout(() => {
                    cartLink.classList.remove('added');
                }, 600);
            } else {
                cartCount.classList.add('empty');
            }
        };

        // Add to cart function
        function addToCart(product) {
            // Check spam blocker for add-to-cart actions
            if (spamBlocker.checkDisabledState()) {
                showNotification('🚫 Actions désactivées temporairement.', 'error');
                return;
            }

            // Check for rapid add-to-cart attempts
            const addToCartKey = 'add_to_cart_attempts';
            let addAttempts = JSON.parse(localStorage.getItem(addToCartKey)) || [];
            const now = Date.now();
            const timeWindow = 10000; // 10 seconds
            const maxAddAttempts = 5;

            // Clean old attempts
            addAttempts = addAttempts.filter(attempt => now - attempt < timeWindow);
            addAttempts.push(now);
            localStorage.setItem(addToCartKey, JSON.stringify(addAttempts));

            if (addAttempts.length >= maxAddAttempts) {
                spamBlocker.showWarning();
                if (spamBlocker.warningShown) {
                    spamBlocker.disableButtons(30000); // 30 seconds for add-to-cart spam
                    return;
                }
            }

            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            cart.push(product);
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCart(cart.length);

            // Show success message
            showNotification('Produit ajouté au panier!', 'success');
        }

        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : '#ef4444'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 10001;
                animation: slideInRight 0.3s ease;
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Spam blocker functionality
        let spamBlocker = {
            attempts: JSON.parse(localStorage.getItem('spam_attempts')) || [],
            warningShown: false,
            disabledUntil: parseInt(localStorage.getItem('buttons_disabled_until')) || 0,
            timeoutId: null,

            // Check if user is spamming
            isSpamming: function() {
                const now = Date.now();
                const timeWindow = 30000; // 30 seconds
                const maxAttempts = 3;

                // Clean old attempts
                this.attempts = this.attempts.filter(attempt => now - attempt < timeWindow);

                return this.attempts.length >= maxAttempts;
            },

            // Record an attempt
            recordAttempt: function() {
                this.attempts.push(Date.now());
                localStorage.setItem('spam_attempts', JSON.stringify(this.attempts));
            },

            // Show spam warning
            showWarning: function() {
                if (!this.warningShown) {
                    showNotification('⚠️ Arrêtez de spammer! Vous risquez d\'être bloqué temporairement.', 'error');
                    this.warningShown = true;
                }
            },

            // Disable all buttons with timer
            disableButtons: function(duration = 60000) { // 60 seconds default
                const now = Date.now();
                this.disabledUntil = now + duration;
                localStorage.setItem('buttons_disabled_until', this.disabledUntil.toString());

                // Disable all buttons
                this.disableAllButtons();

                // Start countdown
                this.startCountdown();

                showNotification(`🚫 Boutons désactivés pour ${duration/1000} secondes en raison du spam.`, 'error');
            },

            // Disable all interactive buttons
            disableAllButtons: function() {
                const buttons = document.querySelectorAll('button, .add-to-cart-btn-detail');
                buttons.forEach(btn => {
                    btn.disabled = true;
                    btn.style.opacity = '0.5';
                    btn.style.cursor = 'not-allowed';
                    btn.setAttribute('data-original-text', btn.textContent);
                    btn.textContent = 'Désactivé';
                });
            },

            // Enable all buttons
            enableAllButtons: function() {
                const buttons = document.querySelectorAll('button, .add-to-cart-btn-detail');
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '';
                    btn.style.cursor = '';
                    const originalText = btn.getAttribute('data-original-text');
                    if (originalText) {
                        btn.textContent = originalText;
                        btn.removeAttribute('data-original-text');
                    }
                });
            },

            // Start countdown timer
            startCountdown: function() {
                const countdownElement = document.createElement('div');
                countdownElement.id = 'spam-countdown';
                countdownElement.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #ef4444;
                    color: white;
                    padding: 15px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    z-index: 10001;
                    font-weight: bold;
                `;
                document.body.appendChild(countdownElement);

                const updateCountdown = () => {
                    const remaining = Math.ceil((this.disabledUntil - Date.now()) / 1000);
                    if (remaining > 0) {
                        countdownElement.textContent = `🚫 Boutons désactivés: ${remaining}s`;
                        this.timeoutId = setTimeout(updateCountdown, 1000);
                    } else {
                        this.enableAllButtons();
                        countdownElement.remove();
                        localStorage.removeItem('buttons_disabled_until');
                        this.disabledUntil = 0;
                        showNotification('✅ Boutons réactivés. Veuillez ne pas spammer.', 'success');
                    }
                };
                updateCountdown();
            },

            // Check if buttons should be disabled
            checkDisabledState: function() {
                const now = Date.now();
                if (now < this.disabledUntil) {
                    this.disableAllButtons();
                    this.startCountdown();
                    return true;
                }
                return false;
            },

            // Initialize spam blocker
            init: function() {
                // Check if buttons should still be disabled
                this.checkDisabledState();

                // Clean old attempts on page load
                const now = Date.now();
                this.attempts = this.attempts.filter(attempt => now - attempt < 300000); // Keep 5 minutes
                localStorage.setItem('spam_attempts', JSON.stringify(this.attempts));
            }
        };

        // Initialize spam blocker
        spamBlocker.init();

        // Add notification animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
    <script src="assets/js/navigation.js"></script>
</body>
</html>
