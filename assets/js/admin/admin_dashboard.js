// Admin Dashboard JavaScript

document.addEventListener("DOMContentLoaded", function () {
  initializeDashboard();
});

function initializeDashboard() {
  animateStatNumbers();
  animateCategoryBars();
  initializeRealTimeUpdates();
  setupQuickActions();
}

function animateStatNumbers() {
  const statNumbers = document.querySelectorAll(".stat-number");

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateNumber(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 }
  );

  statNumbers.forEach((stat) => {
    observer.observe(stat);
  });
}

function animateNumber(element) {
  const text = element.textContent;
  const finalValue = parseInt(text.replace(/,/g, ""));

  if (isNaN(finalValue)) return;

  let currentValue = 0;
  const increment = finalValue / 50;
  const duration = 1500;
  const stepTime = duration / 50;

  const timer = setInterval(() => {
    currentValue += increment;
    if (currentValue >= finalValue) {
      currentValue = finalValue;
      clearInterval(timer);
    }
    element.textContent = Math.floor(currentValue).toLocaleString();
  }, stepTime);
}

function animateCategoryBars() {
  const categoryBars = document.querySelectorAll(".category-fill");

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            const fill = entry.target;
            const targetWidth = fill.style.width;
            fill.style.width = "0%";

            // Force reflow
            fill.offsetHeight;

            fill.style.width = targetWidth;
          }, index * 200);

          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.5 }
  );

  categoryBars.forEach((bar) => {
    observer.observe(bar);
  });
}

function initializeRealTimeUpdates() {
  // Update timestamps every minute
  setInterval(updateTimestamps, 60000);

  // Refresh dashboard data every 5 minutes
  setInterval(refreshDashboardData, 300000);

  // Initial timestamp update
  updateTimestamps();
}

function updateTimestamps() {
  const timeElements = document.querySelectorAll(".activity-time");

  timeElements.forEach((element) => {
    const timestamp = element.getAttribute("data-timestamp");
    if (timestamp) {
      element.textContent = formatRelativeTime(new Date(timestamp));
    }
  });
}

function formatRelativeTime(date) {
  const now = new Date();
  const diff = now - date;
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(minutes / 60);
  const days = Math.floor(hours / 24);

  if (days > 0) return `${days}d ago`;
  if (hours > 0) return `${hours}h ago`;
  if (minutes > 0) return `${minutes}m ago`;
  return "Just now";
}

function refreshDashboardData() {
  // Check if refresh endpoint exists
  if (typeof DASHBOARD_REFRESH_URL !== "undefined") {
    fetch(DASHBOARD_REFRESH_URL)
      .then((response) => response.json())
      .then((data) => {
        updateDashboardData(data);
      })
      .catch((error) => {
        console.log("Dashboard refresh not available:", error);
      });
  }
}

function updateDashboardData(data) {
  // Update stat numbers
  if (data.stats) {
    Object.keys(data.stats).forEach((key) => {
      const element = document.querySelector(`[data-stat="${key}"]`);
      if (element) {
        const newValue = data.stats[key];
        element.textContent = newValue.toLocaleString();
      }
    });
  }

  // Update recent activities
  if (data.activities) {
    updateActivitiesList(data.activities);
  }

  // Update top performers
  if (data.performers) {
    updatePerformersList(data.performers);
  }
}

function updateActivitiesList(activities) {
  const container = document.querySelector(".activity-list");
  if (!container) return;

  container.innerHTML = "";

  activities.forEach((activity) => {
    const item = createActivityItem(activity);
    container.appendChild(item);
  });
}

function createActivityItem(activity) {
  const item = document.createElement("div");
  item.className = "activity-item";

  const iconClass = getActivityIconClass(activity.type);

  item.innerHTML = `
        <div class="activity-icon ${activity.type}">
            ${getActivityIcon(activity.type)}
        </div>
        <div class="activity-content">
            <div class="activity-title">${escapeHtml(activity.title)}</div>
            <div class="activity-time" data-timestamp="${activity.created_at}">
                ${formatRelativeTime(new Date(activity.created_at))}
            </div>
        </div>
    `;

  return item;
}

function getActivityIcon(type) {
  const icons = {
    user_registration: `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
        `,
    thread_created: `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
        `,
    session_completed: `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20,6 9,17 4,12"/>
            </svg>
        `,
  };

  return icons[type] || icons["session_completed"];
}

function updatePerformersList(performers) {
  const container = document.querySelector(".performers-list");
  if (!container) return;

  container.innerHTML = "";

  performers.forEach((performer, index) => {
    const item = createPerformerItem(performer, index + 1);
    container.appendChild(item);
  });
}

function createPerformerItem(performer, rank) {
  const item = document.createElement("div");
  item.className = "performer-item";

  item.innerHTML = `
        <div class="performer-rank">#${rank}</div>
        <div class="performer-info">
            <div class="performer-name">${escapeHtml(performer.full_name)}</div>
            <div class="performer-stats">
                ${performer.points.toLocaleString()} poin • ${
    performer.accuracy
  }% akurasi
            </div>
        </div>
    `;

  return item;
}

function setupQuickActions() {
  const actionButtons = document.querySelectorAll(".action-btn");

  actionButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      // Add click animation
      this.style.transform = "scale(0.95)";
      setTimeout(() => {
        this.style.transform = "scale(1)";
      }, 150);
    });
  });
}

// Method untuk escape HTML characters
// Mencegah XSS dengan meng-escape karakter khusus
// referensi: https://stackoverflow.com/a/6234804
function escapeHtml(unsafe) {
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// Utility function to show loading state
function showLoadingState(container) {
  if (!container) return;

  container.innerHTML = `
        <div class="loading-skeleton">
            <div class="loading-skeleton-text"></div>
            <div class="loading-skeleton-text"></div>
            <div class="loading-skeleton-text"></div>
        </div>
    `;
}

// Export for use in other scripts
window.Dashboard = {
  refreshDashboardData,
  updateTimestamps,
  showLoadingState,
};
