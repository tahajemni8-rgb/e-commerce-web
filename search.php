<?php
session_start();
require_once 'db_config.php';
// Dynamic products array
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

// Get dynamic data for filters
$categories = array_unique(array_column($products, 'category'));
$minPrice = min(array_column($products, 'price'));
$maxPrice = max(array_column($products, 'price'));


// Count products per category
$categoryCounts = [];
foreach ($products as $product) {
    $category = $product['category'];
    $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
}

// Handle search functionality with filters
$searchResults = [];
$searchQuery = '';
$selectedCategory = '';
$maxPriceFilter = isset($_GET['price']) ? floatval($_GET['price']) : $maxPrice;

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $selectedCategory = $_GET['category'];
}

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchQuery = trim($_GET['search']);
    $searchTerm = strtolower($searchQuery);

    // Filter products based on search term, category, and price
    foreach ($products as $product) {
        $productName = strtolower($product['name']);
        $productCategory = strtolower($product['category']);

        // Check if search term matches product name or category
        $matchesSearch = strpos($productName, $searchTerm) !== false || strpos($productCategory, $searchTerm) !== false;
        $matchesCategory = empty($selectedCategory) || $product['category'] === $selectedCategory;
        $matchesPrice = $product['price'] <= $maxPriceFilter;

        if ($matchesSearch && $matchesCategory && $matchesPrice) {
            $searchResults[] = $product;
        }
    }
} else {
    // If no search query, show all products (but still apply filters)
    $searchResults = array_filter($products, function($product) use ($selectedCategory, $maxPriceFilter) {
        $matchesCategory = empty($selectedCategory) || $product['category'] === $selectedCategory;
        $matchesPrice = $product['price'] <= $maxPriceFilter;
        return $matchesCategory && $matchesPrice;
    });
}
 if ($product['stock'] > 1): 
                                    $stockStatus = 'En stock';
                                else:
                                    $stockStatus = 'Épuisé';
                                endif; 
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
                                <div class="stock-status">
                                <div>
                                    ${product.stock > 0 ? '<span class="in-stock" ></span>' : '<span class="out-of-stock"></span>'}
                                    </div>
                            </div>
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
                <input type="text" id="search-input" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($searchQuery); ?>">
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

    <!-- Filter Toggle Button for Mobile -->
    <button class="filter-toggle-btn" id="filter-toggle-btn" aria-label="Toggle Filters">
        <span>🔍</span>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebar-overlay">
        <div class="sidebar-content">
            <button class="close-btn" id="close-sidebar">&times;</button>
            <aside class="sidebar">
                <h3>Prix</h3>
                <div class="price-filter">
                    <input type="range" min="<?php echo floor($minPrice); ?>" max="<?php echo ceil($maxPrice); ?>" value="<?php echo isset($_GET['price']) ? floatval($_GET['price']) : ceil($maxPrice); ?>" class="price-range" id="price-range-mobile">
                    <div class="price-display">
                        <span class="price-min"><?php echo floor($minPrice); ?> DT</span>
                        <span class="price-max" id="price-max-mobile"><?php echo isset($_GET['price']) ? floatval($_GET['price']) : ceil($maxPrice); ?> DT</span>
                    </div>
                </div>

                <h3>Catégories</h3>
                <div class="category-filter">
                    <label>
                        <input type="radio" name="category-mobile" value="" <?php echo empty($selectedCategory) ? 'checked' : ''; ?>>
                        Toutes les catégories <span class="count">(<?php echo count($products); ?>)</span>
                    </label>
                    <?php foreach ($categories as $category): ?>
                        <label>
                            <input type="radio" name="category-mobile" value="<?php echo $category; ?>" <?php echo $selectedCategory === $category ? 'checked' : ''; ?>>
                            <?php echo ucfirst($category); ?> <span class="count">(<?php echo $categoryCounts[$category]; ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <h3>Recherche</h3>
                <div class="search-filter">
                    <input type="text" id="sidebar-search-mobile" placeholder="Rechercher un produit..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="button" id="clear-search-mobile" class="clear-btn" style="display: <?php echo isset($_GET['search']) && !empty($_GET['search']) ? 'block' : 'none'; ?>;">×</button>
                </div>

                <div class="filter-actions">
                    <button class="apply-filters" id="apply-filters-mobile">Appliquer les filtres</button>
                    <button class="reset-filters" id="reset-filters-mobile">Réinitialiser les filtres</button>
                    <div class="active-filters" id="active-filters-mobile" style="display: none;">
                        <h4>Filtres actifs:</h4>
                        <div id="filter-tags-mobile"></div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
    
    <!-- Hero Section for dynamic feel -->
    
    
    <main class="container">
        <!-- Search Results Header -->
        <div class="search-results-header">
            <?php if (!empty($searchQuery) || !empty($selectedCategory)): ?>
                <h2>
                    <?php
                    if (!empty($searchQuery) && !empty($selectedCategory)) {
                        echo "Résultats pour \"$searchQuery\" dans " . ucfirst($selectedCategory);
                    } elseif (!empty($searchQuery)) {
                        echo "Résultats pour \"$searchQuery\"";
                    } elseif (!empty($selectedCategory)) {
                        echo "Produits " . ucfirst($selectedCategory);
                    }
                    ?>
                    <span style="font-size: 0.8em; color: #666;">(<?php echo count($searchResults); ?> produits)</span>
                </h2>
            <?php else: ?>
                <h2>Tous les Produits</h2>
                <p>Affichage de <?php echo count($searchResults); ?> produit(s)</p>
            <?php endif; ?>
        </div>

        <div class="products-page">
        <!-- Sidebar Filters -->
        <aside class="sidebar">
            <h3>Prix</h3>
            <div class="price-filter">
                <input type="range" min="<?php echo floor($minPrice); ?>" max="<?php echo ceil($maxPrice); ?>" value="<?php echo isset($_GET['price']) ? floatval($_GET['price']) : ceil($maxPrice); ?>" class="price-range" id="price-range">
                <div class="price-display">
                    <span class="price-min"><?php echo floor($minPrice); ?> DT</span>
                    <span class="price-max" id="price-max"><?php echo isset($_GET['price']) ? floatval($_GET['price']) : ceil($maxPrice); ?> DT</span>
                </div>
            </div>

            <h3>Catégories</h3>
            <div class="category-filter">
                <label>
                    <input type="radio" name="category" value="" <?php echo empty($selectedCategory) ? 'checked' : ''; ?>>
                    Toutes les catégories <span class="count">(<?php echo count($products); ?>)</span>
                </label>
                <?php foreach ($categories as $category): ?>
                    <label>
                        <input type="radio" name="category" value="<?php echo $category; ?>" <?php echo $selectedCategory === $category ? 'checked' : ''; ?>>
                        <?php echo ucfirst($category); ?> <span class="count">(<?php echo $categoryCounts[$category]; ?>)</span>
                    </label>
                <?php endforeach; ?>
            </div>

            <h3>Recherche</h3>
            <div class="search-filter">
                <input type="text" id="sidebar-search" placeholder="Rechercher un produit..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="button" id="clear-search" class="clear-btn" style="display: <?php echo isset($_GET['search']) && !empty($_GET['search']) ? 'block' : 'none'; ?>;">×</button>
            </div>

            <div class="filter-actions">
                <button class="apply-filters" id="apply-filters">Appliquer les filtres</button>
                <button class="reset-filters" id="reset-filters">Réinitialiser les filtres</button>
                <div class="active-filters" id="active-filters" style="display: none;">
                    <h4>Filtres actifs:</h4>
                    <div id="filter-tags"></div>
                </div>
            </div>
        </aside>

        <!-- Products Grid -->
        <section class="products-grid">
            <?php if (count($searchResults) > 0): ?>
               <?php foreach ($filteredProducts as $product): ?>
                <div class="product-card">
                    <a href="product_view.php?id=<?php echo $product['id']; ?>" class="product-link">
                        <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                    </a>
                    <div class="product-info">
                        <h4><?php echo $product['name']; ?></h4>
                        <p class="gender"><?php echo $product['category']; ?></p>
                    
                            <p class="price"><?php echo number_format($product['price'], 2); ?> DT</p>
                            <div class="stock-status"> 
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="in-stock">En stock</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Épuisé</span>
                                <?php endif; ?>
                           
                        
                        </div>
                        
                        <div class="options">
                            
                                <a href="product_view.php?id=<?php echo $product['id']; ?>" class="view-details-btn">Voir détails</a>
                                <button class="add-to-cart-btn" onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    Ajouter au panier
                                </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php else: ?>
                <div class="no-results">
                    <h3>No products found</h3>
                    <p>Try searching with different keywords or browse all products.</p>
                    <a href="search.php" class="btn">View All Products</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

    
    <footer>
        <p>&copy; 2025 MyShop. All rights reserved.</p>
    </footer>

    <script>
        // Enhanced sidebar functionality for search page
        document.addEventListener('DOMContentLoaded', function() {
            const categoryRadios = document.querySelectorAll('input[name="category"]');
            const priceRange = document.getElementById('price-range');
            const priceMaxDisplay = document.getElementById('price-max');
            const sidebarSearch = document.getElementById('sidebar-search');
            const clearSearchBtn = document.getElementById('clear-search');
            const resetButton = document.getElementById('reset-filters');
            const activeFiltersDiv = document.getElementById('active-filters');
            const filterTagsDiv = document.getElementById('filter-tags');
            const productsGrid = document.querySelector('.products-grid');
            const searchResultsHeader = document.querySelector('.search-results-header h2');

            // Store original products data
            const allProducts = <?php echo json_encode($products); ?>;

            // Price range slider functionality (visual only, no auto-filter)
            if (priceRange) {
                priceRange.addEventListener('input', function() {
                    priceMaxDisplay.textContent = this.value + ' DT';
                });
            }

            // Sidebar search functionality (visual only, no auto-filter)
            if (sidebarSearch) {
                sidebarSearch.addEventListener('input', function() {
                    clearSearchBtn.style.display = this.value ? 'block' : 'none';
                });
            }

            // Clear search button
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    sidebarSearch.value = '';
                    this.style.display = 'none';
                });
            }

            // Apply filters button
            const applyButton = document.getElementById('apply-filters');
            if (applyButton) {
                applyButton.addEventListener('click', function() {
                    filterProducts();
                    updateURL();
                });
            }

            // Reset filters
            if (resetButton) {
                resetButton.addEventListener('click', function() {
                    // Reset category
                    document.querySelector('input[name="category"][value=""]').checked = true;

                    // Reset price
                    if (priceRange) {
                        priceRange.value = priceRange.max;
                        priceMaxDisplay.textContent = priceRange.max + ' DT';
                    }

                    // Reset search
                    if (sidebarSearch) {
                        sidebarSearch.value = '';
                        clearSearchBtn.style.display = 'none';
                    }

                    filterProducts();
                    updateURL();
                });
            }

            // Filter products function
            function filterProducts() {
                const selectedCategory = document.querySelector('input[name="category"]:checked').value;
                const maxPrice = priceRange ? parseFloat(priceRange.value) : Infinity;
                const searchTerm = sidebarSearch ? sidebarSearch.value.toLowerCase() : '';

                let filtered = allProducts.filter(product => {
                    const matchesCategory = !selectedCategory || product.category === selectedCategory;
                    const matchesPrice = product.price <= maxPrice;
                    const matchesSearch = !searchTerm ||
                        product.name.toLowerCase().includes(searchTerm) ||
                        product.category.toLowerCase().includes(searchTerm);

                    return matchesCategory && matchesPrice && matchesSearch;
                });

                // Update products grid
                updateProductsGrid(filtered);

                // Update page title
                updatePageTitle(selectedCategory, filtered.length, searchTerm);

                // Update active filters
                updateActiveFilters(selectedCategory, maxPrice, searchTerm);
            }

            // Update products grid
            function updateProductsGrid(products) {
                if (!productsGrid) return;

                productsGrid.innerHTML = '';

                if (products.length === 0) {
                    productsGrid.innerHTML = '<div class="no-results"><h3>Aucun produit trouvé</h3><p>Essayez de rechercher avec des mots-clés différents ou parcourez tous les produits.</p><a href="search.php" class="btn">Voir tous les produits</a></div>';
                    return;
                }

                products.forEach(product => {
                    const productCard = document.createElement('div');
                    productCard.className = 'product-card';
                    productCard.innerHTML = `
                        <img src="${product.image}" alt="${product.name}">
                        <div class="product-info">
                            <h4>${product.name}</h4>
                            <p class="gender">${product.category}</p>
                            <p class="price">${product.price} DT</p>
                            <div class="options">
                            <a href="product_view.php?id=<?php echo $product['id']; ?>" class="view-details-btn">Voir détails</a>
                                <button class="add-to-cart-btn" onclick="addToCart(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                                    Ajouter au panier
                                </button>
                            </div>
                        </div>
                    `;
                    productsGrid.appendChild(productCard);
                });
            }

            // Update page title
            function updatePageTitle(category, count, searchTerm) {
                if (!searchResultsHeader) return;

                let titleText = '';
                if (searchTerm && category) {
                    titleText = `Résultats pour "${searchTerm}" dans ${category.charAt(0).toUpperCase() + category.slice(1)}`;
                } else if (searchTerm) {
                    titleText = `Résultats pour "${searchTerm}"`;
                } else if (category) {
                    titleText = `Produits ${category.charAt(0).toUpperCase() + category.slice(1)}`;
                } else {
                    titleText = 'Tous les Produits';
                }

                searchResultsHeader.innerHTML = `${titleText} <span style="font-size: 0.8em; color: #666;">(${count} produits)</span>`;
            }

            // Update active filters display
            function updateActiveFilters(category, maxPrice, searchTerm) {
                if (!filterTagsDiv || !activeFiltersDiv) return;

                const tags = [];
                const originalMaxPrice = parseFloat(priceRange.max);

                if (category) {
                    tags.push(`<span class="filter-tag">Catégorie: ${category} <span class="remove-tag" data-type="category">×</span></span>`);
                }

                if (maxPrice < originalMaxPrice) {
                    tags.push(`<span class="filter-tag">Prix max: ${maxPrice} DT <span class="remove-tag" data-type="price">×</span></span>`);
                }

                if (searchTerm) {
                    tags.push(`<span class="filter-tag">Recherche: "${searchTerm}" <span class="remove-tag" data-type="search">×</span></span>`);
                }

                if (tags.length > 0) {
                    filterTagsDiv.innerHTML = tags.join('');
                    activeFiltersDiv.style.display = 'block';

                    // Add event listeners to remove tags (UI only, requires Apply Filters click)
                    document.querySelectorAll('.remove-tag').forEach(tag => {
                        tag.addEventListener('click', function() {
                            const type = this.dataset.type;
                            if (type === 'category') {
                                document.querySelector('input[name="category"][value=""]').checked = true;
                            } else if (type === 'price') {
                                priceRange.value = originalMaxPrice;
                                priceMaxDisplay.textContent = originalMaxPrice + ' DT';
                            } else if (type === 'search') {
                                sidebarSearch.value = '';
                                clearSearchBtn.style.display = 'none';
                            }
                            // Update active filters display after UI change
                            updateActiveFilters(
                                document.querySelector('input[name="category"]:checked').value,
                                parseFloat(priceRange.value),
                                sidebarSearch ? sidebarSearch.value.toLowerCase() : ''
                            );
                        });
                    });
                } else {
                    activeFiltersDiv.style.display = 'none';
                }
            }

            // Update URL with current filters
            function updateURL() {
                const url = new URL(window.location);
                const category = document.querySelector('input[name="category"]:checked').value;
                const search = sidebarSearch ? sidebarSearch.value : '';
                const maxPrice = priceRange ? parseFloat(priceRange.value) : parseFloat(priceRange.max);

                if (category) {
                    url.searchParams.set('category', category);
                } else {
                    url.searchParams.delete('category');
                }

                if (search) {
                    url.searchParams.set('search', search);
                } else {
                    url.searchParams.delete('search');
                }

                if (maxPrice < parseFloat(priceRange.max)) {
                    url.searchParams.set('price', maxPrice);
                } else {
                    url.searchParams.delete('price');
                }

                // Update URL without reloading
                window.history.replaceState({}, '', url);
            }

            // Initialize
            filterProducts();

            // Mobile Filter Toggle
            const filterToggleBtn = document.getElementById('filter-toggle-btn');
            const sidebarOverlay = document.getElementById('sidebar-overlay');
            const closeSidebarBtn = document.getElementById('close-sidebar');

            console.log('Filter toggle button:', filterToggleBtn);
            console.log('Sidebar overlay:', sidebarOverlay);

            if (filterToggleBtn && sidebarOverlay) {
                filterToggleBtn.addEventListener('click', function() {
                    console.log('Filter toggle button clicked');
                    sidebarOverlay.classList.add('show');
                });

                closeSidebarBtn.addEventListener('click', function() {
                    console.log('Close button clicked');
                    sidebarOverlay.classList.remove('show');
                });

                sidebarOverlay.addEventListener('click', function(e) {
                    if (e.target === sidebarOverlay) {
                        console.log('Overlay clicked');
                        sidebarOverlay.classList.remove('show');
                    }
                });
            } else {
                console.log('Filter toggle button or overlay not found');
            }

            // Mobile Filter Functionality
            const categoryRadiosMobile = document.querySelectorAll('input[name="category-mobile"]');
            const priceRangeMobile = document.getElementById('price-range-mobile');
            const priceMaxDisplayMobile = document.getElementById('price-max-mobile');
            const sidebarSearchMobile = document.getElementById('sidebar-search-mobile');
            const clearSearchBtnMobile = document.getElementById('clear-search-mobile');
            const applyButtonMobile = document.getElementById('apply-filters-mobile');
            const resetButtonMobile = document.getElementById('reset-filters-mobile');

            // Price range for mobile
            if (priceRangeMobile) {
                priceRangeMobile.addEventListener('input', function() {
                    if (priceMaxDisplayMobile) {
                        priceMaxDisplayMobile.textContent = this.value + ' DT';
                    }
                });
            }

            // Search for mobile
            if (sidebarSearchMobile) {
                sidebarSearchMobile.addEventListener('input', function() {
                    if (clearSearchBtnMobile) {
                        clearSearchBtnMobile.style.display = this.value ? 'block' : 'none';
                    }
                });
            }

            if (clearSearchBtnMobile) {
                clearSearchBtnMobile.addEventListener('click', function() {
                    if (sidebarSearchMobile) {
                        sidebarSearchMobile.value = '';
                        this.style.display = 'none';
                    }
                });
            }

            if (applyButtonMobile) {
                applyButtonMobile.addEventListener('click', function() {
                    // Get values from mobile controls
                    const selectedCategoryRadio = document.querySelector('input[name="category-mobile"]:checked');
                    const selectedCategory = selectedCategoryRadio ? selectedCategoryRadio.value : '';
                    const maxPrice = priceRangeMobile ? parseFloat(priceRangeMobile.value) : Infinity;
                    const searchTerm = sidebarSearchMobile ? sidebarSearchMobile.value : '';

                    // Update URL
                    const url = new URL(window.location);
                    if (selectedCategory) {
                        url.searchParams.set('category', selectedCategory);
                    } else {
                        url.searchParams.delete('category');
                    }
                    if (searchTerm) {
                        url.searchParams.set('search', searchTerm);
                    } else {
                        url.searchParams.delete('search');
                    }
                    if (maxPrice < (priceRangeMobile ? parseFloat(priceRangeMobile.max) : Infinity)) {
                        url.searchParams.set('price', maxPrice);
                    } else {
                        url.searchParams.delete('price');
                    }

                    // Reload page with new filters
                    window.location.href = url.toString();
                });
            }

            if (resetButtonMobile) {
                resetButtonMobile.addEventListener('click', function() {
                    // Reset to defaults
                    document.querySelector('input[name="category-mobile"][value=""]').checked = true;
                    if (priceRangeMobile) {
                        priceRangeMobile.value = priceRangeMobile.max;
                        if (priceMaxDisplayMobile) {
                            priceMaxDisplayMobile.textContent = priceRangeMobile.max + ' DT';
                        }
                    }
                    if (sidebarSearchMobile) {
                        sidebarSearchMobile.value = '';
                        if (clearSearchBtnMobile) {
                            clearSearchBtnMobile.style.display = 'none';
                        }
                    }
                    // Close overlay
                    sidebarOverlay.classList.remove('show');
                    // Reset URL
                    const url = new URL(window.location);
                    url.searchParams.delete('category');
                    url.searchParams.delete('search');
                    url.searchParams.delete('price');
                    window.location.href = url.toString();
                });
            }
        });
    </script>
    <script src="assets/js/navigation.js"></script>
    <?php include 'cart.php'; ?>
</body>
</html>