<?php
session_start();
// Dynamic products array (for consistency, though not used on contact page)
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Ecommerce Shopping Site</title>
    <link rel="stylesheet" href="assets/css/style2.css">
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

    <main class="container">
        <div class="contact-page">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you! Send us a message and we'll get back to you as soon as possible.</p>

            <div class="contact-content">
                <div class="contact-form">
                    <h2>Send us a message</h2>
                    <div id="form-message" style="display: none; margin-bottom: 20px; padding: 10px; border-radius: 5px;"></div>
                    <form id="contact-form" action="process_contact.php" method="post">
                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" id="subject" name="subject" required>
                        </div>

                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>

                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                </div>

                <div class="contact-info">
                    <h2>Get in touch</h2>
                    <div class="info-item">
                        <h3>Address</h3>
                        <p>123 Shopping Street<br>Tunis, Tunisia</p>
                    </div>

                    <div class="info-item">
                        <h3>Phone</h3>
                        <p>+216 XX XXX XXX</p>
                    </div>

                    <div class="info-item">
                        <h3>Email</h3>
                        <p>contact@myshop.com</p>
                    </div>

                    <div class="info-item">
                        <h3>Hours</h3>
                        <p>Mon - Fri: 9:00 AM - 6:00 PM<br>Sat: 10:00 AM - 4:00 PM</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 MyShop. All rights reserved.</p>
    </footer>

    <?php include 'cart.php'; ?>

    <script>
    document.getElementById('contact-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('.submit-btn');
        const messageDiv = document.getElementById('form-message');

        // Disable button and show loading
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        fetch('process_contact.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text(); // Get as text first
        })
        .then(text => {
            console.log('Raw response text:', text);
            try {
                const data = JSON.parse(text);
                console.log('Parsed JSON data:', data);
                messageDiv.style.display = 'block';
                if (data.success) {
                    messageDiv.className = 'success-message';
                    messageDiv.textContent = '✅ ' + data.message;
                    document.getElementById('contact-form').reset(); // Clear form
                    // Hide success message after 5 seconds
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 5000);
                } else {
                    messageDiv.className = 'error-message';
                    messageDiv.textContent = '❌ ' + data.message;
                }
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response was not valid JSON:', text);
                // If we received the email but JSON failed, show success anyway
                if (text.includes('sent successfully') || text.includes('Message sent')) {
                    messageDiv.style.display = 'block';
                    messageDiv.className = 'success-message';
                    messageDiv.textContent = '✅ Message sent successfully!';
                    document.getElementById('contact-form').reset();
                } else {
                    messageDiv.style.display = 'block';
                    messageDiv.className = 'error-message';
                    messageDiv.textContent = 'Server response error. Please check if you received the email.';
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            messageDiv.style.display = 'block';
            messageDiv.className = 'error-message';
            messageDiv.textContent = 'Connection error. Please check your internet and try again.';
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Send Message';
        });
    });
    </script>

    <style>
    .success-message {
        background-color: #d4edda;
        color: #155724;
        border: 2px solid #c3e6cb;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        font-weight: bold;
        text-align: center;
    }
    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        border: 2px solid #f5c6cb;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        font-weight: bold;
        text-align: center;
    }
    </style>
    <script src="assets/js/navigation.js"></script>

</body>
</html>