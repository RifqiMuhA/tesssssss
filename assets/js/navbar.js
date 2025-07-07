// Navbar JavaScript functionality

// Profile dropdown functionality
function toggleDropdown() {
  const dropdown = document.getElementById("profileDropdown");
  dropdown.classList.toggle("active");
}

// Mobile menu functionality
function toggleMobileMenu() {
  const toggle = document.querySelector(".mobile-menu-toggle");
  const overlay = document.getElementById("mobileNavOverlay");
  const menu = document.getElementById("mobileNavMenu");

  toggle.classList.toggle("active");
  overlay.classList.toggle("active");
  menu.classList.toggle("active");

  // Prevent body scroll when menu is open
  document.body.style.overflow = menu.classList.contains("active")
    ? "hidden"
    : "";
}

function closeMobileMenu() {
  const toggle = document.querySelector(".mobile-menu-toggle");
  const overlay = document.getElementById("mobileNavOverlay");
  const menu = document.getElementById("mobileNavMenu");

  toggle.classList.remove("active");
  overlay.classList.remove("active");
  menu.classList.remove("active");
  document.body.style.overflow = "";
}

// Initialize navbar functionality
function initNavbar() {
  // Close dropdowns when clicking outside
  document.addEventListener("click", function (event) {
    const dropdown = document.getElementById("profileDropdown");
    if (dropdown && !dropdown.contains(event.target)) {
      dropdown.classList.remove("active");
    }
  });

  // Close dropdowns on escape key
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      const dropdown = document.getElementById("profileDropdown");
      if (dropdown) {
        dropdown.classList.remove("active");
      }
      closeMobileMenu();
    }
  });

  // Handle window resize
  window.addEventListener("resize", function () {
    if (window.innerWidth > 768) {
      closeMobileMenu();
    }
  });

  console.log("Navbar initialized successfully!");
}

// Auto-initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  initNavbar();
});
