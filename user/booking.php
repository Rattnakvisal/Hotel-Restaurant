<?php
require_once '../config/connect.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$room_id = isset($_GET['room_id']) ? $_GET['room_id'] : null;
$booking_msg = '';
$booking_id = null;

// Fetch room info for booking form
$room = null;
$room_bookings = [];
if ($room_id) {
    $sql = "SELECT * FROM rooms WHERE room_id = :room_id";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':room_id', $room_id);
    if (oci_execute($stmt)) {
        $room = oci_fetch_assoc($stmt);
        if ($room && isset($room['DESCRIPTION']) && is_object($room['DESCRIPTION']) && $room['DESCRIPTION'] instanceof OCILob) {
            $room['DESCRIPTION'] = $room['DESCRIPTION']->load();
        }
        // Image handling
        $default_img = '/Hotel-Restaurant/assets/img/default-room.jpg';
        $img = trim($room['IMAGE_URL'] ?? '');
        $filename = $img ? basename($img) : '';
        $local_url = '/Hotel-Restaurant/assets/uploads/rooms/' . $filename;
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;
        if ($img && preg_match('/^https?:\/\//', $img)) {
            $room['IMAGE_URL_DISPLAY'] = $img;
        } elseif ($filename && file_exists($file_path)) {
            $room['IMAGE_URL_DISPLAY'] = $local_url;
        } else {
            $room['IMAGE_URL_DISPLAY'] = $default_img;
        }
    } else {
        $room = null;
    }

    // Fetch existing bookings for this room (only future or ongoing bookings)
    $sql_existing = "SELECT check_in_date, check_out_date, status FROM bookings WHERE room_id = :room_id AND check_out_date >= TRUNC(SYSDATE) ORDER BY check_in_date";
    $stmt_existing = oci_parse($connection, $sql_existing);
    oci_bind_by_name($stmt_existing, ':room_id', $room_id);
    oci_execute($stmt_existing);
    while ($row = oci_fetch_assoc($stmt_existing)) {
        $row['CHECK_IN_DATE'] = date('Y-m-d', strtotime($row['CHECK_IN_DATE']));
        $row['CHECK_OUT_DATE'] = date('Y-m-d', strtotime($row['CHECK_OUT_DATE']));
        $room_bookings[] = $row;
    }
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_room'])) {
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guest_name = $_POST['guest_name'] ?? ($_SESSION['name'] ?? 'Guest');
    $guest_email = $_POST['guest_email'] ?? ($_SESSION['email'] ?? 'guest@example.com');
    $guest_phone = $_POST['guest_phone'] ?? ($_SESSION['phone'] ?? '');

    // Basic validation
    if (!$room_id || !$check_in || !$check_out) {
        $booking_msg = "Please fill all fields.";
    } elseif (strtotime($check_in) >= strtotime($check_out)) {
        $booking_msg = "Check-out must be after check-in.";
    } else {
        // Check for overlapping bookings
        $sql_overlap = "SELECT COUNT(*) AS CNT FROM bookings
            WHERE room_id = :room_id
            AND status IN ('Pending', 'Confirmed')
            AND (
                (TO_DATE(:check_in, 'YYYY-MM-DD') < check_out_date AND TO_DATE(:check_out, 'YYYY-MM-DD') > check_in_date)
            )";
        $stmt_overlap = oci_parse($connection, $sql_overlap);
        oci_bind_by_name($stmt_overlap, ':room_id', $room_id);
        oci_bind_by_name($stmt_overlap, ':check_in', $check_in);
        oci_bind_by_name($stmt_overlap, ':check_out', $check_out);
        oci_execute($stmt_overlap);
        $row_overlap = oci_fetch_assoc($stmt_overlap);
        if ($row_overlap['CNT'] > 0) {
            $booking_msg = "This room is already booked for the selected dates. Please choose different dates.";
        } else {
            $sql = "INSERT INTO bookings (booking_id, user_id, room_id, check_in_date, check_out_date, status, created_at)
                    VALUES (bookings_seq.NEXTVAL, :p_uid, :p_rid, TO_DATE(:p_cin, 'YYYY-MM-DD'), TO_DATE(:p_cout, 'YYYY-MM-DD'), 'Pending', SYSDATE)
                    RETURNING booking_id INTO :new_booking_id";
            $stmt = oci_parse($connection, $sql);
            oci_bind_by_name($stmt, ':p_uid', $user_id);
            oci_bind_by_name($stmt, ':p_rid', $room_id);
            oci_bind_by_name($stmt, ':p_cin', $check_in);
            oci_bind_by_name($stmt, ':p_cout', $check_out);
            oci_bind_by_name($stmt, ':new_booking_id', $booking_id, 32);
            if (oci_execute($stmt)) {
                // Insert guest record
                $sql_guest = "INSERT INTO guests (guest_id, first_name, email, phone, booking_id, room_id, created_at)
                              VALUES (guests_seq.NEXTVAL, :gname, :gemail, :gphone, :bid, :rid, SYSDATE)";
                $stmt_guest = oci_parse($connection, $sql_guest);
                oci_bind_by_name($stmt_guest, ':gname', $guest_name);
                oci_bind_by_name($stmt_guest, ':gemail', $guest_email);
                oci_bind_by_name($stmt_guest, ':gphone', $guest_phone); // <-- FIXED: phone now set
                oci_bind_by_name($stmt_guest, ':bid', $booking_id);
                oci_bind_by_name($stmt_guest, ':rid', $room_id);
                oci_execute($stmt_guest);
                // Redirect to booking payment page
                header("Location: Booking_payment.php?booking_id=" . $booking_id);
                exit;
            } else {
                $e = oci_error($stmt);
                $booking_msg = "Booking failed: " . htmlspecialchars($e['message']);
            }
        }
    }
}

// Handle booking cancellation
if (isset($_GET['cancel_booking'])) {
    $cancel_id = intval($_GET['cancel_booking']);

    // Delete child records first to avoid ORA-02292
    // 1. Delete booking_payments
    $sql = "DELETE FROM booking_payments WHERE booking_id = :bid";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':bid', $cancel_id);
    oci_execute($stmt);

    // 2. Delete guests
    $sql = "DELETE FROM guests WHERE booking_id = :bid";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':bid', $cancel_id);
    oci_execute($stmt);

    // 3. Delete any other child tables referencing bookings here if needed

    // 4. Delete the booking itself
    $sql = "DELETE FROM bookings WHERE booking_id = :bid AND user_id = :uid";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':bid', $cancel_id);
    oci_bind_by_name($stmt, ':uid', $user_id);
    oci_execute($stmt);

    header("Location: booking.php?msg=cancelled");
    exit;
}

// Handle check-in
if (isset($_GET['checkin'])) {
    $bid = intval($_GET['checkin']);
    $today = date('Y-m-d');
    $sql = "UPDATE bookings SET status = 'Checked-in' WHERE booking_id = :bid AND user_id = :uid AND status = 'Confirmed' AND TO_CHAR(check_in_date, 'YYYY-MM-DD') = :today";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':bid', $bid);
    oci_bind_by_name($stmt, ':uid', $user_id);
    oci_bind_by_name($stmt, ':today', $today);
    oci_execute($stmt);
    header("Location: booking.php?msg=checkedin");
    exit;
}

// Handle check-out
if (isset($_GET['checkout'])) {
    $bid = intval($_GET['checkout']);
    $today = date('Y-m-d');
    $sql = "UPDATE bookings SET status = 'Checked-out' WHERE booking_id = :bid AND user_id = :uid AND status = 'Checked-in' AND TO_CHAR(check_out_date, 'YYYY-MM-DD') = :today";
    $stmt = oci_parse($connection, $sql);
    oci_bind_by_name($stmt, ':bid', $bid);
    oci_bind_by_name($stmt, ':uid', $user_id);
    oci_bind_by_name($stmt, ':today', $today);
    oci_execute($stmt);
    header("Location: booking.php?msg=checkedout");
    exit;
}

// Fetch user's bookings
$bookings = [];
$sql = "SELECT b.*, r.room_name, r.price_per_night, r.image_url, u.name AS user_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.room_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.user_id = :p_uid
        ORDER BY b.booking_id DESC";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':p_uid', $user_id);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    // Image handling for bookings table
    $default_img = '/Hotel-Restaurant/assets/img/default-room.jpg';
    $img = trim($row['IMAGE_URL'] ?? '');
    $filename = $img ? basename($img) : '';
    $local_url = '/Hotel-Restaurant/uploads/rooms/' . $filename;
    $file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;
    if ($img && preg_match('/^https?:\/\//', $img)) {
        $row['IMAGE_URL_DISPLAY'] = $img;
    } elseif ($filename && file_exists($file_path)) {
        $row['IMAGE_URL_DISPLAY'] = $local_url;
    } else {
        $row['IMAGE_URL_DISPLAY'] = $default_img;
    }
    $bookings[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Booking | RoyalNest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/Styles/booking.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <a href="/Hotel-Restaurant/user/index.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>

        <div class="header">
            <div class="logo">Royal<span>Nest</span></div>
            <div class="subtitle">RESERVE YOUR LUXURY EXPERIENCE</div>
        </div>

        <div class="booking-container">
            <div class="booking-header">
                <h1 class="booking-title">Book Your Stay</h1>
                <div class="booking-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>

            <?php if ($room): ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="<?= htmlspecialchars($room['IMAGE_URL_DISPLAY']) ?>" alt="<?= htmlspecialchars($room['ROOM_NAME']) ?>">
                    </div>
                    <div class="room-info">
                        <h2 class="room-name"><?= htmlspecialchars($room['ROOM_NAME']) ?></h2>
                        <div class="room-price">$<?= number_format($room['PRICE_PER_NIGHT'], 2) ?> <span style="font-size:1rem;color:#888;">per night</span></div>
                        <p class="room-desc"><?= substr(htmlspecialchars($room['DESCRIPTION']), 0, 200) ?>...</p>
                    </div>
                </div>

                <!-- Show existing bookings for this room -->
                <?php if (!empty($room_bookings)): ?>
                    <div style="margin-bottom:20px;">
                        <h3 style="font-size:1.2rem;color:#8c6d46;margin-bottom:8px;">Existing Bookings for this Room:</h3>
                        <table style="width:100%;border-collapse:collapse;">
                            <thead>
                                <tr style="background:#f8f6f2;">
                                    <th style="padding:8px;">Check-in</th>
                                    <th style="padding:8px;">Check-out</th>
                                    <th style="padding:8px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($room_bookings as $b): ?>
                                    <tr>
                                        <td style="padding:8px;"><?= htmlspecialchars($b['CHECK_IN_DATE']) ?></td>
                                        <td style="padding:8px;"><?= htmlspecialchars($b['CHECK_OUT_DATE']) ?></td>
                                        <td style="padding:8px;"><?= htmlspecialchars($b['STATUS']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <form method="post" class="booking-form">
                    <input type="hidden" name="room_id" value="<?= $room['ROOM_ID'] ?>">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-sign-in-alt"></i> Check-in Date</label>
                        <input type="date" name="check_in" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-sign-out-alt"></i> Check-out Date</label>
                        <input type="date" name="check_out" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-user"></i> Guest Name</label>
                        <input type="text" name="guest_name" class="form-input" value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="guest_email" class="form-input" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-phone"></i> Phone</label>
                        <input type="tel" name="guest_phone" class="form-input" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>" required>
                    </div>
                    <button type="submit" name="book_room" class="submit-btn">
                        <i class="fas fa-check-circle"></i> Confirm Booking
                    </button>
                </form>
            <?php else: ?>
                <div class="no-room">
                    <p>Please select a room to book from our <a href="/Hotel-Restaurant/rooms.php" style="color:#8c6d46;text-decoration:none;">Rooms page</a>.</p>
                </div>
            <?php endif; ?>

            <?php if ($booking_msg): ?>
                <div class="msg <?= strpos($booking_msg, 'failed') ? 'error' : 'success' ?>">
                    <i class="fas <?= strpos($booking_msg, 'failed') ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                    <?= htmlspecialchars($booking_msg) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="footer">
            <p>RoyalNest Hotel &copy; 2023 | Luxury Redefined</p>
            <p>Contact: reservations@royalnest.com | +1 (555) 123-4567</p>
        </div>
    </div>

    <script>
        // Set minimum date to today for check-in
        const today = new Date().toISOString().split('T')[0];
        document.querySelector('input[name="check_in"]').min = today;

        // Set check-out min to next day
        document.querySelector('input[name="check_in"]').addEventListener('change', function() {
            const checkInDate = new Date(this.value);
            checkInDate.setDate(checkInDate.getDate() + 1);
            const nextDay = checkInDate.toISOString().split('T')[0];
            document.querySelector('input[name="check_out"]').min = nextDay;
        });

        // Initialize check-out min based on check-in if already set
        const checkInValue = document.querySelector('input[name="check_in"]').value;
        if (checkInValue) {
            const checkInDate = new Date(checkInValue);
            checkInDate.setDate(checkInDate.getDate() + 1);
            const nextDay = checkInDate.toISOString().split('T')[0];
            document.querySelector('input[name="check_out"]').min = nextDay;
        }
    </script>
</body>

</html>