<?php
require_once '../config/connect.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
$room_id = $_GET['id'] ?? null;
if (!$room_id) {
    echo "<h2>Room not found.</h2>";
    exit;
}

$sql = "SELECT * FROM rooms WHERE room_id = :room_id";
$stmt = oci_parse($connection, $sql);
oci_bind_by_name($stmt, ':room_id', $room_id);
oci_execute($stmt);
$room = oci_fetch_assoc($stmt);

if (!$room) {
    echo "<h2>Room not found.</h2>";
    exit;
}

// Handle CLOB for description
if (isset($room['DESCRIPTION']) && is_object($room['DESCRIPTION']) && $room['DESCRIPTION'] instanceof OCILob) {
    $room['DESCRIPTION'] = $room['DESCRIPTION']->load();
}

$img = trim($room['IMAGE_URL'] ?? '');
$filename = $img ? basename($img) : '';
$local_url = '/Hotel-Restaurant/assets/uploads/rooms/' . $filename;
$file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;
$default_img = '/Hotel-Restaurant/assets/img/default-room.jpg';

if ($img && preg_match('/^https?:\/\//', $img)) {
    $img_url_display = $img;
} elseif ($filename && file_exists($file_path)) {
    $img_url_display = $local_url;
} else {
    $img_url_display = $default_img;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($room['ROOM_NAME']) ?> | RoyalNest Room Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/Styles/room_details.css">
</head>

<body>
    <div class="container">
        <button class="back-btn" onclick="history.back()">
            <i class="fas fa-arrow-left"></i> Back to Rooms
        </button>

        <div class="header">
            <div class="logo">Royal<span>Nest</span></div>
            <div class="subtitle">LUXURY ACCOMMODATIONS</div>
        </div>

        <div class="detail-container">
            <div class="image-section">
                <img src="<?= htmlspecialchars($img_url_display) ?>" alt="<?= htmlspecialchars($room['ROOM_NAME']) ?>" class="main-image">
                <div class="image-overlay">
                    <div class="price-tag">$<?= number_format($room['PRICE_PER_NIGHT'], 2) ?> <span>/night</span></div>
                    <div class="room-status">
                        <i class="fas fa-circle"></i> <?= htmlspecialchars($room['STATUS']) ?>
                    </div>
                </div>
            </div>

            <div class="info-section">
                <h1 class="room-title"><?= htmlspecialchars($room['ROOM_NAME']) ?></h1>

                <div class="room-meta">
                    <div class="room-size">
                        <i class="fas fa-ruler-combined"></i>
                        <?= isset($room['ROOM_SIZE']) && $room['ROOM_SIZE'] ? htmlspecialchars($room['ROOM_SIZE']) . ' sq.ft.' : 'N/A' ?>
                    </div>
                    <div class="room-status">
                        <i class="fas fa-bed"></i> Sleeps <?= htmlspecialchars($room['SLEEPS']) ?>
                    </div>
                </div>

                <p class="room-desc">
                    <?= nl2br(htmlspecialchars($room['DESCRIPTION'])) ?>
                </p>

                <div class="room-features">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="feature-title">Bed Type</div>
                        <div class="feature-value">King Size</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="feature-title">Capacity</div>
                        <div class="feature-value"><?= htmlspecialchars($room['SLEEPS']) ?> Guests</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <div class="feature-title">Room Type</div>
                        <div class="feature-value">Deluxe</div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-wind"></i>
                        </div>
                        <div class="feature-title">View</div>
                        <div class="feature-value">Ocean</div>
                    </div>
                </div>

                <div class="amenities">
                    <div class="amenity"><i class="fas fa-wifi"></i> Free Wi-Fi</div>
                    <div class="amenity"><i class="fas fa-tv"></i> Smart TV</div>
                    <div class="amenity"><i class="fas fa-coffee"></i> Coffee Maker</div>
                    <div class="amenity"><i class="fas fa-wine-bottle"></i> Mini Bar</div>
                    <div class="amenity"><i class="fas fa-shower"></i> Rain Shower</div>
                </div>

                <div class="booking-section">
                    <div class="amenities">
                        <div class="amenity"><i class="fas fa-parking"></i> Parking</div>
                        <div class="amenity"><i class="fas fa-snowflake"></i> A/C</div>
                        <div class="amenity"><i class="fas fa-lock"></i> Safe</div>
                    </div>
                    <a href="booking.php?room_id=<?= $room['ROOM_ID'] ?>" class="book-btn">
                        <i class="fas fa-calendar-check"></i> Book Now
                    </a>
                </div>
            </div>
        </div>

        <div class="testimonials">
            <h2 class="section-title">Guest Experiences</h2>
            <div class="testimonial-grid">
                <div class="testimonial-card">
                    <p class="testimonial-text">
                        The room was absolutely stunning! The ocean view took our breath away every morning.
                        Service was impeccable and the bed was incredibly comfortable.
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">S</div>
                        <div class="author-info">
                            <div class="author-name">Sarah Johnson</div>
                            <div class="author-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <p class="testimonial-text">
                        Best hotel experience we've ever had. The attention to detail in the room design
                        and the quality of amenities were exceptional. Will definitely return!
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">M</div>
                        <div class="author-info">
                            <div class="author-name">Michael Chen</div>
                            <div class="author-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <p class="testimonial-text">
                        Perfect anniversary getaway! The room was spacious and luxurious.
                        The balcony with the ocean view was our favorite spot for evening drinks.
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">A</div>
                        <div class="author-info">
                            <div class="author-name">Amanda Roberts</div>
                            <div class="author-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            // Simple animation for cards when they come into view
            document.addEventListener('DOMContentLoaded', function() {
                const featureCards = document.querySelectorAll('.feature-card');
                featureCards.forEach((card, index) => {
                    setTimeout(() => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        card.style.transition = 'all 0.5s ease';

                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 100);
                    }, index * 100);
                });

                // Button hover effects
                const bookBtn = document.querySelector('.book-btn');
                bookBtn.addEventListener('mouseover', function() {
                    this.style.transform = 'scale(1.05)';
                });

                bookBtn.addEventListener('mouseout', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        </script>
</body>

</html>