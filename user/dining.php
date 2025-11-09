<?php require_once("../include/Header.php");
require_once '../config/connect.php';
?>
<link rel="stylesheet" href="../assets/Styles/dining.css">
<div class="container">
    <section class="restaurant-banner">
        <div class="banner-image"></div>
        <div class="banner-content">
            <h1 class="banner-title">Savor Exquisite Culinary Delights</h1>
            <p class="banner-subtitle">
                Experience the artistry of our master chefs with dishes crafted from
                the freshest ingredients and inspired by global flavors.
            </p>
            <a href="#menu" class="btn">Explore Our Menu</a>
        </div>
    </section>

    <!-- Restaurant Features -->
    <div class="restaurant-features">
        <div class="feature">
            <div class="feature-icon">
                <i class="fas fa-seedling"></i>
            </div>
            <div class="feature-text">
                <strong>Fresh Ingredients</strong>
                Locally sourced, organic produce
            </div>
        </div>

        <div class="feature">
            <div class="feature-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="feature-text">
                <strong>Expert Chefs</strong>
                Michelin-trained culinary artists
            </div>
        </div>

        <div class="feature">
            <div class="feature-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <div class="feature-text">
                <strong>Quick Service</strong>
                30-minute delivery guarantee
            </div>
        </div>

        <div class="feature">
            <div class="feature-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="feature-text">
                <strong>Premium Quality</strong>
                Handcrafted dishes with care
            </div>
        </div>
    </div>
    <!-- Menu Section -->
    <?php require_once("../include/Food.php"); ?>
</div>
<?php require_once("../include/Footer.php"); ?>