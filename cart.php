<!-- Cart Modal -->
<div id="cart-modal" class="cart-modal">
    <div class="cart-modal-content">
        <div class="cart-modal-header">
            <h2>Mon Panier</h2>
            <span class="cart-close">&times;</span>
        </div>

        <div class="cart-items" id="cart-items">
            <!-- Cart items will be populated here -->
            <div class="empty-cart">
                <p>Votre panier est vide</p>
                <img src="assets/images/empty-cart.png" alt="Empty Cart" style="max-width: 150px; opacity: 0.5;">
            </div>
        </div>

        <div class="cart-summary" id="cart-summary" style="display: none;">
            <div class="cart-totals">
                <div class="total-row">
                    <span>Sous-total:</span>
                    <span id="subtotal">0.00 DT</span>
                </div>
                <div class="total-row">
                    <span>Frais de livraison:</span>
                    <span id="delivery-fee">7.00 DT</span>
                </div>
                <div class="total-row total">
                    <span>Total:</span>
                    <span id="total">7.00 DT</span>
                </div>
            </div>
        </div>

        <div class="buyer-info">
            <h3>Informations de livraison</h3>
            <form id="checkout-form">
                <div class="form-group">
                    <label for="full-name">Nom complet *</label>
                    <input type="text" id="full-name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="phone">Téléphone *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="location">Adresse de livraison *</label>
                    <textarea id="location" name="location" rows="3" placeholder="Adresse complète..." required></textarea>
                </div>

                <button type="submit" class="checkout-btn" disabled>Commander</button>
            </form>
        </div>
    </div>
</div>

<style>
/* Cart Modal Styles */
.cart-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease;
    align-items: center;
    justify-content: center;
}

.cart-modal-content {
    background-color: #fff;
    margin: 0;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        transform: translateY(-50px) scale(0.9);
        opacity: 0;
    }
    to {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
}

.cart-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    border-bottom: 1px solid #eee;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 15px 15px 0 0;
}

.cart-modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.cart-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
}

.cart-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.cart-items {
    padding: 20px 25px;
    max-height: 300px;
    overflow-y: auto;
}

.empty-cart {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-cart p {
    font-size: 1.1rem;
    margin-bottom: 20px;
}

.cart-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 15px;
}

.cart-item-details {
    flex: 1;
}

.cart-item-details h4 {
    margin: 0 0 5px 0;
    font-size: 1rem;
    color: #333;
}

.cart-item-details p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.cart-item-price {
    font-weight: 600;
    color: #1e40af;
}

.cart-item-remove {
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.3s ease;
}

.cart-item-remove:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.cart-summary {
    padding: 20px 25px;
    background: #f8f9fa;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.cart-totals {
    max-width: 300px;
    margin: 0 auto;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 0.95rem;
}

.total-row.total {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e40af;
    border-top: 2px solid #1e40af;
    padding-top: 10px;
    margin-top: 10px;
}

.buyer-info {
    padding: 20px 25px;
}

.buyer-info h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 1.2rem;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #1e40af;
    box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
}

.checkout-btn {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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

/* Responsive */
@media (max-width: 768px) {
    .cart-modal-content {
        width: 95%;
        margin: 5% auto;
        max-height: 95vh;
    }

    .cart-modal-header {
        padding: 15px 20px;
    }

    .cart-items,
    .buyer-info {
        padding: 15px 20px;
    }
}

/* Spam blocker styles */
button:disabled,
input[type="submit"]:disabled,
.add-to-cart-btn:disabled {
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

<script>
// Cart Modal Functionality
document.addEventListener('DOMContentLoaded', function() {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let deliveryFee = 7.00;

    // Initialize cart modal
    function initCartModal() {
        // Update cart count in header
        updateCartCount();

        // Add click event to cart buttons
        document.querySelectorAll('.cart-link').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                openCartModal();
            });
        });
    }

    // Open cart modal
    function openCartModal() {
        const modal = document.getElementById('cart-modal');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        renderCartItems();
        updateTotals();
    }

    // Close cart modal
    function closeCartModal() {
        const modal = document.getElementById('cart-modal');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Render cart items
    function renderCartItems() {
        const cartItemsContainer = document.getElementById('cart-items');
        const cartSummary = document.getElementById('cart-summary');

        if (cart.length === 0) {
            cartItemsContainer.innerHTML = `
                <div class="empty-cart">
                    <p>Votre panier est vide</p>
                    <img src="assets/images/empty-cart.png" alt="Empty Cart" style="max-width: 150px; opacity: 0.5;">
                </div>
            `;
            cartSummary.style.display = 'none';
            return;
        }

        cartSummary.style.display = 'block';
        let itemsHTML = '';

        cart.forEach((item, index) => {
            itemsHTML += `
                <div class="cart-item">
                    <img src="${item.image}" alt="${item.name}">
                    <div class="cart-item-details">
                        <h4>${item.name}</h4>
                        <p>${item.category}</p>
                    </div>
                    <div class="cart-item-price">${item.price} DT</div>
                    <button class="cart-item-remove" onclick="removeFromCart(${index})">×</button>
                </div>
            `;
        });

        cartItemsContainer.innerHTML = itemsHTML;
    }

    // Update totals
    function updateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + parseFloat(item.price), 0);
        const total = subtotal + deliveryFee;

        document.getElementById('subtotal').textContent = subtotal.toFixed(2) + ' DT';
        document.getElementById('total').textContent = total.toFixed(2) + ' DT';

        // Update checkout button state
        updateCheckoutButtonState();
    }

    // Update checkout button state based on cart and form validation
    function updateCheckoutButtonState() {
        const checkoutBtn = document.querySelector('.checkout-btn');
        const hasItems = cart.length > 0;
        const formValid = validateCheckoutForm();

        if (hasItems && formValid) {
            checkoutBtn.disabled = false;
            checkoutBtn.textContent = 'Commander';
        } else if (hasItems && !formValid) {
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = 'Remplissez tous les champs';
        } else {
            checkoutBtn.disabled = true;
            checkoutBtn.textContent = 'Panier vide';
        }
    }

    // Validate checkout form
    function validateCheckoutForm() {
        const fullName = document.getElementById('full-name').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const email = document.getElementById('email').value.trim();
        const location = document.getElementById('location').value.trim();

        // Basic validation
        return fullName.length >= 2 &&
               phone.length >= 8 &&
               /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) &&
               location.length >= 10;
    }

    // Update cart count in header
    function updateCartCount() {
        const count = cart.length;
        if (window.updateCart) {
            window.updateCart(count);
        }
    }

    // Remove item from cart
    window.removeFromCart = function(index) {
        cart.splice(index, 1);
        localStorage.setItem('cart', JSON.stringify(cart));
        renderCartItems();
        updateTotals();
        updateCartCount();
    };

    // Add item to cart (global function)
    window.addToCart = function(product) {
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

        cart.push(product);
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartCount();

        // Show success message
        showNotification('Produit ajouté au panier!', 'success');
    };

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
            const buttons = document.querySelectorAll('button, input[type="submit"], .add-to-cart-btn, .checkout-btn');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.5';
                btn.style.cursor = 'not-allowed';
                btn.setAttribute('data-original-text', btn.textContent || btn.value);
                btn.textContent = btn.value = 'Désactivé';
            });
        },

        // Enable all buttons
        enableAllButtons: function() {
            const buttons = document.querySelectorAll('button, input[type="submit"], .add-to-cart-btn, .checkout-btn');
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '';
                btn.style.cursor = '';
                const originalText = btn.getAttribute('data-original-text');
                if (originalText) {
                    btn.textContent = btn.value = originalText;
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

    // Checkout form handling
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Check if buttons are disabled due to spam
        if (spamBlocker.checkDisabledState()) {
            showNotification('🚫 Boutons désactivés temporairement.', 'error');
            return;
        }

        // Check for spam attempts
        if (spamBlocker.isSpamming()) {
            spamBlocker.showWarning();
            // If they continue spamming, disable buttons
            if (spamBlocker.warningShown) {
                spamBlocker.disableButtons();
                return;
            }
        }

        // Record this attempt
        spamBlocker.recordAttempt();

        if (cart.length === 0) {
            showNotification('Votre panier est vide!', 'error');
            return;
        }

        if (!validateCheckoutForm()) {
            showNotification('Veuillez remplir tous les champs correctement!', 'error');
            return;
        }

        const formData = new FormData(this);

        // Prepare data to send
        const orderData = {
            full_name: formData.get('full_name'),
            phone: formData.get('phone'),
            email: formData.get('email'),
            location: formData.get('location'),
            cart_items: cart,
            subtotal: parseFloat(document.getElementById('subtotal').textContent.replace(' DT', '')),
            delivery_fee: deliveryFee,
            total: parseFloat(document.getElementById('total').textContent.replace(' DT', ''))
        };

        // Disable submit button and show loading
        const submitBtn = this.querySelector('.checkout-btn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Traitement en cours...';

        // Send data to server
        const jsonString = JSON.stringify(orderData);
        console.log('Order data to send:', orderData);
        console.log('JSON string:', jsonString);
        console.log('JSON length:', jsonString.length);

        fetch('process_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: jsonString
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text(); // Get raw text first
        })
        .then(rawText => {
            console.log('Raw response text:', rawText);
            console.log('Raw response length:', rawText.length);

            try {
                const data = JSON.parse(rawText);
                console.log('Parsed response:', data);
                if (data.success) {
                    showNotification(data.message, 'success');

                    // Clear cart
                    cart = [];
                    localStorage.setItem('cart', JSON.stringify(cart));
                    closeCartModal();
                    updateCartCount();
                    renderCartItems();

                    // Reset form
                    this.reset();
                } else {
                    showNotification(data.message || 'Erreur inconnue du serveur', 'error');
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Raw response that failed to parse:', rawText);
                showNotification('Erreur de traitement de la réponse serveur', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            console.error('Order data being sent:', orderData);
            showNotification('Erreur de connexion au serveur. Vérifiez votre connexion internet ou contactez le support.', 'error');
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // Close modal when clicking outside or on close button
    document.querySelector('.cart-close').addEventListener('click', closeCartModal);
    document.getElementById('cart-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCartModal();
        }
    });

    // Initialize
    initCartModal();

    // Add form input listeners for real-time validation
    const formInputs = document.querySelectorAll('#checkout-form input, #checkout-form textarea');
    formInputs.forEach(input => {
        input.addEventListener('input', updateCheckoutButtonState);
        input.addEventListener('blur', updateCheckoutButtonState);
    });
});

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
