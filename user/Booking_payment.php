<?php
require_once '../config/connect.php';
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: /Hotel-Restaurant/auth/login.php');
    exit;
}

$booking_id = $_GET['booking_id'] ?? null;
$success = false;
$error = '';
$booking = null;
$nights = 0;
$amount = 0;

if ($booking_id) {
    $sql = "SELECT b.*, r.room_name, r.price_per_night FROM bookings b JOIN rooms r ON b.room_id = r.room_id WHERE b.booking_id = :p_bid AND b.user_id = :p_uid";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':p_bid', $booking_id);
    oci_bind_by_name($stmt, ':p_uid', $user_id);
    oci_execute($stmt);
    $booking = oci_fetch_assoc($stmt);

    if ($booking) {
        $check_in = new DateTime($booking['CHECK_IN_DATE']);
        $check_out = new DateTime($booking['CHECK_OUT_DATE']);
        $nights = $check_in->diff($check_out)->days;
        $amount = $nights * $booking['PRICE_PER_NIGHT'];
    }
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_room'])) {
    $method = $_POST['method'] ?? 'Phone';
    $allowed_methods = ['Phone', 'Cash', 'Stripe', 'PayPal'];
    if (!in_array($method, $allowed_methods, true)) $method = 'Phone';

    if ($booking) {
        $status = ($method === 'Phone') ? 'Pending' : 'Paid';
        $sql = "INSERT INTO booking_payments (payment_id, booking_id, user_id, amount, method, status, payment_date)
                VALUES (booking_payments_seq.NEXTVAL, :p_bid, :p_uid, :amt, :method, :status, SYSDATE)
                RETURNING payment_id INTO :new_payment_id";
        $stmt = oci_parse($connection, $sql);
        oci_bind_by_name($stmt, ':p_bid', $booking_id);
        oci_bind_by_name($stmt, ':p_uid', $user_id);
        oci_bind_by_name($stmt, ':amt', $amount);
        oci_bind_by_name($stmt, ':method', $method);
        oci_bind_by_name($stmt, ':status', $status);
        $new_payment_id = null;
        oci_bind_by_name($stmt, ':new_payment_id', $new_payment_id, 32);
        if (oci_execute($stmt)) {
            // Update guest record with payment_id
            $sql_guest_update = "UPDATE guests SET payment_id = :pid WHERE booking_id = :bid";
            $stmt_guest_update = oci_parse($connection, $sql_guest_update);
            oci_bind_by_name($stmt_guest_update, ':pid', $new_payment_id);
            oci_bind_by_name($stmt_guest_update, ':bid', $booking_id);
            oci_execute($stmt_guest_update);
            $success = true;
        } else {
            $e = oci_error($stmt);
            $error = 'Payment failed: ' . htmlspecialchars($e['message']);
        }
    } else {
        $error = "Invalid booking.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Payment - RoyalNest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/Styles/booking_payment.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="header">
        <a href="/Hotel-Restaurant/user/booking.php" class="logo">
            <i class="fas fa-crown"></i>
            <span>RoyalNest</span>
        </a>
        <div class="user-info">
            <div class="user-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-name">
                <?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest') ?>
            </div>
        </div>
    </div>

    <div class="payment-container">
        <div class="payment-header">
            <i class="fas fa-credit-card"></i>
            <div class="payment-title">Complete Your Booking Payment</div>
        </div>

        <div class="payment-content">
            <div class="booking-summary">
                <div class="section-title">
                    <i class="fas fa-receipt"></i>
                    Booking Summary
                </div>

                <?php if ($booking_id && $booking): ?>
                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="summary-row">
                                <div class="summary-label">Booking ID</div>
                                <div class="summary-value">#<?= htmlspecialchars($booking_id) ?></div>
                            </div>
                            <div class="summary-row">
                                <div class="summary-label">Room Type</div>
                                <div class="summary-value highlight"><?= htmlspecialchars($booking['ROOM_NAME']) ?></div>
                            </div>
                            <div class="summary-row">
                                <div class="summary-label">Price per Night</div>
                                <div class="summary-value">$<?= number_format($booking['PRICE_PER_NIGHT'], 2) ?></div>
                            </div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-row">
                                <div class="summary-label">Check-in Date</div>
                                <div class="summary-value"><?= htmlspecialchars(date('M d, Y', strtotime($booking['CHECK_IN_DATE']))) ?></div>
                            </div>
                            <div class="summary-row">
                                <div class="summary-label">Check-out Date</div>
                                <div class="summary-value"><?= htmlspecialchars(date('M d, Y', strtotime($booking['CHECK_OUT_DATE']))) ?></div>
                            </div>
                            <div class="summary-row">
                                <div class="summary-label">Total Nights</div>
                                <div class="summary-value"><?= $nights ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="total-amount">
                        <div class="total-label">Total Amount Due</div>
                        <div class="total-value">$<?= number_format($amount, 2) ?></div>
                    </div>
                <?php else: ?>
                    <div class="msg error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>Invalid booking or no booking selected.</div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="payment-form-section">
                <?php if ($booking_id && $booking): ?>
                    <?php if ($success): ?>
                        <div class="msg success">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Payment submitted successfully!</strong>
                                <?php if ($_POST['method'] === 'Phone'): ?>
                                    <div class="pending">
                                        <i class="fas fa-info-circle"></i>
                                        Please wait for staff to contact you by phone to complete payment.
                                    </div>
                                <?php else: ?>
                                    <div>Thank you for your payment. Your booking is confirmed!</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="booking.php" class="back-btn">
                            <i class="fas fa-arrow-left"></i> Back to My Bookings
                        </a>
                    <?php else: ?>
                        <div class="section-title">
                            <i class="fas fa-credit-card"></i>
                            Payment Method
                        </div>

                        <?php if ($error): ?>
                            <div class="msg error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div><?= $error ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="form-group">
                                <label class="form-label">Select Payment Method</label>
                                <select name="method" id="method" class="method-select">
                                    <option value="Phone">Phone Payment</option>
                                    <option value="Cash">Cash at Property</option>
                                    <option value="Stripe">Credit/Debit Card</option>
                                    <option value="PayPal">PayPal</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Or choose an option below:</label>
                                <div class="method-options">
                                    <div class="method-option" data-value="Phone">
                                        <div class="method-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="method-name">Phone</div>
                                        <div class="method-desc">Pay later by phone</div>
                                    </div>
                                    <div class="method-option" data-value="Cash">
                                        <div class="method-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="method-name">Cash</div>
                                        <div class="method-desc">Pay at the property</div>
                                    </div>
                                    <div class="method-option" data-value="Stripe">
                                        <div class="method-icon">
                                            <i class="fab fa-cc-stripe"></i>
                                        </div>
                                        <div class="method-name">Card</div>
                                        <div class="method-desc">Credit/Debit Card</div>
                                    </div>
                                    <div class="method-option" data-value="PayPal">
                                        <div class="method-icon">
                                            <i class="fab fa-paypal"></i>
                                        </div>
                                        <div class="method-name">PayPal</div>
                                        <div class="method-desc">Online Payment</div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="pay_room" class="pay-btn">
                                <i class="fas fa-lock"></i> Pay $<?= number_format($amount, 2) ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Method selection interaction
        document.querySelectorAll('.method-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove active class from all options
                document.querySelectorAll('.method-option').forEach(opt => {
                    opt.classList.remove('active');
                });

                // Add active class to clicked option
                this.classList.add('active');

                // Update the select element
                const method = this.getAttribute('data-value');
                document.getElementById('method').value = method;
            });
        });

        // Initialize active option based on select value
        document.addEventListener('DOMContentLoaded', function() {
            const initialMethod = document.getElementById('method').value;
            document.querySelector(`.method-option[data-value="${initialMethod}"]`).classList.add('active');
        });
    </script>
</body>

</html>