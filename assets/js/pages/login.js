document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("loginForm");
  const usernameInput = document.getElementById("username");
  const passwordInput = document.getElementById("password");
  const submitBtn = document.getElementById("submitBtn");
  const btnText = document.getElementById("btnText");
  const errorAlert = document.getElementById("errorAlert");
  const errorMessage = document.getElementById("errorMessage");

  // Form validation and submission
  loginForm.addEventListener("submit", (e) => {
      const username = usernameInput.value.trim();
      const password = passwordInput.value;

      // Clear previous errors
      clearErrors();
      hideError();

      let hasError = false;

      if (!username) {
          showFieldError(usernameInput, "Username harus diisi");
          hasError = true;
      }

      if (!password) {
          showFieldError(passwordInput, "Password harus diisi");
          hasError = true;
      }

      if (hasError) {
          e.preventDefault();
          return false;
      }

      // Show loading state
      setLoading(true);
      
      // Form will submit normally - remove preventDefault for actual submission
      // e.preventDefault(); // Remove this line for real form submission
  });

  function showFieldError(field, message) {
      field.style.borderColor = "#dc2626";
      field.style.boxShadow = "0 0 0 3px rgba(220, 38, 38, 0.1)";

      const errorDiv = document.createElement("div");
      errorDiv.className = "field-error";
      errorDiv.innerHTML = `⚠️ ${message}`;

      field.parentNode.appendChild(errorDiv);
  }

  function clearErrors() {
      const fieldErrors = document.querySelectorAll(".field-error");
      fieldErrors.forEach((error) => error.remove());

      const inputs = document.querySelectorAll("input");
      inputs.forEach((input) => {
          input.style.borderColor = "";
          input.style.boxShadow = "";
      });
  }

  function showError(message) {
      errorMessage.textContent = message;
      errorAlert.style.display = "block";
  }

  function hideError() {
      errorAlert.style.display = "none";
  }

  function setLoading(isLoading) {
      if (isLoading) {
          submitBtn.disabled = true;
          btnText.innerHTML = '<span class="loading-spinner"></span>Memuat...';
      } else {
          submitBtn.disabled = false;
          btnText.textContent = "Masuk";
      }
  }

  // Enter key navigation
  usernameInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
          passwordInput.focus();
      }
  });

  // Show error from PHP if exists
  <?php if (!empty($error)): ?>
      showError("<?php echo addslashes($error); ?>");
  <?php endif; ?>
});