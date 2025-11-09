<?php
require_once '../config/connect.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: /Hotel-Restaurant/auth/login.php');
    exit;
}

$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_json'])) {
    $cart = json_decode($_POST['cart_json'], true);
    $allowed_methods = ['Cash', 'Stripe', 'PayPal'];
    $method = $_POST['method'] ?? 'Cash';
    if (!in_array($method, $allowed_methods, true)) {
        $method = 'Cash';
    }
    // Validate cart
    if (!is_array($cart) || empty($cart)) {
        $error = "Invalid cart data. Please refresh and try again.";
    } else {
        $total = 0;
        $validated_cart = [];
        $menu_ids = array_column($cart, 'id');
        $binds = [];
        foreach ($menu_ids as $idx => $mid) {
            // Oracle bind variable names must only use letters and numbers, no underscores
            $binds[] = ':mid' . $idx;
        }
        $in_clause = implode(',', $binds);
        $menu_sql = "SELECT menu_id, price, name FROM restaurant_menu WHERE menu_id IN ($in_clause)";
        $menu_stmt = oci_parse($connection, $menu_sql);
        foreach ($menu_ids as $idx => $mid) {
            oci_bind_by_name($menu_stmt, $binds[$idx], $menu_ids[$idx]);
        }
        oci_execute($menu_stmt);
        $menu_rows = [];
        while ($row = oci_fetch_assoc($menu_stmt)) {
            $menu_rows[$row['MENU_ID']] = $row;
        }
        foreach ($cart as $item) {
            if (!isset($menu_rows[$item['id']])) {
                $error = "Invalid menu item detected.";
                break;
            }
            $menu_row = $menu_rows[$item['id']];
            if (floatval($menu_row['PRICE']) != floatval($item['price'])) {
                $error = "Price mismatch for item: " . htmlspecialchars($menu_row['NAME']);
                break;
            }
            $total += $menu_row['PRICE'] * $item['quantity'];
            $validated_cart[] = [
                'id' => $menu_row['MENU_ID'],
                'name' => $menu_row['NAME'],
                'price' => $menu_row['PRICE'],
                'quantity' => $item['quantity']
            ];
        }
        if (!$error) {
            $cart = $validated_cart;

            // Always store order and payment, set payment status based on method
            $payment_status = ($method === 'Stripe' || $method === 'PayPal') ? 'Paid' : 'Pending';

            // Insert into restaurant_orders and get order_id
            $order_id = null;
            $order_sql = "BEGIN INSERT INTO restaurant_orders (order_id, user_id, total_amount, status) 
                              VALUES (restaurant_orders_seq.NEXTVAL, :uid, :total, 'Confirmed') RETURNING order_id INTO :out_order_id; END;";
            $stmt = oci_parse($connection, $order_sql);
            oci_bind_by_name($stmt, ':uid', $user_id);
            oci_bind_by_name($stmt, ':total', $total);
            oci_bind_by_name($stmt, ':out_order_id', $order_id, 32);

            if (!oci_execute($stmt)) {
                $error = 'Order creation failed: ' . htmlspecialchars(oci_error($stmt)['message']);
            } else {
                // Insert order items
                foreach ($cart as $item) {
                    $sql_item = "INSERT INTO order_items (order_item_id, order_id, menu_id, quantity, price) VALUES (order_items_seq.NEXTVAL, :oid, :mid, :qty, :price)";
                    $stmt_item = oci_parse($connection, $sql_item);
                    oci_bind_by_name($stmt_item, ':oid', $order_id);
                    oci_bind_by_name($stmt_item, ':mid', $item['id']);
                    oci_bind_by_name($stmt_item, ':qty', $item['quantity']);
                    oci_bind_by_name($stmt_item, ':price', $item['price']);
                    oci_execute($stmt_item);
                }
                $sql_pay = "INSERT INTO order_payments (payment_id, order_id, user_id, amount, method, status, payment_date)
                            VALUES (order_payments_seq.NEXTVAL, :oid_pay, :uid_pay, :amt_pay, :method_pay, :status_pay, SYSDATE)";
                $stmt_pay = oci_parse($connection, $sql_pay);
                oci_bind_by_name($stmt_pay, ':oid_pay', $order_id);
                oci_bind_by_name($stmt_pay, ':uid_pay', $user_id);
                oci_bind_by_name($stmt_pay, ':amt_pay', $total);
                oci_bind_by_name($stmt_pay, ':method_pay', $method);
                oci_bind_by_name($stmt_pay, ':status_pay', $payment_status);
                oci_execute($stmt_pay);

                $success = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - RoyalNest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/Styles/payment.css">
</head>

<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1 class="payment-title">
                <i class="fas fa-credit-card"></i> Payment
            </h1>
            <p class="payment-subtitle">Complete your RoyalNest dining experience</p>
        </div>

        <?php if ($success): ?>
            <div class="payment-status">
                <div class="success-msg">
                    <i class="fas fa-check-circle success-icon"></i>
                    <div>Payment successful! Thank you for your order.</div>
                </div>
                <a href="/Hotel-Restaurant/user/dining.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Menu
                </a>
            </div>
            <script>
                localStorage.removeItem('restaurant_cart');
            </script>
        <?php elseif ($error): ?>
            <div class="payment-status">
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <a href="/Hotel-Restaurant/user/dining.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Menu
                </a>
            </div>
        <?php else: ?>
            <form method="post" id="payment-form" autocomplete="off">
                <div class="payment-body">
                    <div class="order-summary">
                        <h3 class="summary-title">
                            <i class="fas fa-receipt"></i> Order Summary
                        </h3>
                        <div class="order-items" id="order-items">
                            <!-- Items will be populated by JavaScript -->
                        </div>
                        <div class="order-total">
                            <span>Total:</span>
                            <span id="order-total">$0.00</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-wallet"></i> Payment Method</label>
                        <div class="payment-methods">
                            <div class="method-card active" data-method="Cash">
                                <div class="method-icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <div class="method-name">Cash</div>
                            </div>
                            <div class="method-card" data-method="Stripe">
                                <div class="method-icon">
                                    <i class="fab fa-cc-stripe"></i>
                                </div>
                                <div class="method-name">Stripe</div>
                            </div>
                            <div class="method-card" data-method="PayPal">
                                <div class="method-icon">
                                    <i class="fab fa-cc-paypal"></i>
                                </div>
                                <div class="method-name">PayPal</div>
                            </div>
                        </div>
                        <input type="hidden" name="method" id="method" value="Cash" required>
                    </div>

                    <input type="hidden" name="cart_json" id="cart_json" />
                    <button type="submit" class="pay-btn">
                        <i class="fas fa-lock"></i> Pay Now
                    </button>
                </div>
            </form>

            <script>
                // Load cart from localStorage
                let cart = [];
                try {
                    cart = JSON.parse(localStorage.getItem('restaurant_cart') || '[]');
                } catch (e) {}

                const orderItems = document.getElementById('order-items');
                const orderTotal = document.getElementById('order-total');
                const cartJson = document.getElementById('cart_json');

                if (!Array.isArray(cart) || cart.length === 0) {
                    orderItems.innerHTML = `
                        <div class="empty-cart">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>Your cart is empty</h3>
                            <p>Please add items from our menu</p>
                        </div>
                    `;
                    document.querySelector('.pay-btn').disabled = true;
                } else {
                    let itemsHtml = '';
                    let total = 0;

                    cart.forEach(item => {
                        const itemTotal = item.price * item.quantity;
                        total += itemTotal;

                        itemsHtml += `
                            <div class="order-item">
                                <div class="item-name">
                                    <i class="fas fa-utensils"></i>
                                    <span>${item.name} x${item.quantity}</span>
                                </div>
                                <div class="item-price">$${itemTotal.toFixed(2)}</div>
                            </div>
                        `;
                    });

                    orderItems.innerHTML = itemsHtml;
                    orderTotal.textContent = `$${total.toFixed(2)}`;
                    cartJson.value = JSON.stringify(cart);
                }

                // Payment method selection
                const methodCards = document.querySelectorAll('.method-card');
                const methodInput = document.getElementById('method');

                methodCards.forEach(card => {
                    card.addEventListener('click', () => {
                        methodCards.forEach(c => c.classList.remove('active'));
                        card.classList.add('active');
                        methodInput.value = card.dataset.method;
                    });
                });
            </script>
        <?php endif; ?>
    </div>
</body>

</html>