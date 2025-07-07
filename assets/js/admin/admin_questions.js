// Admin Questions JavaScript with AJAX

document.addEventListener("DOMContentLoaded", function () {
  initializeQuestionsPage();
});

let currentPage = 1;
let searchTimeout;
let isLoading = false;

function initializeQuestionsPage() {
  const showAddForm =
    new URLSearchParams(window.location.search).get("action") === "add";

  if (showAddForm) {
    setupFormHandlers();
    setupFormValidation();
  } else {
    setupAjaxFilters();
    setupConfirmations();
    loadQuestions(); // Initial load
  }

  setupQuestionInteractions();
}

function setupAjaxFilters() {
  const searchInput = document.getElementById("searchInput");
  const categorySelect = document.getElementById("categoryFilter");
  const statusSelect = document.getElementById("statusFilter");

  // Debounced search
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadQuestions();
      }, 500);
    });
  }

  // Filter changes
  [categorySelect, statusSelect].forEach((select) => {
    if (select) {
      select.addEventListener("change", function () {
        currentPage = 1;
        loadQuestions();
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
      loadQuestions();
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

function loadQuestions(page = currentPage) {
  if (isLoading) return;

  isLoading = true;
  currentPage = page;

  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");
  const statusFilter = document.getElementById("statusFilter");
  const loadingIndicator = document.getElementById("loadingIndicator");
  const questionsContainer = document.getElementById("questionsListContainer");
  const paginationContainer = document.getElementById("paginationContainer");

  // Show loading
  loadingIndicator.style.display = "block";
  questionsContainer.style.opacity = "0.5";

  const requestData = {
    action: "search_questions",
    search: searchInput ? searchInput.value : "",
    category: categoryFilter ? categoryFilter.value : "",
    status: statusFilter ? statusFilter.value : "",
    page: page,
  };

  fetch("../ajax/admin_questions_ajax.php", {
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
        questionsContainer.innerHTML = data.html;
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
      questionsContainer.style.opacity = "1";
    });
}

function loadPage(page) {
  loadQuestions(page);
}

function setupActionConfirmations() {
  // Handle AJAX actions for dynamically loaded content
  document.addEventListener("click", function (e) {
    const button = e.target.closest("button.ajax-action");
    if (!button) return;

    const action = button.getAttribute("data-action");
    const questionId = button.getAttribute("data-question-id");

    let confirmMessage = "";
    switch (action) {
      case "toggle_status":
        confirmMessage = "Yakin ingin mengubah status soal ini?";
        break;
      default:
        return;
    }

    e.preventDefault();

    if (confirm(confirmMessage)) {
      performAjaxAction(action, questionId, button);
    }
  });
}

function performAjaxAction(action, questionId, button) {
  // Disable button during request
  const originalContent = button.innerHTML;
  button.disabled = true;
  button.innerHTML = '<div class="mini-spinner"></div>';

  const requestData = {
    action: action,
    question_id: parseInt(questionId),
  };

  fetch("../ajax/admin_questions_ajax.php", {
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
        // Reload questions to reflect changes
        loadQuestions(currentPage);
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

function setupFormHandlers() {
  const addForm = document.querySelector('form[action*="add_question"]');
  if (addForm) {
    setupAddQuestionForm(addForm);
  }

  // Auto-resize textareas
  const textareas = document.querySelectorAll("textarea");
  textareas.forEach((textarea) => {
    autoResizeTextarea(textarea);
  });
}

function openImageModal(imageSrc) {
  const modal = document.getElementById("imageModal");
  const modalImage = document.getElementById("modalImage");

  if (modal && modalImage) {
    modalImage.src = imageSrc;
    modal.classList.add("active");

    // Prevent body scroll
    document.body.style.overflow = "hidden";

    // Add keyboard listener for ESC key
    document.addEventListener("keydown", handleModalKeydown);
  }
}

function closeImageModal() {
  const modal = document.getElementById("imageModal");

  if (modal) {
    modal.classList.remove("active");

    // Restore body scroll
    document.body.style.overflow = "";

    // Remove keyboard listener
    document.removeEventListener("keydown", handleModalKeydown);
  }
}

function handleModalKeydown(e) {
  if (e.key === "Escape") {
    closeImageModal();
  }
}

function setupAddQuestionForm(form) {
  const correctAnswerSelect = document.getElementById("correct_answer");
  const optionInputs = document.querySelectorAll('input[name^="option_"]');

  // Update correct answer options when option inputs change
  optionInputs.forEach((input, index) => {
    input.addEventListener("input", function () {
      updateCorrectAnswerOptions();
    });
  });

  // Form submission validation
  form.addEventListener("submit", function (e) {
    if (!validateQuestionForm()) {
      e.preventDefault();
    }
  });

  // Real-time validation feedback
  const requiredFields = form.querySelectorAll("[required]");
  requiredFields.forEach((field) => {
    field.addEventListener("blur", function () {
      validateField(this);
    });

    field.addEventListener("input", function () {
      clearFieldError(this);
    });
  });
}

function updateCorrectAnswerOptions() {
  const correctAnswerSelect = document.getElementById("correct_answer");
  if (!correctAnswerSelect) return;

  const currentValue = correctAnswerSelect.value;
  const options = ["A", "B", "C", "D", "E"];

  // Clear existing options except the first one
  correctAnswerSelect.innerHTML =
    '<option value="">Select Correct Answer</option>';

  options.forEach((option) => {
    const input = document.querySelector(
      `input[name="option_${option.toLowerCase()}"]`
    );
    if (input && input.value.trim()) {
      const optionElement = document.createElement("option");
      optionElement.value = option;
      optionElement.textContent = `${option} - ${input.value.substring(0, 30)}${
        input.value.length > 30 ? "..." : ""
      }`;
      correctAnswerSelect.appendChild(optionElement);
    }
  });

  // Restore previous value if still valid
  if (
    currentValue &&
    correctAnswerSelect.querySelector(`option[value="${currentValue}"]`)
  ) {
    correctAnswerSelect.value = currentValue;
  }
}

function validateQuestionForm() {
  let isValid = true;
  const errors = [];

  // Validate required fields
  const questionText = document.getElementById("question_text");
  const topicId = document.getElementById("topic_id");
  const correctAnswer = document.getElementById("correct_answer");

  if (!questionText || !questionText.value.trim()) {
    isValid = false;
    errors.push("Question text is required");
    if (questionText) markFieldError(questionText);
  }

  if (!topicId || !topicId.value) {
    isValid = false;
    errors.push("Topic selection is required");
    if (topicId) markFieldError(topicId);
  }

  if (!correctAnswer || !correctAnswer.value) {
    isValid = false;
    errors.push("Correct answer must be selected");
    if (correctAnswer) markFieldError(correctAnswer);
  }

  // Validate all options are filled
  const options = ["a", "b", "c", "d", "e"];
  options.forEach((option) => {
    const input = document.getElementById(`option_${option}`);
    if (!input || !input.value.trim()) {
      isValid = false;
      errors.push(`Option ${option.toUpperCase()} is required`);
      if (input) markFieldError(input);
    }
  });

  // Validate points
  const points = document.getElementById("points");
  if (points && (parseInt(points.value) < 1 || parseInt(points.value) > 100)) {
    isValid = false;
    errors.push("Points must be between 1 and 100");
    markFieldError(points);
  }

  if (!isValid) {
    showAlert(errors.join("\n"), "error");
  }

  return isValid;
}

function validateField(field) {
  if (field.required && !field.value.trim()) {
    markFieldError(field);
    return false;
  }

  clearFieldError(field);
  return true;
}

function markFieldError(field) {
  field.style.borderColor = "#dc2626";
  field.style.boxShadow = "0 0 0 3px rgba(220, 38, 38, 0.1)";
}

function clearFieldError(field) {
  field.style.borderColor = "#d1d5db";
  field.style.boxShadow = "";
}

function setupQuestionInteractions() {
  // Image modal interactions
  const imageModal = document.getElementById("imageModal");
  if (imageModal) {
    // Close modal when clicking outside
    imageModal.addEventListener("click", function (e) {
      if (e.target === this) {
        closeImageModal();
      }
    });
  }

  // Keyboard shortcuts
  document.addEventListener("keydown", function (e) {
    if (e.ctrlKey || e.metaKey) {
      switch (e.key) {
        case "f":
          e.preventDefault();
          const searchInput = document.getElementById("searchInput");
          if (searchInput) {
            searchInput.focus();
            searchInput.select();
          }
          break;
        case "n":
          e.preventDefault();
          const addButton = document.querySelector('a[href*="action=add"]');
          if (addButton) {
            window.location.href = addButton.href;
          }
          break;
      }
    }
  });
}

function setupFormValidation() {
  // This is already handled in setupAddQuestionForm
}

function setupConfirmations() {
  setupActionConfirmations();
}

function autoResizeTextarea(textarea) {
  function resize() {
    textarea.style.height = "auto";
    textarea.style.height = textarea.scrollHeight + "px";
  }

  textarea.addEventListener("input", resize);
  textarea.addEventListener("change", resize);

  // Initial resize
  resize();
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
      white-space: pre-line;
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

  // Auto remove after 7 seconds for errors, 5 for others
  const duration = type === "error" ? 7000 : 5000;
  setTimeout(() => {
    alert.style.opacity = "0";
    alert.style.transform = "translateX(100%)";
    setTimeout(() => {
      alert.remove();
    }, 300);
  }, duration);

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
window.openImageModal = openImageModal;
window.closeImageModal = closeImageModal;
window.loadPage = loadPage;

// Export functions for global use
window.QuestionsPage = {
  validateQuestionForm,
  showAlert,
  updateCorrectAnswerOptions,
  openImageModal,
  closeImageModal,
  loadQuestions,
  loadPage,
};
