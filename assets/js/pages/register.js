// Register Page JavaScript - External File
// No PHP code - uses data attributes to get server data

document.addEventListener("DOMContentLoaded", () => {
  // Get DOM elements
  const registerForm = document.getElementById("registerForm");
  const usernameInput = document.getElementById("username");
  const emailInput = document.getElementById("email");
  const passwordInput = document.getElementById("password");
  const confirmPasswordInput = document.getElementById("confirm_password");
  const fullNameInput = document.getElementById("full_name");
  const schoolNameInput = document.getElementById("school_name");
  const gradeSelect = document.getElementById("grade");
  const submitBtn = document.getElementById("submitBtn");
  const btnText = document.getElementById("btnText");
  const errorAlert = document.getElementById("errorAlert");
  const successAlert = document.getElementById("successAlert");
  const errorMessage = document.getElementById("errorMessage");
  const successMessage = document.getElementById("successMessage");

  // Get PHP data from body data attributes
  const body = document.body;
  const phpError = body.getAttribute("data-error");
  const phpSuccess = body.getAttribute("data-success");

  // Show PHP alerts if data exists
  if (phpError && phpError.trim() !== "") {
    showError(phpError);
  }

  if (phpSuccess && phpSuccess.trim() !== "") {
    showSuccess(phpSuccess);
  }

  // Real-time validation
  passwordInput.addEventListener("input", validatePasswordStrength);
  confirmPasswordInput.addEventListener("input", validatePasswordMatch);
  emailInput.addEventListener("blur", validateEmailFormat);
  usernameInput.addEventListener("blur", validateUsername);

  // Form submission
  registerForm.addEventListener("submit", (e) => {
    clearErrors();
    hideAlerts();

    let hasError = false;

    // Validate required fields
    if (!usernameInput.value.trim()) {
      showFieldError(usernameInput, "Username harus diisi");
      hasError = true;
    } else if (usernameInput.value.length < 3) {
      showFieldError(usernameInput, "Username minimal 3 karakter");
      hasError = true;
    } else if (usernameInput.value.length > 20) {
      showFieldError(usernameInput, "Username maksimal 20 karakter");
      hasError = true;
    } else if (!/^[a-zA-Z0-9_]+$/.test(usernameInput.value)) {
      showFieldError(
        usernameInput,
        "Username hanya boleh huruf, angka, dan underscore"
      );
      hasError = true;
    }

    if (!emailInput.value.trim()) {
      showFieldError(emailInput, "Email harus diisi");
      hasError = true;
    } else if (!validateEmail(emailInput.value)) {
      showFieldError(emailInput, "Format email tidak valid");
      hasError = true;
    }

    if (!fullNameInput.value.trim()) {
      showFieldError(fullNameInput, "Nama lengkap harus diisi");
      hasError = true;
    } else if (fullNameInput.value.length < 2) {
      showFieldError(fullNameInput, "Nama minimal 2 karakter");
      hasError = true;
    } else if (fullNameInput.value.length > 50) {
      showFieldError(fullNameInput, "Nama maksimal 50 karakter");
      hasError = true;
    }

    if (!passwordInput.value) {
      showFieldError(passwordInput, "Password harus diisi");
      hasError = true;
    } else if (passwordInput.value.length < 6) {
      showFieldError(passwordInput, "Password minimal 6 karakter");
      hasError = true;
    } else if (passwordInput.value.length > 50) {
      showFieldError(passwordInput, "Password maksimal 50 karakter");
      hasError = true;
    }

    if (!confirmPasswordInput.value) {
      showFieldError(confirmPasswordInput, "Konfirmasi password harus diisi");
      hasError = true;
    } else if (passwordInput.value !== confirmPasswordInput.value) {
      showFieldError(confirmPasswordInput, "Password tidak sama");
      hasError = true;
    }

    // Optional field validation
    if (schoolNameInput.value && schoolNameInput.value.length > 100) {
      showFieldError(schoolNameInput, "Nama sekolah maksimal 100 karakter");
      hasError = true;
    }

    if (hasError) {
      e.preventDefault();
      // Scroll to first error
      const firstError = document.querySelector(".field-error");
      if (firstError) {
        firstError.scrollIntoView({ behavior: "smooth", block: "center" });
      }
      return false;
    }

    setLoading(true);
    // Form akan submit secara normal ke server
  });

  // Password strength validation
  function validatePasswordStrength() {
    const password = passwordInput.value;
    const strengthIndicator = document.getElementById("password-strength");

    if (!password) {
      strengthIndicator.textContent = "";
      return;
    }

    let strength = 0;
    let message = "";
    let color = "";

    // Check various criteria
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    // Determine strength level
    if (strength <= 2) {
      message = "Kekuatan: Lemah ⚠️";
      color = "#dc2626";
    } else if (strength <= 4) {
      message = "Kekuatan: Sedang 🔸";
      color = "#f59e0b";
    } else {
      message = "Kekuatan: Kuat ✅";
      color = "#16a34a";
    }

    strengthIndicator.textContent = message;
    strengthIndicator.style.color = color;
  }

  // Password match validation
  function validatePasswordMatch() {
    const matchIndicator = document.getElementById("password-match");

    if (!confirmPasswordInput.value) {
      matchIndicator.textContent = "";
      return;
    }

    if (passwordInput.value === confirmPasswordInput.value) {
      matchIndicator.textContent = "✅ Password cocok";
      matchIndicator.style.color = "#16a34a";
    } else {
      matchIndicator.textContent = "❌ Password tidak cocok";
      matchIndicator.style.color = "#dc2626";
    }
  }

  // Email format validation
  function validateEmailFormat() {
    const email = emailInput.value.trim();
    if (email && !validateEmail(email)) {
      showFieldError(emailInput, "Format email tidak valid");
    }
  }

  // Username validation
  function validateUsername() {
    const username = usernameInput.value.trim();
    if (username) {
      if (username.length < 3) {
        showFieldError(usernameInput, "Username minimal 3 karakter");
      } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        showFieldError(
          usernameInput,
          "Username hanya boleh huruf, angka, dan underscore"
        );
      }
    }
  }

  // Show field error
  function showFieldError(field, message) {
    // Remove existing error first
    const existingError = field.parentNode.querySelector(".field-error");
    if (existingError) {
      existingError.remove();
    }

    // Set field style
    field.style.borderColor = "#dc2626";
    field.style.boxShadow = "0 0 0 3px rgba(220, 38, 38, 0.1)";

    // Create error element
    const errorDiv = document.createElement("div");
    errorDiv.className = "field-error";
    errorDiv.innerHTML = `⚠️ ${message}`;

    // Add error to field parent
    field.parentNode.appendChild(errorDiv);
  }

  // Clear all errors
  function clearErrors() {
    const fieldErrors = document.querySelectorAll(".field-error");
    fieldErrors.forEach((error) => error.remove());

    const inputs = document.querySelectorAll("input, select");
    inputs.forEach((input) => {
      input.style.borderColor = "";
      input.style.boxShadow = "";
    });
  }

  // Show error alert
  function showError(message) {
    errorMessage.textContent = message;
    errorAlert.style.display = "block";

    // Auto hide after 5 seconds
    setTimeout(() => {
      hideAlerts();
    }, 5000);

    // Scroll to alert
    errorAlert.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  // Show success alert
  function showSuccess(message) {
    successMessage.textContent = message;
    successAlert.style.display = "block";

    // Auto hide after 3 seconds and redirect
    setTimeout(() => {
      window.location.href = "login.php";
    }, 3000);

    // Scroll to alert
    successAlert.scrollIntoView({ behavior: "smooth", block: "center" });
  }

  // Hide alerts
  function hideAlerts() {
    errorAlert.style.display = "none";
    successAlert.style.display = "none";
  }

  // Email validation regex
  function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email.toLowerCase());
  }

  // Set loading state
  function setLoading(isLoading) {
    if (isLoading) {
      submitBtn.disabled = true;
      btnText.innerHTML = '<span class="loading-spinner"></span>Mendaftar...';
    } else {
      submitBtn.disabled = false;
      btnText.textContent = "Daftar Sekarang";
    }
  }

  // Enter key navigation
  const inputs = [
    usernameInput,
    emailInput,
    fullNameInput,
    schoolNameInput,
    gradeSelect,
    passwordInput,
    confirmPasswordInput,
  ];

  inputs.forEach((input, index) => {
    if (input) {
      input.addEventListener("keypress", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          const nextInput = inputs[index + 1];
          if (nextInput) {
            nextInput.focus();
          } else {
            registerForm.dispatchEvent(new Event("submit"));
          }
        }
      });
    }
  });

  // Input formatting
  usernameInput.addEventListener("input", function () {
    // Convert to lowercase and remove invalid characters
    this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, "");
  });

  fullNameInput.addEventListener("input", function () {
    // Capitalize first letter of each word
    this.value = this.value.replace(/\b\w/g, (l) => l.toUpperCase());
  });

  // Form field animations
  const formInputs = document.querySelectorAll("input, select");
  formInputs.forEach((input) => {
    input.addEventListener("focus", function () {
      this.parentNode.style.transform = "scale(1.01)";
      this.parentNode.style.transition = "transform 0.2s ease";
    });

    input.addEventListener("blur", function () {
      this.parentNode.style.transform = "scale(1)";
    });
  });

  // Auto-hide alerts on new input
  formInputs.forEach((input) => {
    input.addEventListener("input", function () {
      if (errorAlert.style.display === "block") {
        setTimeout(hideAlerts, 3000);
      }
    });
  });

  console.log("Register form initialized successfully!");
});
