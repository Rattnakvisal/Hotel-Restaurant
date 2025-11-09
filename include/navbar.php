<link rel="stylesheet" href="../assets/Css/styles.css" />
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link
  href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap"
  rel="stylesheet" />

<div class="container">
  <nav class="navbar">
    <div class="navbar-brand">
      <div class="logo">
        <i class="fas fa-crown"></i>
      </div>
      <span class="brand-text">RoyalNest</span>
    </div>

    <div class="hamburger" id="hamburger">
      <i class="fas fa-bars"></i>
    </div>

    <ul class="nav-links" id="nav-links">
      <li>
        <a href="../user/index.php" class="active">Home</a>
      </li>

      <!-- Rooms Dropdown -->
      <li class="dropdown">
        <a
          href="../user/room.php"
          class="dropdown-toggle"
          id="rooms-dropdown-toggle">
          Rooms <i class="fas fa-chevron-down" style="font-size: 0.9em"></i>
        </a>
        <div class="dropdown-menu" id="rooms-dropdown-menu">
          <a href="#">Deluxe Suite</a>
          <a href="#">Ocean View Villa</a>
          <a href="#">Presidential Suite</a>
          <a href="#">Family Apartment</a>
          <a href="#">Honeymoon Package</a>
        </div>
      </li>

      <!-- Dining Dropdown -->
      <li class="dropdown">
        <a
          href="../user/dining.php"
          class="dropdown-toggle"
          id="dining-dropdown-toggle">
          Dining
          <i class="fas fa-chevron-down" style="font-size: 0.9em"></i>
        </a>
        <div class="dropdown-menu" id="dining-dropdown-menu">
          <a href="#">Fine Dining</a>
          <a href="#">Seafood Restaurant</a>
          <a href="#">Poolside Bar</a>
          <a href="#">Breakfast Buffet</a>
          <a href="#">Room Service</a>
        </div>
      </li>
      <li>
        <a href="../user/contact.php">Contact</a>
      </li>

      <!-- Mobile Menu Icons -->
      <div class="mobile-icons">
        <div class="icon-group">
          <a href="#" id="mobile-search-toggle">
            <i class="fas fa-search"></i>
            <span>Search</span>
          </a>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="#">
              <i class="fas fa-user-circle"></i>
              <span><?= htmlspecialchars($_SESSION['name']) ?></span>
            </a>
          <?php endif; ?>
        </div>
        <div class="mobile-logout">
          <a href="../auth/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </div>
      </div>
    </ul>

    <!-- Desktop Icons (hidden on mobile) -->
    <div class="nav-icons">
      <div class="nav-search-wrapper">
        <form
          class="navbar-search"
          id="navbar-search"
          action="/search"
          method="get">
          <input
            type="text"
            name="q"
            placeholder="Search..."
            autocomplete="off" />
          <button type="submit" aria-label="Submit search">
            <i class="fas fa-arrow-right"></i>
          </button>
        </form>
        <a
          href="#"
          id="search-toggle"
          aria-label="Open search"
          aria-expanded="false">
          <i class="fas fa-search"></i>
        </a>
      </div>
      <!-- Profile Icon and Name -->
      <?php if (isset($_SESSION['user_id'])): ?>
        <li style="display:flex;align-items:center;gap:8px;">
          <span style="display:flex;align-items:center;">
            <i class="fas fa-user-circle" style="font-size:1.5em;color:#4361ee;margin-right:6px;"></i>
            <span style="font-weight:600;color:#222;">
              <?= htmlspecialchars($_SESSION['name']) ?>
            </span>
          </span>
        </li>
      <?php endif; ?>
      <a href="../auth/logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>

    </div>

    <div class="navbar-overlay" id="navbar-overlay"></div>
  </nav>
  <!-- Mobile Search Overlay -->
  <div class="mobile-search-overlay" id="mobile-search-overlay">
    <button
      class="mobile-search-close"
      id="mobile-search-close"
      aria-label="Close search">
      <i class="fas fa-times"></i>
    </button>
    <form class="mobile-search-form" action="/search" method="get">
      <input
        type="text"
        name="q"
        placeholder="Search..."
        autocomplete="off" />
      <button type="submit">Search</button>
    </form>
  </div>
</div>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    // Navbar Toggle
    const hamburger = document.getElementById("hamburger");
    const navLinks = document.getElementById("nav-links");
    const navbarOverlay = document.getElementById("navbar-overlay");

    hamburger.addEventListener("click", () => {
      navLinks.classList.toggle("active");
      navbarOverlay.classList.toggle("active");
      document.body.style.overflow = navLinks.classList.contains("active") ?
        "hidden" :
        "";

      // Change hamburger icon to X when active
      if (navLinks.classList.contains("active")) {
        hamburger.innerHTML = '<i class="fas fa-times"></i>';
      } else {
        hamburger.innerHTML = '<i class="fas fa-bars"></i>';
      }
    });

    navbarOverlay.addEventListener("click", () => {
      navLinks.classList.remove("active");
      navbarOverlay.classList.remove("active");
      document.body.style.overflow = "";
      hamburger.innerHTML = '<i class="fas fa-bars"></i>';
    });

    // Dropdown Toggles
    const dropdownToggles = document.querySelectorAll(".dropdown-toggle");

    dropdownToggles.forEach((toggle) => {
      toggle.addEventListener("click", (e) => {
        if (window.innerWidth <= 992) {
          e.preventDefault();
          const dropdownMenu = toggle.nextElementSibling;
          toggle.classList.toggle("active");
          dropdownMenu.classList.toggle("active");
        }
      });
    });

    // Desktop Search Toggle
    const searchToggle = document.getElementById("search-toggle");
    const navbarSearch = document.getElementById("navbar-search");

    searchToggle.addEventListener("click", (e) => {
      e.preventDefault();
      navbarSearch.classList.toggle("active");

      // Update aria-expanded attribute
      const expanded = navbarSearch.classList.contains("active") ?
        "true" :
        "false";
      searchToggle.setAttribute("aria-expanded", expanded);
    });

    // Mobile Search Toggle
    const mobileSearchToggle = document.getElementById(
      "mobile-search-toggle"
    );
    const mobileSearchOverlay = document.getElementById(
      "mobile-search-overlay"
    );
    const mobileSearchClose = document.getElementById(
      "mobile-search-close"
    );

    mobileSearchToggle.addEventListener("click", (e) => {
      e.preventDefault();
      mobileSearchOverlay.classList.add("active");
      document.body.style.overflow = "hidden";
    });

    mobileSearchClose.addEventListener("click", () => {
      mobileSearchOverlay.classList.remove("active");
      document.body.style.overflow = "";
    });

    // Close mobile menu when clicking outside
    document.addEventListener("click", (e) => {
      if (
        navLinks.classList.contains("active") &&
        !navLinks.contains(e.target) &&
        !hamburger.contains(e.target)
      ) {
        navLinks.classList.remove("active");
        navbarOverlay.classList.remove("active");
        document.body.style.overflow = "";
        hamburger.innerHTML = '<i class="fas fa-bars"></i>';
      }
    });
  });
</script>