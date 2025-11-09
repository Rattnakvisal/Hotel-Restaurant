<?php

require_once '../config/connect.php';

// --- Fetch All Rooms (not just Available) ---
$sql = "SELECT * FROM rooms ORDER BY room_id DESC"; // <-- changed from WHERE status = 'Available'
$stmt = oci_parse($connection, $sql);
oci_execute($stmt);
$rooms = [];
while ($row = oci_fetch_assoc($stmt)) {
  $default_img = '/Hotel-Restaurant/assets/img/default-room.jpg';
  $img = trim($row['IMAGE_URL'] ?? '');
  $filename = $img ? basename($img) : '';
  $local_url = '/Hotel-Restaurant/assets/uploads/rooms/' . $filename; // <-- FIXED PATH
  $file_path = $_SERVER['DOCUMENT_ROOT'] . $local_url;

  // Always set IMAGE_URL_DISPLAY for template use
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
  $rooms[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Document</title>
  <link rel="stylesheet" href="/Hotel-Restaurant/assets/css/rooms.css">
</head>
<style>
  .container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
  }

  .hero {
    height: 60vh;
    background: linear-gradient(rgba(10, 30, 35, 0.7), rgba(10, 30, 35, 0.8)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    color: white;
    margin-bottom: 60px;
    position: relative;
    border-radius: 0 0 40px 40px;
  }

  .hero-content {
    max-width: 800px;
    padding: 20px;
  }

  .hero h1 {
    font-size: 3.5rem;
    margin-bottom: 20px;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
  }

  .hero p {
    font-size: 1.2rem;
    margin-bottom: 30px;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  }

  .cta-button {
    display: inline-block;
    background: var(--accent);
    color: var(--dark);
    text-decoration: none;
    padding: 12px 30px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: var(--transition);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  }

  .cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
    background: #f1c340;
  }

  .section-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 50px;
    color: var(--primary);
    position: relative;
    padding-bottom: 15px;
  }

  .section-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 4px;
    background: var(--accent);
    border-radius: 2px;
  }

  .room-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 40px;
    margin-top: 30px;
  }

  .room-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    height: 100%;
  }

  .room-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
  }

  .room-image {
    height: 250px;
    position: relative;
    overflow: hidden;
  }

  .room-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
  }

  .room-card:hover .room-image img {
    transform: scale(1.05);
  }

  .room-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: var(--accent);
    color: var(--dark);
    padding: 6px 15px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 0.9rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
  }

  .room-content {
    padding: 25px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
  }

  .room-name {
    font-size: 1.6rem;
    margin-bottom: 10px;
    color: var(--primary);
  }

  .room-price {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--secondary);
    margin-bottom: 15px;
  }

  .room-price span {
    font-size: 1rem;
    font-weight: 400;
    color: #777;
  }

  .room-desc {
    color: #555;
    margin-bottom: 20px;
    flex-grow: 1;
    line-height: 1.7;
  }

  .room-features {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 25px;
  }

  .feature-tag {
    background: var(--light);
    color: var(--secondary);
    padding: 6px 15px;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 500;
  }

  .room-btn {
    display: block;
    background: var(--primary);
    color: white;
    text-align: center;
    padding: 12px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
    cursor: pointer;
  }

  .room-btn:hover {
    background: var(--secondary);
  }
</style>

<body>
  <!-- Luxury Features -->
  <div class="container">
    <!-- Room Information -->
    <section class="room-info" id="rooms">
      <h2 class="section-title">Our Luxury Accommodations</h2>

      <div class="room-grid">
        <?php foreach ($rooms as $room): ?>
          <div class="room-card">
            <div class="room-image">
              <img
                src="<?php echo htmlspecialchars($room['IMAGE_URL_DISPLAY']); ?>"
                alt="Room Image"
                style="width:100%;height:250px;object-fit:cover;display:block;border-bottom:1px solid #eee;">
            </div>
            <div class="room-content">
              <h3 class="room-name"><?php echo htmlspecialchars($room['ROOM_NAME']); ?></h3>
              <div class="room-price">
                $<?php echo number_format($room['PRICE_PER_NIGHT'], 2); ?>
                <span>per night</span>
              </div>
              <p class="room-desc">
                <?php echo nl2br(htmlspecialchars($room['DESCRIPTION'])); ?>
              </p>
              <div class="room-features">
                <?php if (!empty($room['SLEEPS'])): ?>
                  <span class="feature-tag">Sleeps <?php echo htmlspecialchars($room['SLEEPS']); ?></span>
                <?php endif; ?>
              </div>
              <a href="/Hotel-Restaurant/user/room_details.php?id=<?= $room['ROOM_ID'] ?>" class="btn">
                <i class="fas fa-eye"></i> View Details
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</body>

</html>