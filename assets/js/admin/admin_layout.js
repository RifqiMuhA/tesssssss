// Admin Layout JavaScript

document.addEventListener("DOMContentLoaded", function () {
  initializeSidebar();
  initializeTooltips();
  initializeConfirmations();
});

function initializeSidebar() {
  const sidebar = document.getElementById("adminSidebar");
  const sidebarToggle = document.getElementById("sidebarToggle");
  const sidebarOverlay = document.getElementById("sidebarOverlay");
  const mainContent = document.querySelector(".admin-main");

  // Create mobile menu button
  createMobileMenuButton();

  // Toggle sidebar function
  function toggleSidebar() {
    if (window.innerWidth > 1024) {
      // Desktop: collapse sidebar
      sidebar.classList.toggle("collapsed");
      mainContent.classList.toggle("collapsed");
    } else {
      // Mobile: slide sidebar
      sidebar.classList.toggle("mobile-active");
      sidebar.classList.toggle("mobile-collapsed");
      sidebarOverlay.classList.toggle("active");
      mainContent.classList.toggle("mobile-expanded");
    }
  }

  // Sidebar toggle event
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", toggleSidebar);
  }

  // Mobile menu button event
  const mobileMenuButton = document.getElementById("mobileMenuButton");
  if (mobileMenuButton) {
    mobileMenuButton.addEventListener("click", toggleSidebar);
  }

  // Overlay click to close sidebar (mobile only)
  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", function () {
      if (window.innerWidth <= 1024) {
        sidebar.classList.remove("mobile-active");
        sidebar.classList.add("mobile-collapsed");
        sidebarOverlay.classList.remove("active");
        mainContent.classList.remove("mobile-expanded");
      }
    });
  }

  // Handle window resize
  window.addEventListener("resize", function () {
    if (window.innerWidth > 1024) {
      // Desktop mode
      sidebar.classList.remove("mobile-active", "mobile-collapsed");
      sidebarOverlay.classList.remove("active");
      mainContent.classList.remove("mobile-expanded");
    } else {
      // Mobile mode
      sidebar.classList.remove("collapsed");
      sidebar.classList.add("mobile-collapsed");
      mainContent.classList.remove("collapsed");
      mainContent.classList.add("mobile-expanded");
      sidebarOverlay.classList.remove("active");
    }
  });

  // Initial setup based on screen size
  if (window.innerWidth <= 1024) {
    sidebar.classList.add("mobile-collapsed");
    mainContent.classList.add("mobile-expanded");
  }
}

function createMobileMenuButton() {
  // Check if button already exists
  if (document.getElementById("mobileMenuButton")) {
    return;
  }

  const button = document.createElement("button");
  button.id = "mobileMenuButton";
  button.className = "mobile-menu-button";
  button.innerHTML = `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    `;

  document.body.appendChild(button);
}

function initializeTooltips() {
  // Simple tooltip implementation
  const elements = document.querySelectorAll("[title]");

  elements.forEach((element) => {
    let tooltip = null;

    element.addEventListener("mouseenter", function (e) {
      const title = this.getAttribute("title");
      if (!title) return;

      // Remove title to prevent default tooltip
      this.setAttribute("data-original-title", title);
      this.removeAttribute("title");

      // Create tooltip
      tooltip = document.createElement("div");
      tooltip.className = "custom-tooltip";
      tooltip.textContent = title;
      tooltip.style.cssText = `
                position: absolute;
                background: #1e293b;
                color: white;
                padding: 0.5rem 0.75rem;
                border-radius: 0.375rem;
                font-size: 0.75rem;
                z-index: 9999;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.2s ease;
            `;

      document.body.appendChild(tooltip);

      // Position tooltip
      const rect = this.getBoundingClientRect();
      tooltip.style.left =
        rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px";
      tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + "px";

      // Show tooltip
      setTimeout(() => {
        tooltip.style.opacity = "1";
      }, 10);
    });

    element.addEventListener("mouseleave", function () {
      // Restore title
      const originalTitle = this.getAttribute("data-original-title");
      if (originalTitle) {
        this.setAttribute("title", originalTitle);
        this.removeAttribute("data-original-title");
      }

      // Remove tooltip
      if (tooltip) {
        tooltip.remove();
        tooltip = null;
      }
    });
  });
}

function initializeConfirmations() {
  // Handle confirmation dialogs
  const confirmButtons = document.querySelectorAll('[onclick*="confirm"]');

  confirmButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      const onclickAttr = this.getAttribute("onclick");
      if (onclickAttr && onclickAttr.includes("confirm")) {
        e.preventDefault();

        // Extract confirm message
        const match = onclickAttr.match(/confirm\(['"`]([^'"`]+)['"`]\)/);
        const message = match ? match[1] : "Are you sure?";

        if (confirm(message)) {
          // Get the form and submit it
          const form = this.closest("form");
          if (form) {
            form.submit();
          }
        }
      }
    });

    // Remove onclick attribute to prevent default behavior
    button.removeAttribute("onclick");
  });
}

// Utility functions
function showAlert(message, type = "info") {
  const alertContainer = document.createElement("div");
  alertContainer.className = `alert alert-${type}`;
  alertContainer.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        z-index: 9999;
        min-width: 300px;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
  alertContainer.textContent = message;

  document.body.appendChild(alertContainer);

  // Show alert
  setTimeout(() => {
    alertContainer.style.opacity = "1";
    alertContainer.style.transform = "translateX(0)";
  }, 10);

  // Auto remove after 5 seconds
  setTimeout(() => {
    alertContainer.style.opacity = "0";
    alertContainer.style.transform = "translateX(100%)";
    setTimeout(() => {
      alertContainer.remove();
    }, 300);
  }, 5000);
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Export functions for use in other scripts
window.AdminLayout = {
  showAlert,
  debounce,
};
