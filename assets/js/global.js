// Global JavaScript Functions for DrillPTN Platform

// Utility Functions
function $(selector) {
  return document.querySelector(selector)
}

function $$(selector) {
  return document.querySelectorAll(selector)
}

// Form Validation Helpers
function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return re.test(email)
}

function validatePassword(password) {
  return password.length >= 6
}

function showAlert(message, type = "info") {
  const alertDiv = document.createElement("div")
  alertDiv.className = `alert alert-${type}`
  alertDiv.textContent = message

  const container = $(".container") || document.body
  container.insertBefore(alertDiv, container.firstChild)

  setTimeout(() => {
    alertDiv.remove()
  }, 5000)
}

// Loading State
function setLoading(element, loading = true) {
  if (loading) {
    element.disabled = true
    element.textContent = "Loading..."
  } else {
    element.disabled = false
    element.textContent = element.dataset.originalText || "Submit"
  }
}

// Mobile Menu Toggle
function initMobileMenu() {
  const navToggle = $(".nav-toggle")
  const navMenu = $(".nav-menu")

  if (navToggle && navMenu) {
    navToggle.addEventListener("click", () => {
      navMenu.classList.toggle("active")
    })
  }
}

// Initialize on DOM load
document.addEventListener("DOMContentLoaded", () => {
  initMobileMenu()

  // Store original button text for loading states
  $$('button[type="submit"]').forEach((btn) => {
    btn.dataset.originalText = btn.textContent
  })
})

// Confirmation Dialog
function confirmAction(message) {
  return confirm(message)
}

// Format Numbers
function formatNumber(num) {
  return new Intl.NumberFormat("id-ID").format(num)
}

// Time Formatting
function formatTime(seconds) {
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60

  if (hours > 0) {
    return `${hours}:${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`
  }
  return `${minutes}:${secs.toString().padStart(2, "0")}`
}
