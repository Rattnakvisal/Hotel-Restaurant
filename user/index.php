<?php
session_start();
// Only allow users (not admins) to access this page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: /Hotel-Restaurant/auth/login.php");
    exit;
}
// Session timeout: 30 minutes
$timeout = 1800; // seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: /Hotel-Restaurant/auth/login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/config/connect.php';

// Fetch services
$services = [];
$sql = "SELECT * FROM viewsitem ORDER BY image_id DESC";
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $services[] = $row;
}

// Separate by category
$rooms = [];
$foods = [];
$others = [];
foreach ($services as $service) {
    $cat = strtolower(trim($service['CATEGORY']));
    if ($cat === 'room') {
        $rooms[] = $service;
    } elseif ($cat === 'food') {
        $foods[] = $service;
    } else {
        $others[] = $service;
    }
}

// Define hotel and food menu categories
$categories = [
    'Room',
    'Food',
    'Spa',
    'Event',
    'Other'
];

// To display the user's name in the profile icon or header, use:
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/include/Header.php'; ?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/include/slider.php'; ?>
<div class="container">
    <div class="menu-wrapper">
        <div class="menu-container" id="menuScroll">
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-child"></i>
                </div>
                <p>JUNIOR PALATE</p>
            </div>
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <p>À LA CARTE</p>
            </div>
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <p>GOURMET BOXES</p>
            </div>
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-star"></i>
                </div>
                <p>CHEF'S SPECIAL</p>
            </div>
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-crown"></i>
                </div>
                <p>ROYAL FEASTS</p>
            </div>
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-drumstick-bite"></i>
                </div>
                <p>POULTRY SELECTION</p>
            </div>
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-mortar-pestle"></i>
                </div>
                <p>ARTISAN SAUCES</p>
            </div>
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-wine-glass-alt"></i>
                </div>
                <p>SIGNATURE DRINKS</p>
            </div>
            <div class="menu-item">
                <div class="menu-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <p>GOURMET SIDES</p>
            </div>
        </div>
    </div>
</div>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/include/about.php'; ?>

<section class="services-section">
    <h2 class="section-title">Our Services</h2>
    <div class="container">
        <!-- Filter Buttons -->
        <div class="category-list" id="filter-buttons">
            <div class="category-item filter-btn active" data-filter="all">
                <i class="fa-solid fa-layer-group"></i>All
            </div>
            <?php foreach ($categories as $category): ?>
                <?php
                $icon = 'fa-utensils';
                switch (strtolower($category)) {
                    case 'room':
                        $icon = 'fa-bed';
                        break;
                    case 'food':
                        $icon = 'fa-burger';
                        break;
                    case 'spa':
                        $icon = 'fa-spa';
                        break;
                    case 'event':
                        $icon = 'fa-champagne-glasses';
                        break;
                    case 'other':
                        $icon = 'fa-star';
                        break;
                }
                ?>
                <div class="category-item filter-btn" data-filter="<?= strtolower($category) ?>">
                    <i class="fa-solid <?= $icon ?>"></i>
                    <?= htmlspecialchars($category) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <!-- All Services Grid -->
        <div class="services-grid" id="services-grid">
            <?php foreach ($services as $service): ?>
                <?php
                $cat = strtolower(trim($service['CATEGORY']));
                // ...existing code...
                ?>
                <div class="service-card" data-category="<?= $cat ?>">
                    <div class="service-img"
                        style="background: linear-gradient(rgba(0,0,0,0.2), rgba(0,0,0,0.2)), url('<?= htmlspecialchars($service['IMAGE_URL']) ?>'); background-size: cover; background-position: center; background-repeat: no-repeat; height: 200px;">
                    </div>
                    <div class="service-content">
                        <h3 class="service-title"><?= htmlspecialchars($service['TITLE']) ?></h3>
                        <div style="font-size:0.98rem;color:#4895ef;font-weight:600;margin-bottom:4px;">
                            <?= htmlspecialchars($service['CATEGORY']) ?>
                        </div>
                        <p class="service-description">
                            <?php
                            $desc = $service['DESCRIPTION'];
                            if ($desc instanceof OCILob) {
                                $desc = $desc->load();
                            }
                            echo htmlspecialchars($desc);
                            ?>
                        </p>
                        <!-- Book Now button removed -->
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<script>
    // Filtering logic
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.getAttribute('data-filter');
            document.querySelectorAll('.service-card').forEach(card => {
                if (filter === 'all' || card.getAttribute('data-category') === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
</script>
<script src="/Hotel-Restaurant/assets/Js/logo_show.js"></script>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/include/footer.php'; ?>
</body>

</html>