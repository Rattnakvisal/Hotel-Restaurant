// Slider Functionality
const slider = document.getElementById("slider");
const slides = document.querySelectorAll(".slide");
const prevBtn = document.getElementById("prev-btn");
const nextBtn = document.getElementById("next-btn");
const navDots = document.querySelectorAll(".nav-dot");

let currentSlide = 0;
let slideInterval;
const slideDuration = 5000; // 5 seconds

function showSlide(index) {
  // Hide all slides
  slides.forEach((slide) => slide.classList.remove("active"));
  navDots.forEach((dot) => dot.classList.remove("active"));

  // Show current slide
  slides[index].classList.add("active");
  navDots[index].classList.add("active");
  currentSlide = index;
}

function nextSlide() {
  let nextIndex = (currentSlide + 1) % slides.length;
  showSlide(nextIndex);
}

function prevSlide() {
  let prevIndex = (currentSlide - 1 + slides.length) % slides.length;
  showSlide(prevIndex);
}

function startSlider() {
  slideInterval = setInterval(nextSlide, slideDuration);
}

function resetSlider() {
  clearInterval(slideInterval);
  startSlider();
}

// Navigation dots
navDots.forEach((dot) => {
  dot.addEventListener("click", () => {
    let slideIndex = parseInt(dot.getAttribute("data-slide"));
    showSlide(slideIndex);
    resetSlider();
  });
});

// Next and previous buttons
nextBtn.addEventListener("click", () => {
  nextSlide();
  resetSlider();
});

prevBtn.addEventListener("click", () => {
  prevSlide();
  resetSlider();
});

// Keyboard navigation
document.addEventListener("keydown", (e) => {
  if (e.key === "ArrowRight") {
    nextSlide();
    resetSlider();
  } else if (e.key === "ArrowLeft") {
    prevSlide();
    resetSlider();
  }
});

// Initialize slider
showSlide(currentSlide);
startSlider();

// Pause slider on hover
slider.addEventListener("mouseenter", () => {
  clearInterval(slideInterval);
});

slider.addEventListener("mouseleave", () => {
  startSlider();
});

// Touch swipe for mobile
let touchStartX = 0;
let touchEndX = 0;

slider.addEventListener("touchstart", (e) => {
  touchStartX = e.changedTouches[0].screenX;
});

slider.addEventListener("touchend", (e) => {
  touchEndX = e.changedTouches[0].screenX;
  handleSwipe();
});

function handleSwipe() {
  const threshold = 50;

  if (touchEndX < touchStartX - threshold) {
    // Swipe left
    nextSlide();
    resetSlider();
  } else if (touchEndX > touchStartX + threshold) {
    // Swipe right
    prevSlide();
    resetSlider();
  }
}
