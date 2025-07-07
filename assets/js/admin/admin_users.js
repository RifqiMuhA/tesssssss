// Admin Users JavaScript with AJAX

document.addEventListener("DOMContentLoaded", function () {
  initializeUsersPage();
});

let currentPage = 1;
let searchTimeout;
let isLoading = false;

function initializeUsersPage() {
  setupModalHandlers();
  setupAjaxFilters();
  setupConfirmations();
  loadUsers(); // Initial load
}

function setupAjaxFilters() {
  const searchInput = document.getElementById("searchInput");
  const statusSelect = document.getElementById("statusFilter");
  const gradeSelect = document.getElementById("gradeFilter");

  // Debounced search
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadUsers();
      }, 500);
    });
  }

  // Filter changes
  [statusSelect, gradeSelect].forEach((select) => {
    if (select) {
      select.addEventListener("change", function () {
        currentPage = 1;
        loadUsers();
      });
    }
  });

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
      loadUsers();
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

function loadUsers(page = currentPage) {
  if (isLoading) return;

  isLoading = true;
  currentPage = page;

  const searchInput = document.getElementById("searchInput");
  const statusFilter = document.getElementById("statusFilter");
  const gradeFilter = document.getElementById("gradeFilter");
  const loadingIndicator = document.getElementById("loadingIndicator");
  const usersContainer = document.getElementById("usersTableContainer");
  const paginationContainer = document.getElementById("paginationContainer");

  // Show loading
  loadingIndicator.style.display = "block";
  usersContainer.style.opacity = "0.5";

  const requestData = {
    action: "search_users",
    search: searchInput ? searchInput.value : "",
    status: statusFilter ? statusFilter.value : "",
    grade: gradeFilter ? gradeFilter.value : "",
    page: page,
  };

  fetch("../ajax/admin_users_ajax.php", {
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
        usersContainer.innerHTML = data.html;
        paginationContainer.innerHTML = data.pagination;

        // Re-setup action confirmations for new content
        setupActionConfirmations();

        // Update URL without reload
        const url = new URL(window.location);
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
      usersContainer.style.opacity = "1";
    });
}

function loadPage(page) {
  loadUsers(page);
}

function setupActionConfirmations() {
  // Handle AJAX actions for dynamically loaded content
  document.addEventListener("click", function (e) {
    const button = e.target.closest("button.ajax-action");
    if (!button) return;

    const action = button.getAttribute("data-action");
    const userId = button.getAttribute("data-user-id");

    let confirmMessage = "";
    switch (action) {
      case "toggle_status":
        confirmMessage = "Yakin ingin mengubah status user ini?";
        break;
      case "reset_password":
        confirmMessage =
          'Yakin ingin mereset password user ini ke "password123"?';
        break;
      default:
        return;
    }

    e.preventDefault();

    if (confirm(confirmMessage)) {
      performAjaxAction(action, userId, button);
    }
  });
}

function performAjaxAction(action, userId, button) {
  // Disable button during request
  const originalContent = button.innerHTML;
  button.disabled = true;
  button.innerHTML = '<div class="mini-spinner"></div>';

  const requestData = {
    action: action,
    user_id: parseInt(userId),
  };

  fetch("../ajax/admin_users_ajax.php", {
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
        // Reload users to reflect changes
        loadUsers(currentPage);
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

function setupModalHandlers() {
  const editModal = document.getElementById("editModal");
  const editForm = document.getElementById("editForm");
  const modalOverlay = document.querySelector(".modal-overlay");

  // Close modal handlers
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("modal-close")) {
      closeEditModal();
    }

    if (e.target === modalOverlay) {
      closeEditModal();
    }
  });

  // Escape key to close modal
  document.addEventListener("keydown", function (e) {
    if (
      e.key === "Escape" &&
      editModal &&
      editModal.classList.contains("active")
    ) {
      closeEditModal();
    }
  });

  // Form validation and submission
  if (editForm) {
    editForm.addEventListener("submit", function (e) {
      e.preventDefault();

      if (!validateEditForm()) {
        return;
      }

      // Submit form via regular POST (not AJAX for edit)
      const formData = new FormData(this);

      fetch(window.location.href, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.text())
        .then(() => {
          closeEditModal();
          loadUsers(currentPage);
          showAlert("Data user berhasil diperbarui", "success");
        })
        .catch((error) => {
          console.error("Error:", error);
          showAlert("Terjadi kesalahan saat menyimpan data", "error");
        });
    });
  }
}

function openEditModal(user) {
  const modal = document.getElementById("editModal");
  if (!modal) return;

  // Populate form fields
  document.getElementById("editUserId").value = user.id;
  document.getElementById("editFullName").value = user.full_name;
  document.getElementById("editUsername").value = user.username;
  document.getElementById("editEmail").value = user.email;
  document.getElementById("editSchool").value = user.school_name || "";
  document.getElementById("editGrade").value = user.grade || "";

  // Show modal
  modal.classList.add("active");

  // Focus first input
  setTimeout(() => {
    document.getElementById("editFullName").focus();
  }, 100);
}

function closeEditModal() {
  const modal = document.getElementById("editModal");
  if (modal) {
    modal.classList.remove("active");
  }
}

function validateEditForm() {
  const fullName = document.getElementById("editFullName").value.trim();
  const username = document.getElementById("editUsername").value.trim();
  const email = document.getElementById("editEmail").value.trim();

  if (!fullName || !username || !email) {
    showAlert("Nama lengkap, username, dan email harus diisi", "error");
    return false;
  }

  // Email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showAlert("Format email tidak valid", "error");
    return false;
  }

  // Username validation (no spaces, special chars)
  const usernameRegex = /^[a-zA-Z0-9_]+$/;
  if (!usernameRegex.test(username)) {
    showAlert(
      "Username hanya boleh mengandung huruf, angka, dan underscore",
      "error"
    );
    return false;
  }

  return true;
}

function setupConfirmations() {
  setupActionConfirmations();
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

// Export functions for global use
window.openEditModal = openEditModal;
window.closeEditModal = closeEditModal;
window.loadPage = loadPage;
