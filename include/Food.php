<?php
require_once '../config/connect.php';

// --- Fetch Available Food Items ---
$sql = "SELECT * FROM restaurant_menu ORDER BY menu_id DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
$menu_items = [];
while ($row = oci_fetch_assoc($stmt)) {
    $default_img = '/Hotel-Restaurant/assets/img/default-food.jpg';
    $img = trim($row['IMAGE_URL'] ?? '');
    $filename = $img ? basename($img) : '';
    $local_url = '/Hotel-Restaurant/assets/uploads/food/' . $filename;
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;

    if ($img && preg_match('/^https?:\/\//', $img)) {
        $row['IMAGE_URL_DISPLAY'] = $img;
    } elseif ($filename && file_exists($file_path)) {
        $row['IMAGE_URL_DISPLAY'] = $local_url;
    } else {
        $row['IMAGE_URL_DISPLAY'] = $default_img;
    }
    // Convert OCILob (CLOB) fields to string for DESCRIPTION
    if (isset($row['DESCRIPTION']) && is_object($row['DESCRIPTION']) && $row['DESCRIPTION'] instanceof OCILob) {
        $row['DESCRIPTION'] = $row['DESCRIPTION']->load();
    }
    $menu_items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>RoyalNest Restaurant Menu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .header {
            text-align: center;
            margin: 30px 0;
            padding: 0 20px;
        }

        .menu-container {
            width: 100%;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 40px;
            margin-top: 60px;
        }

        .menu-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            transition: var(--transition);
            display: grid;
            height: 100%;
        }

        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .menu-img {
            height: 250px;
            width: 100%;
            object-fit: cover;
            display: block;
            border-bottom: 1px solid #eee;
        }

        .menu-content {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .menu-name {
            font-family: "Playfair Display", serif;
            font-size: 1.8rem;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .menu-price {
            color: var(--royal-gold);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .menu-price span {
            font-size: 1rem;
            color: var(--gray);
            font-weight: normal;
        }

        .menu-desc {
            color: var(--gray);
            margin-bottom: 20px;
            line-height: 1.6;
            flex: 1;
        }

        .menu-features {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }

        .feature-tag {
            background: var(--light-purple);
            color: var(--royal-purple);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .add-to-cart-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--royal-purple);
            color: white;
            padding: 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .add-to-cart-btn:hover {
            background: var(--dark);
            transform: translateY(-3px);
        }

        /* Floating Cart Icon */
        .floating-cart {
            position: fixed;
            top: 30px;
            right: 40px;
            z-index: 2000;
            background: var(--royal-purple, #6c3bb8);
            color: #fff;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.13);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: box-shadow 0.2s;
        }

        .floating-cart:hover {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
        }

        .cart-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--royal-gold, #e3b04b);
            color: #fff;
            border-radius: 50%;
            min-width: 22px;
            height: 22px;
            font-size: 1rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
        }

        /* Modal Styles */
        .modal-bg {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.25);
            align-items: center;
            justify-content: center;
        }

        .modal-bg.active {
            display: flex;
        }

        .cart-modal {
            background: #fff;
            border-radius: 18px;
            max-width: 420px;
            width: 95vw;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.18);
            padding: 32px 24px 24px 24px;
            position: relative;
            animation: fadeIn 0.2s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        .cart-modal .close-modal {
            position: absolute;
            top: 18px;
            right: 18px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #888;
            cursor: pointer;
        }

        /* Cart Styles */
        .cart-summary {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 30px;
            height: fit-content;
        }

        .cart-title {
            font-family: "Playfair Display", serif;
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: var(--royal-purple);
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-purple);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-title i {
            color: var(--royal-gold);
        }

        .cart-items {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 10px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item-details {
            flex: 1;
            padding-right: 15px;
        }

        .cart-item-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .cart-item-price {
            color: var(--royal-gold);
            font-weight: 600;
        }

        .cart-item-qty {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--light-purple);
            color: var(--royal-purple);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .qty-btn:hover {
            background: var(--royal-purple);
            color: white;
        }

        .cart-item-qty span {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.3rem;
            font-weight: 700;
            padding: 20px 0;
            border-top: 2px solid var(--light-purple);
            margin-top: 10px;
            color: var(--royal-purple);
        }

        .checkout-btn {
            width: 100%;
            padding: 16px;
            font-size: 1.1rem;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }

        .checkout-btn:hover {
            background: #219653;
            transform: translateY(-3px);
        }

        .empty-cart {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-cart i {
            font-size: 3.5rem;
            color: var(--light-purple);
            margin-bottom: 20px;
        }

        .empty-cart p {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .container-1 {
                max-width: 900px;
            }

            .menu-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 900px) {
            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container-1 {
                max-width: 100%;
                padding: 0;
            }

            .section-title {
                font-size: 2.5rem;
            }

            .menu-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .cart-summary {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .section-title {
                font-size: 2rem;
            }

            .menu-name {
                font-size: 1.6rem;
            }

            .menu-price {
                font-size: 1.2rem;
            }

            .menu-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>

<body class="body-1">
    <!-- Floating Cart Icon -->
    <div class="floating-cart" id="floating-cart" tabindex="0" aria-label="Show cart" title="Show cart">
        <i class="fas fa-shopping-cart fa-lg"></i>
        <span class="cart-badge" id="cart-badge" style="display:none;">0</span>
    </div>

    <!-- Cart Modal -->
    <div class="modal-bg" id="cart-modal-bg">
        <div class="cart-modal">
            <button class="close-modal" id="close-cart-modal" title="Close">&times;</button>
            <h2 class="cart-title"><i class="fas fa-shopping-cart"></i> Your Order</h2>
            <div class="cart-items" id="cart-items-modal">
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty</p>
                    <p>Add delicious items to your order</p>
                </div>
            </div>
            <div class="cart-total">
                <span>Total:</span>
                <span id="cart-total-modal">$0.00</span>
            </div>
            <button class="checkout-btn" id="checkout-btn-modal">
                <i class="fas fa-lock"></i> Secure Checkout
            </button>
        </div>
    </div>

    <div class="container-1">
        <div class="header">
            <h1 class="section-title">RoyalNest Restaurant Menu</h1>
            <p style="color: var(--gray); max-width: 700px; margin: 0 auto; font-size: 1.1rem;">
                Discover our exquisite culinary creations crafted with the finest ingredients by our award-winning chefs
            </p>
        </div>

        <div class="menu-container">
            <div class="menu-grid">
                <?php foreach ($menu_items as $item): ?>
                    <div class="menu-card">
                        <img
                            src="<?php echo htmlspecialchars($item['IMAGE_URL_DISPLAY']); ?>"
                            alt="<?php echo htmlspecialchars($item['NAME']); ?>"
                            class="menu-img">
                        <div class="menu-content">
                            <h3 class="menu-name">
                                <?php echo htmlspecialchars($item['NAME']); ?>
                            </h3>
                            <div class="menu-price">
                                $<?php echo number_format($item['PRICE'], 2); ?>
                                <span>per item</span>
                            </div>
                            <p class="menu-desc">
                                <?php echo nl2br(htmlspecialchars($item['DESCRIPTION'])); ?>
                            </p>
                            <div class="menu-features">
                                <?php if (!empty($item['CATEGORY'])): ?>
                                    <span class="feature-tag"><?php echo htmlspecialchars($item['CATEGORY']); ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="add-to-cart-btn"
                                data-id="<?php echo htmlspecialchars($item['MENU_ID']); ?>"
                                data-name="<?php echo htmlspecialchars($item['NAME']); ?>"
                                data-price="<?php echo htmlspecialchars($item['PRICE']); ?>">
                                <i class="fas fa-plus"></i> Add to Order
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script>
        // Cart functionality
        let cart = [];
        const cartBadge = document.getElementById('cart-badge');
        const floatingCart = document.getElementById('floating-cart');
        const cartModalBg = document.getElementById('cart-modal-bg');
        const cartItemsModal = document.getElementById('cart-items-modal');
        const cartTotalModal = document.getElementById('cart-total-modal');
        const closeCartModal = document.getElementById('close-cart-modal');
        const checkoutBtnModal = document.getElementById('checkout-btn-modal');

        // Add to cart buttons
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const name = button.dataset.name;
                const price = parseFloat(button.dataset.price);

                // Check if item already in cart
                const existingItem = cart.find(item => item.id === id);

                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    cart.push({
                        id,
                        name,
                        price,
                        quantity: 1
                    });
                }
                updateCartBadge();
                updateCartModal();
                // Update navbar cart badge if available
                if (window.updateCartBadge) window.updateCartBadge();
            });
        });

        // Update cart badge
        function updateCartBadge() {
            let count = cart.reduce((sum, item) => sum + item.quantity, 0);
            cartBadge.textContent = count;
            cartBadge.style.display = count > 0 ? 'flex' : 'none';
        }

        // Show modal on cart icon click
        floatingCart.addEventListener('click', () => {
            updateCartModal();
            cartModalBg.classList.add('active');
        });

        // Close modal
        closeCartModal.addEventListener('click', () => {
            cartModalBg.classList.remove('active');
        });
        cartModalBg.addEventListener('click', (e) => {
            if (e.target === cartModalBg) cartModalBg.classList.remove('active');
        });

        // Update cart modal display
        function updateCartModal() {
            cartItemsModal.innerHTML = '';
            let total = 0;
            if (cart.length === 0) {
                cartItemsModal.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Your cart is empty</p>
                        <p>Add delicious items to your order</p>
                    </div>
                `;
            } else {
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    const cartItemEl = document.createElement('div');
                    cartItemEl.className = 'cart-item';
                    cartItemEl.innerHTML = `
                        <div class="cart-item-details">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                        </div>
                        <div class="cart-item-qty">
                            <button class="qty-btn minus" data-id="${item.id}">-</button>
                            <span>${item.quantity}</span>
                            <button class="qty-btn plus" data-id="${item.id}">+</button>
                        </div>
                    `;
                    cartItemsModal.appendChild(cartItemEl);
                });
                // Add event listeners to new buttons
                cartItemsModal.querySelectorAll('.qty-btn.minus').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.dataset.id;
                        const item = cart.find(item => item.id === id);
                        if (item.quantity > 1) {
                            item.quantity--;
                        } else {
                            cart = cart.filter(item => item.id !== id);
                        }
                        updateCartBadge();
                        updateCartModal();
                    });
                });
                cartItemsModal.querySelectorAll('.qty-btn.plus').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.dataset.id;
                        const item = cart.find(item => item.id === id);
                        item.quantity++;
                        updateCartBadge();
                        updateCartModal();
                    });
                });
            }
            cartTotalModal.textContent = `$${total.toFixed(2)}`;
        }

        // Checkout button in modal
        checkoutBtnModal.addEventListener('click', () => {
            if (cart.length === 0) {
                alert('Your cart is empty. Please add items before checking out.');
            } else {
                // Store cart in localStorage and redirect to payment page
                localStorage.setItem('restaurant_cart', JSON.stringify(cart));
                window.location.href = '/Hotel-Restaurant/user/Payment.php';
            }
        });

        // On page load, restore cart from localStorage if present
        window.addEventListener('DOMContentLoaded', () => {
            try {
                const savedCart = JSON.parse(localStorage.getItem('restaurant_cart') || '[]');
                if (Array.isArray(savedCart) && savedCart.length > 0) {
                    cart = savedCart;
                    updateCartBadge();
                }
            } catch (e) {}
        });
    </script>
</body>

</html>