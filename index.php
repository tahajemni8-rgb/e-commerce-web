<?php
require_once 'db_config.php';

// Load ads from database
try {
    $ads = getMultipleRows("SELECT image, title, description, link, link_text as linkText, badge FROM announcements ORDER BY created_at DESC");
} catch (Exception $e) {
    // Fallback to empty array if database error
    $ads = [];
}

// Load featured products from database
try {
    $products = getMultipleRows("
        SELECT p.id, p.name, p.price, p.image, c.name as category
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.stock > 0
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
} catch (Exception $e) {
    // Fallback to empty array if database error
    $products = [];
}

try {
    $category = getMultipleRows("
        SELECT DISTINCT c.name 
        FROM categories c
        JOIN products p ON c.id = p.category_id;    
    ");
} catch (Exception $e) {
    // Fallback to empty array if database error
    $category = [];
}




?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ecommerce Shopping Site</title>
    <link rel="stylesheet" href="assets/css/style2.css">
    <script>
        // Products data for autocomplete
        const products = <?php echo json_encode($products); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const searchForm = document.querySelector('.search-form');
            
            // Create suggestions container
            const suggestionsContainer = document.createElement('div');
            suggestionsContainer.id = 'search-suggestions';
            suggestionsContainer.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-top: none;
                border-radius: 0 0 4px 4px;
                max-height: 300px;
                overflow-y: auto;
                z-index: 1000;
                display: none;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            `;
            searchForm.style.position = 'relative';
            searchForm.appendChild(suggestionsContainer);
            
            let currentFocus = -1;
            
            searchInput.addEventListener('input', function(e) {
                const value = e.target.value.toLowerCase();
                suggestionsContainer.innerHTML = '';
                currentFocus = -1;
                
                if (value.length === 0) {
                    suggestionsContainer.style.display = 'none';
                    return;
                }
                
                const matches = products.filter(product => 
                    product.name.toLowerCase().includes(value) || 
                    product.category.toLowerCase().includes(value)
                ).slice(0, 4); // Limit to 4 suggestions
                
                if (matches.length > 0) {
                    matches.forEach((product, index) => {
                        const suggestion = document.createElement('div');
                        suggestion.style.cssText = `
                            padding: 10px;
                            cursor: pointer;
                            border-bottom: 1px solid #eee;
                            display: flex;
                            align-items: center;
                        `;
                        suggestion.innerHTML = `
                            <img src="${product.image}" alt="${product.name}" style="width: 40px; height: 40px; object-fit: cover; margin-right: 10px; border-radius: 4px;">
                            <div>
                                <div style="font-weight: bold;">${product.name}</div>
                                <div style="color: #666; font-size: 12px;">${product.category} - ${product.price} DT</div>
                            </div>
                        `;
                        
                        suggestion.addEventListener('click', function() {
                            searchInput.value = product.name;
                            suggestionsContainer.style.display = 'none';
                            searchForm.submit();
                        });
                        
                        suggestion.addEventListener('mouseenter', function() {
                            this.style.backgroundColor = '#f5f5f5';
                        });
                        
                        suggestion.addEventListener('mouseleave', function() {
                            this.style.backgroundColor = 'white';
                        });
                        
                        suggestionsContainer.appendChild(suggestion);
                    });
                    suggestionsContainer.style.display = 'block';
                } else {
                    suggestionsContainer.style.display = 'none';
                }
            });
            
            // Handle keyboard navigation
            searchInput.addEventListener('keydown', function(e) {
                const suggestions = suggestionsContainer.querySelectorAll('div');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    currentFocus = currentFocus < suggestions.length - 1 ? currentFocus + 1 : 0;
                    updateFocus(suggestions);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    currentFocus = currentFocus > 0 ? currentFocus - 1 : suggestions.length - 1;
                    updateFocus(suggestions);
                } else if (e.key === 'Enter' && currentFocus >= 0) {
                    e.preventDefault();
                    suggestions[currentFocus].click();
                } else if (e.key === 'Escape') {
                    suggestionsContainer.style.display = 'none';
                    currentFocus = -1;
                }
            });
            
            function updateFocus(suggestions) {
                suggestions.forEach((suggestion, index) => {
                    if (index === currentFocus) {
                        suggestion.style.backgroundColor = '#e3f2fd';
                    } else {
                        suggestion.style.backgroundColor = 'white';
                    }
                });
            }
            
            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchForm.contains(e.target)) {
                    suggestionsContainer.style.display = 'none';
                    currentFocus = -1;
                }
            });
            
            // Add loading state to search button
            const searchButton = searchForm.querySelector('button[type="submit"]');
            searchForm.addEventListener('submit', function() {
                searchButton.classList.add('loading');
                searchButton.innerHTML = '<span>Recherche...</span>';
            });
            
            // Cart functionality
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
                    cartCount.textContent = '(0)';
                    cartCount.classList.add('empty');
                }
            };
        });
    </script>
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

    <div id="annouce-container">
        <button class="slide-arrow prev-arrow" id="prevBtn" aria-label="Previous slide">&#10094;</button>
        <button class="slide-arrow next-arrow" id="nextBtn" aria-label="Next slide">&#10095;</button>

        <?php foreach($ads as $index => $ad): ?>
            <div class="annouce-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                <div class="slide-image">
                    <img src="<?php echo $ad['image']; ?>" alt="<?php echo $ad['title']; ?>">
                    <?php if(isset($ad['badge'])): ?>
                        <div class="slide-badge"><?php echo $ad['badge']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="slide-content">
                    <div class="slide-text">
                        <h2><?php echo $ad['title']; ?></h2>
                        <p><?php echo $ad['description']; ?></p>
                        <a href="<?php echo $ad['link']; ?>" class="slide-btn"><?php echo $ad['linkText']; ?></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div id="slide-indicators" class="slide-indicators"></div>
        <div class="slide-progress">
            <div class="progress-bar" id="progressBar"></div>
        </div>
    </div>

    <!-- Categories Grid -->
    <section class="categories-grid">
        <h2 class="section-h2">Découvrez nos catégories</h2>
        <div class="categories-container">
          
            <a href="product.php?category=homme" class="category-card">
                <div class="category-image">
                    <img src="assets/images/none.jpg" alt="Homme">
                </div>
                <div class="category-info">
                    <h3>Homme</h3>
                    <p>Découvrez notre collection pour hommes</p>
                </div>
            </a>
            
            <a href="product.php?category=femme" class="category-card">
                <div class="category-image">
                    <img src="assets/images/none.jpg" alt="Femme">
                </div>
                <div class="category-info">
                    <h3>Femme</h3>
                    <p>Découvrez notre collection pour femmes</p>
                </div>
            </a>
            
            <a href="product.php?category=enfant" class="category-card">
                <div class="category-image">
                    <img src="assets/images/none.jpg" alt="Enfant">
                </div>
                <div class="category-info">
                    <h3>Enfant</h3>
                    <p>Découvrez notre collection pour enfants</p>
                </div>
            </a>
        </div>
    </section>
        

    <script src="assets/js/slider.js"></script>
    <script src="assets/js/navigation.js"></script>
    <?php include 'cart.php'; ?>
    </body>
    </html>