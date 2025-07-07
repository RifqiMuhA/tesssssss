// Admin Dashboard JavaScript

document.addEventListener("DOMContentLoaded", () => {
  // Initialize dashboard animations
  initializeAnimations()

  // Initialize real-time updates
  initializeRealTimeUpdates()

  // Initialize chart if needed
  initializeCharts()
})

function initializeAnimations() {
  // Animate stat numbers
  const statNumbers = document.querySelectorAll(".stat-number")

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

  // Animate category bars
  const categoryFills = document.querySelectorAll(".category-fill")
  categoryFills.forEach((fill, index) => {
    setTimeout(
      () => {
        const width = fill.style.width
        fill.style.width = "0%"
        fill.style.transition = "width 1s ease-in-out"
        setTimeout(() => {
          fill.style.width = width
        }, 100)
      },
      500 + index * 200,
    )
  })
}

function animateStatNumber(element) {
  const finalValue = Number.parseInt(element.textContent.replace(/,/g, ""))
  if (isNaN(finalValue)) return

  let currentValue = 0
  const increment = finalValue / 50
  const timer = setInterval(() => {
    currentValue += increment
    if (currentValue >= finalValue) {
      currentValue = finalValue
      clearInterval(timer)
    }
    element.textContent = Math.floor(currentValue).toLocaleString()
  }, 30)
}

function initializeRealTimeUpdates() {
  // Update timestamps every minute
  setInterval(updateTimestamps, 60000)

  // Refresh stats every 5 minutes
  setInterval(refreshStats, 300000)
}

function updateTimestamps() {
  const timeElements = document.querySelectorAll(".activity-time")
  timeElements.forEach((element) => {
    // Update relative time display if needed
    const timestamp = element.dataset.timestamp
    if (timestamp) {
      element.textContent = formatRelativeTime(new Date(timestamp))
    }
  })
}

function refreshStats() {
  // Fetch updated statistics
  fetch("api/dashboard-stats.php")
    .then((response) => response.json())
    .then((data) => {
      updateStatCards(data)
    })
    .catch((error) => {
      console.error("Error refreshing stats:", error)
    })
}

function updateStatCards(data) {
  // Update stat numbers with new data
  Object.keys(data).forEach((key) => {
    const element = document.querySelector(`[data-stat="${key}"]`)
    if (element) {
      animateStatNumber(element)
    }
  })
}

function initializeCharts() {
  // Initialize any charts or graphs
  const chartContainer = document.querySelector("#dashboard-chart")
  if (chartContainer) {
    // Chart implementation would go here
    console.log("Chart container found, but chart library not loaded")
  }
}

function formatRelativeTime(date) {
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (days > 0) return `${days}d ago`
  if (hours > 0) return `${hours}h ago`
  if (minutes > 0) return `${minutes}m ago`
  return "Just now"
}

// Add click handlers for quick actions
document.addEventListener("click", (e) => {
  if (e.target.closest(".action-btn")) {
    const btn = e.target.closest(".action-btn")
    btn.style.transform = "scale(0.95)"
    setTimeout(() => {
      btn.style.transform = "scale(1)"
    }, 150)
  }
})
