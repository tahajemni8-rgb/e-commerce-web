<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Ecommerce Shopping Site</title>
    <link rel="stylesheet" href="assets/css/style2.css">
    <?php
    // Products data for autocomplete
    $products = [
        ['id' => 1, 'name' => 'T-Shirt Homme Blanc', 'price' => 25.99, 'image' => 'assets/images/none.jpg','category' => 'homme'],
        ['id' => 2, 'name' => 'Jean Slim Femme', 'price' => 45.99, 'image' => 'assets/images/none.jpg','category' => 'femme'],
        ['id' => 3, 'name' => 'Robe d\'Été', 'price' => 35.99, 'image' => 'assets/images/none.jpg','category' => 'femme'],
        ['id' => 4, 'name' => 'Pull Enfant', 'price' => 20.99, 'image' => 'assets/images/none.jpg','category' => 'enfant'],
        ['id' => 5, 'name' => 'Chaussures de Sport Homme', 'price' => 65.99, 'image' => 'assets/images/none.jpg','category' => 'homme'],
        ['id' => 6, 'name' => 'Veste Femme', 'price' => 75.99, 'image' => 'assets/images/none.jpg','category' => 'femme'],
        ['id' => 7, 'name' => 'Short Enfant', 'price' => 15.99, 'image' => 'assets/images/none.jpg','category' => 'enfant'],
        ['id' => 8, 'name' => 'Chemise Homme', 'price' => 40.99, 'image' => 'assets/images/none.jpg','category' => 'homme'],
    ];
    ?>
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
                    cartCount.classList.add('empty');
                }
            };
        });
    </script>
    <style>
        /* Hero Section */
        .about-hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 20px;
        }

        .about-hero h1 {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        /* Content Container */
        .container2 {
            max-width: 1100px;
            margin: -50px auto 50px;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .intro-text {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 40px;
        }

        .intro-text h2 {
            color: #10b981; /* Matches your About button color */
            margin-bottom: 20px;
        }

        /* Values Grid */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .value-card {
            text-align: center;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-5px);
            border-color: #10b981;
        }

        .value-card h3 {
            margin: 15px 0;
            color: #2d3748;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .about-hero h1 { font-size: 2rem; }
            .container { margin-top: 20px; width: 95%;
            margin:auto; }
        }
    </style>
</head>
<body>
    <header>
        <nav class="container">
            <img src="assets/images/logo.png" alt="" class="logo-img">

            <form class="search-form" action="search.php" method="get">
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
        <!-- Add your about page content here -->
        <div class="container">
            <section class="about-hero">
        <div>
            <h1>Our Story</h1>
            <p>Driven by Passion, Defined by Quality.</p>
        </div>
    </section>

    <div class="container2">
        <div class="intro-text">
            <h2>Welcome to [Your Brand Name]</h2>
            <p>
                Founded in 2024, our journey began with a simple observation: the world needed a more 
                honest way to shop. We don’t just sell products; we curate solutions that fit your lifestyle. 
                Our team works tirelessly to source items that balance modern design with everyday durability.
            </p>
        </div>

        <hr style="border: 0; border-top: 1px solid #eee;">

        <div class="values-grid">
            <div class="value-card">
                <h3>Our Mission</h3>
                <p>To provide high-quality essentials that empower our customers to live their best lives every day.</p>
            </div>
            <div class="value-card">
                <h3>Our Vision</h3>
                <p>To become the world's most trusted destination for [Your Industry] through transparency and innovation.</p>
            </div>
            <div class="value-card">
                <h3>Core Values</h3>
                <p>Integrity, Customer Obsession, and Sustainability are at the heart of every decision we make.</p>
            </div>
        </div>
    </div>
    </main>
    <script src="assets/js/navigation.js"></script>
    <?php include 'cart.php'; ?>
</body>
</html>
