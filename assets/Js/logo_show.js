document.addEventListener("DOMContentLoaded", function () {
  const menuContainer = document.getElementById("menuScroll");
  if (!menuContainer) return;

  // Duplicate menu items for seamless scroll
  menuContainer.innerHTML += menuContainer.innerHTML;

  // Set up styles for seamless horizontal scroll
  menuContainer.style.overflowX = "hidden";
  menuContainer.style.whiteSpace = "nowrap";
  menuContainer.style.display = "flex";
  menuContainer.style.flexWrap = "nowrap";
  menuContainer.style.scrollBehavior = "auto";

  // Make menu items inline for horizontal scroll
  const items = menuContainer.querySelectorAll(".menu-item");
  items.forEach((item) => {
    item.style.flex = "0 0 auto";
  });

  // Responsive scroll speed
  function getScrollSpeed() {
    return window.innerWidth <= 768 ? 0.15 : 0.4; // slower on mobile, faster on desktop
  }

  let scrollAmount = 0;
  let scrollSpeed = getScrollSpeed();

  // Update scroll speed on resize
  window.addEventListener("resize", function () {
    scrollSpeed = getScrollSpeed();
  });

  function animateMenuScroll() {
    scrollAmount += scrollSpeed;
    if (scrollAmount >= menuContainer.scrollWidth / 2) {
      scrollAmount = 0;
    }
    menuContainer.scrollLeft = scrollAmount;
    requestAnimationFrame(animateMenuScroll);
  }

  animateMenuScroll();
});
