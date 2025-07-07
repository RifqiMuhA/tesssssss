// Admin Forum JavaScript with AJAX

document.addEventListener("DOMContentLoaded", function () {
  initializeForumPage();
});

let currentPage = 1;
let currentView = "threads";
let searchTimeout;
let isLoading = false;

function initializeForumPage() {
  setupViewToggle();
  setupAjaxFilters();
  setupConfirmations();
  loadForum(); // Initial load
}

function setupViewToggle() {
  const toggleButtons = document.querySelectorAll(".toggle-btn");

  toggleButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      const view = this.getAttribute("data-view");
      if (view !== currentView) {
        currentView = view;
        currentPage = 1;

        // Update active state
        toggleButtons.forEach((btn) => btn.classList.remove("active"));
        this.classList.add("active");

        // Update search placeholder
        const searchInput = document.getElementById("searchInput");
        if (searchInput) {
          searchInput.placeholder = `Search ${view}...`;
        }

        loadForum();
      }
    });
  });
}

function setupAjaxFilters() {
  const searchInput = document.getElementById("searchInput");
  const categorySelect = document.getElementById("categoryFilter");

  // Debounced search
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadForum();
      }, 500);
    });
  }

  // Filter changes
  if (categorySelect) {
    categorySelect.addEventListener("change", function () {
      currentPage = 1;
      loadForum();
    });
  }

  // Clear search functionality
  if (searchInput) {
    const clearBtn = document.createElement("button");
    clearBtn.type = "button";
    clearBtn.className = "search-clear";
    clearBtn.innerHTML = "×";
    clearBtn.style.cssText = `
      position: absolute;
      right: 2.5rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #64748b;
      cursor: pointer;
      padding: 0.25rem;
      font-size: 1.25rem;
      line-height: 1;
      opacity: 0;
      transition: opacity 0.2s ease;
    `;

    clearBtn.addEventListener("click", function () {
      searchInput.value = "";
      currentPage = 1;
      loadForum();
      this.style.opacity = "0";
    });

    searchInput.addEventListener("input", function () {
      clearBtn.style.opacity = this.value ? "1" : "0";
    });

    const searchGroup = searchInput.closest(".search-group");
    if (searchGroup) {
      searchGroup.style.position = "relative";
      searchGroup.appendChild(clearBtn);
    }
  }
}

function loadForum(page = currentPage) {
  if (isLoading) return;

  isLoading = true;
  currentPage = page;

  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");
  const loadingIndicator = document.getElementById("loadingIndicator");
  const forumContainer = document.getElementById("forumListContainer");
  const paginationContainer = document.getElementById("paginationContainer");

  // Show loading
  loadingIndicator.style.display = "block";
  forumContainer.style.opacity = "0.5";

  const requestData = {
    action: "search_forum",
    search: searchInput ? searchInput.value : "",
    category: categoryFilter ? categoryFilter.value : "",
    view: currentView,
    page: page,
  };

  fetch("../ajax/admin_forum_ajax.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(requestData),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        forumContainer.innerHTML = data.html;
        paginationContainer.innerHTML = data.pagination;

        // Re-setup action confirmations for new content
        setupActionConfirmations();

        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set("view", currentView);
        url.searchParams.set("page", page);
        window.history.replaceState({}, "", url);
      } else {
        showAlert(data.error || "Terjadi kesalahan saat memuat data", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showAlert("Terjadi kesalahan jaringan", "error");
    })
    .finally(() => {
      isLoading = false;
      loadingIndicator.style.display = "none";
      forumContainer.style.opacity = "1";
    });
}

function loadPage(page) {
  loadForum(page);
}

function setupActionConfirmations() {
  // Handle AJAX actions for dynamically loaded content
  document.addEventListener("click", function (e) {
    const button = e.target.closest("button.ajax-action");
    if (!button) return;

    const action = button.getAttribute("data-action");
    const threadId = button.getAttribute("data-thread-id");
    const postId = button.getAttribute("data-post-id");

    let confirmMessage = "";
    switch (action) {
      case "pin_thread":
        confirmMessage = "Yakin ingin mengubah status pin thread ini?";
        break;
      case "lock_thread":
        confirmMessage = "Yakin ingin mengubah status lock thread ini?";
        break;
      case "toggle_thread_status":
        confirmMessage = "Yakin ingin mengubah status thread ini?";
        break;
      case "toggle_post_status":
        confirmMessage = "Yakin ingin mengubah status post ini?";
        break;
      default:
        return;
    }

    e.preventDefault();

    if (confirm(confirmMessage)) {
      performAjaxAction(
        action,
        { thread_id: threadId, post_id: postId },
        button
      );
    }
  });
}

function performAjaxAction(action, params, button) {
  // Disable button during request
  const originalContent = button.innerHTML;
  button.disabled = true;
  button.innerHTML = '<div class="mini-spinner"></div>';

  const requestData = {
    action: action,
    ...params,
  };

  // Clean up undefined values
  Object.keys(requestData).forEach((key) => {
    if (requestData[key] === undefined || requestData[key] === null) {
      delete requestData[key];
    }
  });

  fetch("../ajax/admin_forum_ajax.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(requestData),
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        showAlert(data.message, "success");
        // Reload forum to reflect changes
        loadForum(currentPage);
      } else {
        showAlert(data.error || "Terjadi kesalahan", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showAlert("Terjadi kesalahan jaringan", "error");
    })
    .finally(() => {
      // Re-enable button
      button.disabled = false;
      button.innerHTML = originalContent;
    });
}

function setupConfirmations() {
  setupActionConfirmations();
}

function setupForumInteractions() {
  // Quick preview on hover
  const forumItems = document.querySelectorAll(".forum-item");

  forumItems.forEach((item) => {
    const content = item.querySelector(".item-content");
    const excerpt = item.querySelector(".item-excerpt");

    if (content && excerpt) {
      let isExpanded = false;
      let originalText = excerpt.textContent;

      item.addEventListener("mouseenter", function () {
        if (!isExpanded && originalText.length > 200) {
          setTimeout(() => {
            if (this.matches(":hover")) {
              excerpt.style.transition = "all 0.3s ease";
              excerpt.style.maxHeight = "none";
            }
          }, 500);
        }
      });

      item.addEventListener("mouseleave", function () {
        if (!isExpanded) {
          excerpt.style.maxHeight = "";
        }
      });
    }

    // Mark as read functionality
    const viewButton = item.querySelector(".btn-action.view");
    if (viewButton) {
      viewButton.addEventListener("click", function () {
        markAsRead(item);
      });
    }
  });
}

function markAsRead(item) {
  item.style.opacity = "0.7";
  item.classList.add("read");

  // Store in localStorage for persistence
  const itemId = item.getAttribute("data-id");
  if (itemId) {
    const readItems = JSON.parse(
      localStorage.getItem("readForumItems") || "[]"
    );
    if (!readItems.includes(itemId)) {
      readItems.push(itemId);
      localStorage.setItem("readForumItems", JSON.stringify(readItems));
    }
  }
}

function showAlert(message, type = "info") {
  // Remove existing alerts
  const existingAlerts = document.querySelectorAll(".alert-toast");
  existingAlerts.forEach((alert) => alert.remove());

  const alert = document.createElement("div");
  alert.className = `alert-toast alert-${type}`;
  alert.style.cssText = `
        position: fixed;
        top: 2rem;
        right: 2rem;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
        padding: 1rem;
        background: ${getAlertColor(type)};
        color: ${getAlertTextColor(type)};
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        border: 1px solid ${getAlertBorderColor(type)};
        font-weight: 500;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
    `;
  alert.textContent = message;

  document.body.appendChild(alert);

  // Show alert
  setTimeout(() => {
    alert.style.opacity = "1";
    alert.style.transform = "translateX(0)";
  }, 10);

  // Auto remove after 5 seconds
  setTimeout(() => {
    alert.style.opacity = "0";
    alert.style.transform = "translateX(100%)";
    setTimeout(() => {
      alert.remove();
    }, 300);
  }, 5000);

  // Click to dismiss
  alert.addEventListener("click", function () {
    this.style.opacity = "0";
    this.style.transform = "translateX(100%)";
    setTimeout(() => {
      this.remove();
    }, 300);
  });
}

function getAlertColor(type) {
  const colors = {
    success: "#f0fdf4",
    error: "#fef2f2",
    warning: "#fffbeb",
    info: "#eff6ff",
  };
  return colors[type] || colors.info;
}

function getAlertTextColor(type) {
  const colors = {
    success: "#166534",
    error: "#dc2626",
    warning: "#d97706",
    info: "#2563eb",
  };
  return colors[type] || colors.info;
}

function getAlertBorderColor(type) {
  const colors = {
    success: "#bbf7d0",
    error: "#fecaca",
    warning: "#fed7aa",
    info: "#bfdbfe",
  };
  return colors[type] || colors.info;
}

// Make functions global for HTML onclick handlers
window.loadPage = loadPage;

// Export functions for global use
window.ForumPage = {
  loadForum,
  showAlert,
  markAsRead,
  loadPage,
};
