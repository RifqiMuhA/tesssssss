// Profile Page JavaScript

document.addEventListener("DOMContentLoaded", () => {
  const profileForm = document.querySelector(".profile-form")
  const passwordForm = document.querySelector(".password-form")
 
  // Handle profile form submission
  if (profileForm) {
    profileForm.addEventListener("submit", (e) => {
      const submitBtn = profileForm.querySelector('button[type="submit"]')
      setLoading(submitBtn, true)
    })
  }

  // Handle password form submission
  if (passwordForm) {
    passwordForm.addEventListener("submit", (e) => {
      const submitBtn = passwordForm.querySelector('button[type="submit"]')
      const newPassword = passwordForm.querySelector("#new_password").value
      const confirmPassword = passwordForm.querySelector("#confirm_password").value

      if (newPassword !== confirmPassword) {
        e.preventDefault()
        showAlert("Password baru dan konfirmasi tidak sama", "error")
        return false
      }

      if (newPassword.length < 6) {
        e.preventDefault()
        showAlert("Password baru minimal 6 karakter", "error")
        return false
      }

      setLoading(submitBtn, true)
    })

    // Real-time password confirmation
    const newPasswordInput = passwordForm.querySelector("#new_password")
    const confirmPasswordInput = passwordForm.querySelector("#confirm_password")

    if (newPasswordInput && confirmPasswordInput) {
      confirmPasswordInput.addEventListener("input", () => {
        validatePasswordMatch()
      })

      newPasswordInput.addEventListener("input", () => {
        if (confirmPasswordInput.value) {
          validatePasswordMatch()
        }
      })
    }
  }

  // Initialize animations for stats
  initializeStatsAnimation()

  // Initialize session card interactions
  initializeSessionCards()
})

function validatePasswordMatch() {
  const newPassword = document.querySelector("#new_password").value
  const confirmPassword = document.querySelector("#confirm_password").value
  const confirmInput = document.querySelector("#confirm_password")

  // Remove existing indicator
  const existingIndicator = document.querySelector("#password-match-indicator")
  if (existingIndicator) {
    existingIndicator.remove()
  }

  if (confirmPassword) {
    const indicator = document.createElement("div")
    indicator.id = "password-match-indicator"
    indicator.style.fontSize = "0.75rem"
    indicator.style.marginTop = "0.25rem"

    if (newPassword === confirmPassword) {
      indicator.textContent = "Password cocok ✓"
      indicator.style.color = "var(--success-color)"
      confirmInput.style.borderColor = "var(--success-color)"
    } else {
      indicator.textContent = "Password tidak cocok"
      indicator.style.color = "var(--error-color)"
      confirmInput.style.borderColor = "var(--error-color)"
    }

    confirmInput.parentNode.appendChild(indicator)
  } else {
    confirmInput.style.borderColor = ""
  }
}

function initializeStatsAnimation() {
  const statNumbers = document.querySelectorAll(".stat-number")

  // Use Intersection Observer to trigger animation when visible
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateStatNumber(entry.target)
          observer.unobserve(entry.target)
        }
      })
    },
    { threshold: 0.5 },
  )

  statNumbers.forEach((stat) => {
    observer.observe(stat)
  })
}

function animateStatNumber(element) {
  const finalValue = Number.parseInt(element.textContent.replace(/[,%]/g, ""))
  if (isNaN(finalValue)) return

  let currentValue = 0
  const increment = finalValue / 30
  const isPercentage = element.textContent.includes("%")

  const timer = setInterval(() => {
    currentValue += increment
    if (currentValue >= finalValue) {
      currentValue = finalValue
      clearInterval(timer)
    }

    const displayValue = Math.floor(currentValue)
    element.textContent = isPercentage ? `${displayValue}%` : displayValue.toLocaleString()
  }, 50)
}

function initializeSessionCards() {
  const sessionCards = document.querySelectorAll(".session-card")

  sessionCards.forEach((card) => {
    // Add click handler for better UX
    card.addEventListener("click", (e) => {
      const actionButton = card.querySelector(".session-actions .btn")
      if (actionButton && !e.target.closest(".session-actions")) {
        actionButton.click()
      }
    })

    // Add hover effect
    card.addEventListener("mouseenter", () => {
      card.style.transform = "translateY(-2px)"
    })

    card.addEventListener("mouseleave", () => {
      card.style.transform = "translateY(0)"
    })
  })
}

function setLoading(button, isLoading) {
  if (isLoading) {
    button.disabled = true
    button.dataset.originalText = button.textContent
    button.textContent = "Loading..."
  } else {
    button.disabled = false
    button.textContent = button.dataset.originalText || "Submit"
  }
}

function showAlert(message, type = "info") {
  const alertDiv = document.createElement("div")
  alertDiv.className = `alert alert-${type}`
  alertDiv.textContent = message

  const container = document.querySelector(".container")
  container.insertBefore(alertDiv, container.firstChild)

  setTimeout(() => {
    alertDiv.remove()
  }, 5000)
}
