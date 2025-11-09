<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Responsive Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #4361ee;
      --secondary: #3f37c9;
      --accent: #4895ef;
      --royal-gold: #d4af37;
      --royal-purple: #7851a9;
      --light: #f8f9fa;
      --dark: #212529;
      --gray: #6c757d;
      --sidebar-width: 250px;
      --header-height: 70px;
      --transition: all 0.3s ease;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #f5f7fb;
      color: var(--dark);
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* Menu Toggle */
    .menu-toggle {
      display: none;
      position: fixed;
      top: 15px;
      left: 15px;
      z-index: 1100;
      background: linear-gradient(135deg, var(--royal-purple), var(--primary));
      color: white;
      border: none;
      width: 45px;
      height: 45px;
      border-radius: 10px;
      font-size: 1.2rem;
      cursor: pointer;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      transition: var(--transition);
    }

    .menu-toggle:hover {
      transform: scale(1.05);
    }

    .dashboard-container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
      width: var(--sidebar-width);
      background: linear-gradient(180deg, var(--royal-purple), var(--primary));
      color: white;
      transition: var(--transition);
      height: 100vh;
      position: fixed;
      overflow-y: auto;
      z-index: 1000;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      transform: translateX(0);
    }

    .sidebar-brand {
      padding: 25px 20px;
      font-size: 1.4rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    }

    .sidebar-brand i {
      font-size: 1.8rem;
      margin-right: 12px;
      color: var(--royal-gold);
    }

    .nav-menu {
      list-style: none;
      padding: 25px 0;
    }

    .nav-item {
      margin: 8px 0;
    }

    .nav-item a {
      display: flex;
      align-items: center;
      padding: 15px 25px;
      color: rgba(255, 255, 255, 0.85);
      text-decoration: none;
      font-size: 1rem;
      transition: var(--transition);
      border-left: 4px solid transparent;
    }

    .nav-item a i {
      font-size: 1.2rem;
      width: 35px;
      transition: var(--transition);
    }

    .nav-item a:hover {
      background: rgba(255, 255, 255, 0.12);
      color: white;
      border-left: 4px solid var(--royal-gold);
    }

    .nav-item a:hover i {
      transform: scale(1.15);
      color: var(--royal-gold);
    }

    .nav-item.active a {
      background: rgba(255, 255, 255, 0.15);
      color: white;
      border-left: 4px solid var(--royal-gold);
    }

    .nav-item.active a i {
      color: var(--royal-gold);
    }

    /* Content Area */
    .content {
      flex: 1;
      padding: 20px;
      margin-left: var(--sidebar-width);
      transition: var(--transition);
    }

    .content-header {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      margin-bottom: 30px;
    }

    .content-header h1 {
      color: var(--royal-purple);
      font-size: 1.8rem;
      margin-bottom: 10px;
    }

    .content-header p {
      color: var(--gray);
      line-height: 1.6;
    }

    /* Cards */
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .card {
      background: white;
      border-radius: 10px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      transition: var(--transition);
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }

    .card-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--dark);
    }

    .card-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }

    .icon-blue {
      background: rgba(67, 97, 238, 0.15);
      color: var(--primary);
    }

    .icon-purple {
      background: rgba(120, 81, 169, 0.15);
      color: var(--royal-purple);
    }

    .icon-gold {
      background: rgba(212, 175, 55, 0.15);
      color: var(--royal-gold);
    }

    .card-value {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 10px;
      color: var(--dark);
    }

    .card-footer {
      color: var(--gray);
      font-size: 0.9rem;
    }

    /* Recent Activity */
    .recent-activity {
      background: white;
      border-radius: 10px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .section-title {
      font-size: 1.4rem;
      margin-bottom: 20px;
      color: var(--dark);
      display: flex;
      align-items: center;
    }

    .section-title i {
      margin-right: 10px;
      color: var(--royal-gold);
    }

    .activity-list {
      list-style: none;
    }

    .activity-item {
      display: flex;
      padding: 15px 0;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      flex-shrink: 0;
    }

    .activity-content {
      flex: 1;
    }

    .activity-title {
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark);
    }

    .activity-time {
      font-size: 0.85rem;
      color: var(--gray);
    }

    /* Mobile Styles */
    @media (max-width: 992px) {
      .menu-toggle {
        display: block;
      }

      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .content {
        margin-left: 0;
        padding-top: 80px;
      }

      .overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: none;
      }

      .overlay.active {
        display: block;
      }
    }

    /* Animation for sidebar */
    .sidebar {
      transition: transform 0.3s ease-out;
    }
  </style>
</head>

<body>
  <button class="menu-toggle">
    <i class="fas fa-bars"></i>
  </button>

  <div class="overlay"></div>

  <div class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
      <div class="sidebar-brand"><i class="fas fa-crown"></i>RoyalNest</div>
      <ul class="nav-menu">
        <li class="nav-item active">
          <a href="../dashboard/dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a href="../Menu/manage_menu.php">
            <i class="fas fa-utensils"></i> Manage Menu
          </a>
        </li>
        <li class="nav-item">
          <a href="../User/manage_contact.php">
            <i class="fas fa-address-book"></i> Manage Contact
          </a>
        </li>
        <li class="nav-item">
          <a href="../Room/manage_room.php">
            <i class="fas fa-hotel"></i> Manage room
          </a>
        </li>
        <li class="nav-item">
          <a href="../Menu/manage_order.php">
            <i class="fas fa-shopping-cart"></i> Orders
          </a>
        </li>
        <li class="nav-item">
          <a href="../Room/manage_booking.php">
            <i class="fas fa-calendar-check"></i> Manage booking
          </a>
        </li>
        <li class="nav-item">
          <a href="../Room/Products.php">
            <i class="fas fa-bed"></i> Products
          </a>
        </li>
        <li class="nav-item">
          <a href="../User/manage_payment.php">
            <i class="fas fa-credit-card"></i> User Payments
          </a>
        </li>
        <li class="nav-item">
          <a href="../User/manage_user.php">
            <i class="fas fa-users"></i> Manage user
          </a>
        </li>
        <li class="nav-item">
          <a href="../../auth/logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const menuToggle = document.querySelector('.menu-toggle');
      const sidebar = document.querySelector('.sidebar');
      const overlay = document.querySelector('.overlay');

      // Toggle sidebar on menu button click
      menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
      });

      // Close sidebar when clicking on overlay
      overlay.addEventListener('click', function() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
      });

      // Handle sidebar menu item clicks
      const navItems = document.querySelectorAll('.nav-item');
      navItems.forEach(item => {
        item.addEventListener('click', function() {
          // Remove active class from all items
          navItems.forEach(i => i.classList.remove('active'));
          // Add active class to clicked item
          this.classList.add('active');

          // On mobile, close sidebar after selection
          if (window.innerWidth < 992) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
          }
        });
      });

      // Close sidebar when window is resized above mobile breakpoint
      window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
          sidebar.classList.remove('active');
          overlay.classList.remove('active');
        }
      });
    });
  </script>
</body>

</html>