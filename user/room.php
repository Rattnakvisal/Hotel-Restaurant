<?php
session_start();
require_once '../config/connect.php';
if (!isset($_SESSION['user_id'])) {
  header("Location: ../auth/login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">

</html>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Dashboard</title>
  <link rel="stylesheet" href="/Hotel-Restaurant/assets/Css/styles.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
</head>
<style>
  /* Button Styles */
  .btn {
    display: inline-block;
    padding: 16px 40px;
    background: #d4af37;
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    font-size: 1.1rem;
    border-radius: 6px;
    border: 2px solid #d4af37;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 14px;
  }

  .btn:hover,
  .btn:focus {
    color: #000000;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(67, 97, 238, 0.1);
  }

  .category-list {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 32px;
    justify-content: center;
  }

  .category-item {
    display: flex;
    align-items: center;
    background: #f7f8fd;
    border-radius: 8px;
    padding: 10px 18px;
    font-weight: 600;
    color: #3f37c9;
    font-size: 1.08rem;
    box-shadow: 0 2px 8px rgba(67, 97, 238, 0.08);
    transition: background 0.18s;
  }

  .category-item i {
    margin-right: 10px;
    font-size: 1.3rem;
  }

  /* Active state for filter buttons */
  .filter-btn.active {
    background: #3f37c9;
    color: #ffffff;
  }

  /* Loading Overlay Styles */
  .loading-overlay {
    position: fixed;
    z-index: 9999;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #232526 0%, #8e44ad 100%);
    transition: opacity 0.7s cubic-bezier(0.4, 0, 0.2, 1);
    opacity: 1;
  }

  .loading-overlay.hide {
    opacity: 0;
    pointer-events: none;
  }

  .loading-container {
    text-align: center;
    color: #fff;
  }

  .logo {
    font-size: 2.8rem;
    font-weight: 900;
    letter-spacing: 3px;
    margin-bottom: 24px;
    font-family: "Playfair Display", serif;
    text-shadow: 0 4px 24px #8e44ad, 0 1px 0 #fff;
  }

  .spinner {
    margin: 0 auto 32px auto;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .spinner div {
    width: 80px;
    height: 80px;
    border: 8px solid rgba(255, 255, 255, 0.18);
    border-top: 8px solid #fff;
    border-radius: 50%;
    animation: spin 1.1s linear infinite;
    box-shadow: 0 0 32px #8e44ad, 0 0 8px #fff;
  }

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }

  .loading-text {
    font-size: 1.25rem;
    letter-spacing: 1.2px;
    margin-top: 8px;
    opacity: 0.92;
  }

  /* Banner Styles */
  .room-banner {
    position: relative;
    height: 85vh;
    overflow: hidden;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.3));
  }

  .banner-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url("https://images.unsplash.com/photo-1611892440504-42a792e24d32?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1770&q=80");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    z-index: -1;
    transition: transform 10s ease;
  }

  .room-banner:hover .banner-image {
    transform: scale(1.05);
  }

  .banner-content {
    text-align: center;
    color: #fff;
    max-width: 900px;
    padding: 0 20px;
    z-index: 2;
  }

  .banner-title {
    font-family: "Playfair Display", serif;
    font-size: 4.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    line-height: 1.1;
  }

  .banner-subtitle {
    font-size: 1.4rem;
    font-weight: 400;
    margin-bottom: 40px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
  }

  .btn:hover {
    background: transparent;
    color: #d4af37;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
  }

  /* Luxury Features */
  .luxury-features {
    display: flex;
    justify-content: center;
    gap: 30px;
    padding: 25px 20px;
    background: #fff;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    max-width: 1200px;
    margin: -50px auto 0;
    position: relative;
    z-index: 10;
    border-radius: 8px;
    transform: translateY(-50px);
  }

  .feature {
    display: flex;
    align-items: center;
    gap: 15px;
  }

  .feature-icon {
    width: 50px;
    height: 50px;
    background: #f8f4e9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d4af37;
    font-size: 20px;
  }

  .feature-text {
    font-size: 0.9rem;
    font-weight: 500;
    color: #333;
  }

  .feature-text strong {
    display: block;
    font-size: 1.1rem;
    margin-bottom: 3px;
    color: #222;
  }

  /* Responsive Design */
  @media (max-width: 992px) {
    .banner-title {
      font-size: 3.5rem;
    }

    .luxury-features {
      flex-wrap: wrap;
      max-width: 90%;
    }
  }

  @media (max-width: 768px) {
    .banner-title {
      font-size: 2.8rem;
    }

    .banner-subtitle {
      font-size: 1.1rem;
    }

    .luxury-features {
      padding: 20px 15px;
      gap: 15px;
    }

    .feature {
      flex: 1 1 calc(50% - 15px);
    }
  }

  @media (max-width: 576px) {
    .banner-title {
      font-size: 2.2rem;
    }

    .feature {
      flex: 1 1 100%;
    }

    .btn {
      padding: 14px 30px;
      font-size: 1rem;
    }
  }
</style>

<body>
  <!-- Enhanced Loading Overlay -->
  <div
    class="loading-overlay"
    id="loadingOverlay"
    style="
        position: fixed;
        z-index: 9999;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #232526 0%, #8e44ad 100%);
        transition: opacity 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        opacity: 1;
      ">
    <div class="loading-container" style="text-align: center; color: #fff">
      <div
        class="spinner"
        style="
            margin: 0 auto 32px auto;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
          ">
        <div
          style="
              width: 80px;
              height: 80px;
              border: 8px solid rgba(255, 255, 255, 0.18);
              border-top: 8px solid #fff;
              border-radius: 50%;
              animation: spin 1.1s linear infinite;
              box-shadow: 0 0 32px #8e44ad, 0 0 8px #fff;
            "></div>
      </div>
      <div
        class="loading-text"
        style="
            font-size: 1.25rem;
            letter-spacing: 1.2px;
            margin-top: 8px;
            opacity: 0.92;
          ">
        Welcome to RoyalNest. Preparing your royal experience...
      </div>
    </div>
    <style>
      @keyframes spin {
        0% {
          transform: rotate(0deg);
        }

        100% {
          transform: rotate(360deg);
        }
      }
    </style>
  </div>
  <script>
    window.addEventListener("DOMContentLoaded", function() {
      setTimeout(function() {
        const overlay = document.getElementById("loadingOverlay");
        overlay.style.opacity = "0";
        overlay.style.pointerEvents = "none";
        overlay.addEventListener(
          "transitionend",
          function() {
            overlay.parentNode.removeChild(overlay);
          }, {
            once: true,
          }
        );
      }, 1800);
    });
  </script>
  <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/include/navbar.php'; ?>
  <!-- Room Banner -->
  <div class="container">
    <section class="room-banner">
      <div class="banner-image"></div>
      <div class="banner-content">
        <h1 class="banner-title">Experience Unparalleled Luxury</h1>
        <p class="banner-subtitle">
          Indulge in our exquisite collection of suites and villas, designed
          with the finest materials and breathtaking views of the ocean and
          mountains.
        </p>
        <a href="../user/bookings.php" class="btn">Reserve Your Suite</a>
      </div>
    </section>

    <!-- Luxury Features -->
    <div class="luxury-features">
      <div class="feature">
        <div class="feature-icon">
          <i class="fas fa-bed"></i>
        </div>
        <div class="feature-text">
          <strong>Premium Comfort</strong>
          Handcrafted king beds with luxury linens
        </div>
      </div>

      <div class="feature">
        <div class="feature-icon">
          <i class="fas fa-spa"></i>
        </div>
        <div class="feature-text">
          <strong>Spa Bathrooms</strong>
          Marble bathrooms with deep soaking tubs
        </div>
      </div>

      <div class="feature">
        <div class="feature-icon">
          <i class="fas fa-wifi"></i>
        </div>
        <div class="feature-text">
          <strong>Connectivity</strong>
          High-speed Wi-Fi & smart room controls
        </div>
      </div>

      <div class="feature">
        <div class="feature-icon">
          <i class="fas fa-umbrella-beach"></i>
        </div>
        <div class="feature-text">
          <strong>Exclusive Access</strong>
          Private beach and pool areas
        </div>
      </div>
    </div>

    <?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Hotel-Restaurant/include/rooms.php'; ?>
  </div>
  <?php require_once '../include/footer.php'; ?>
</body>

</html>