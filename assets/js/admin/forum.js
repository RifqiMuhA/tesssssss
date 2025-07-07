document.addEventListener("DOMContentLoaded", () => {
  // Initialize forum management features
  initializeForumActions()
  initializeFilters()
  initializeViewToggle()
})

function initializeForumActions() { 
  // Add confirmation for destructive actions
  const deleteButtons = document.querySelectorAll(".btn-action.delete")

  deleteButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      e.preventDefault()
      const form = button.closest("form")
      const isThread = form.querySelector('input[name="thread_id"]')
      const itemType = isThread ? "thread" : "post"

      if (confirm(`Are you sure you want to delete this ${itemType}? This action cannot be undone.`)) {
        form.submit()
      }
    })
  })

  // Add loading states for action buttons
  const actionForms = document.querySelectorAll('form[method="POST"]')
  actionForms.forEach((form) => {
    form.addEventListener("submit", () => {
      const button = form.querySelector('button[type="submit"]')
      if (button) {
        button.disabled = true
        const originalContent = button.innerHTML
        button.innerHTML =
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>'

        // Restore button after 3 seconds if form doesn't redirect
        setTimeout(() => {
          button.disabled = false
          button.innerHTML = originalContent
        }, 3000)
      }
    })
  })

  // Add hover effects for forum items
  const forumItems = document.querySelectorAll(".forum-item")
  forumItems.forEach((item) => {
    item.addEventListener("mouseenter", () => {
      item.style.transform = "translateY(-2px)"
      item.style.boxShadow = "var(--shadow-lg)"
    })

    item.addEventListener("mouseleave", () => {
      item.style.transform = "translateY(0)"
      item.style.boxShadow = "var(--shadow-md)"
    })
  })
}

function initializeFilters() {
  const filterForm = document.querySelector(".filter-form")
  const searchInput = document.querySelector(".search-input")
  const categorySelect = document.querySelector('select[name="category"]')

  // Auto-submit on category change
  if (categorySelect) {
    categorySelect.addEventListener("change", () => {
      filterForm.submit()
    })
  }

  // Search with debounce
  let searchTimeout
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        filterForm.submit()
      }, 500)
    })
  }

  // Add filter summary
  addFilterSummary()
}

function addFilterSummary() {
  const searchInput = document.querySelector(".search-input")
  const categorySelect = document.querySelector('select[name="category"]')
  const view = new URLSearchParams(window.location.search).get("view") || "threads"

  const activeFilters = []

  if (searchInput && searchInput.value) {
    activeFilters.push(`Search: "${searchInput.value}"`)
  }

  if (categorySelect && categorySelect.value) {
    const categoryName = categorySelect.options[categorySelect.selectedIndex].textContent
    activeFilters.push(`Category: ${categoryName}`)
  }

  if (activeFilters.length > 0) {
    const summary = document.createElement("div")
    summary.className = "filter-summary"
    summary.innerHTML = `
      <div class="filter-info">
        <span class="filter-count">${activeFilters.length} filter(s) active</span>
        <div class="filter-list">
          ${activeFilters.map((filter) => `<span class="filter-item">${filter}</span>`).join("")}
        </div>
      </div>
      <button type="button" class="clear-all-filters" onclick="clearForumFilters()">
        Clear All
      </button>
    `

    const filtersContainer = document.querySelector(".admin-filters")
    filtersContainer.appendChild(summary)
  }
}

function clearForumFilters() {
  const searchInput = document.querySelector(".search-input")
  const categorySelect = document.querySelector('select[name="category"]')
  const view = new URLSearchParams(window.location.search).get("view") || "threads"

  if (searchInput) searchInput.value = ""
  if (categorySelect) categorySelect.value = ""

  // Redirect with only the view parameter
  window.location.href = `?view=${view}`
}

function initializeViewToggle() {
  const toggleButtons = document.querySelectorAll(".toggle-btn")

  toggleButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      // Add loading state
      button.style.opacity = "0.7"
      button.style.pointerEvents = "none"

      // The actual navigation will happen via the href
      // This just provides visual feedback
    })
  })

  // Add keyboard navigation for toggle
  document.addEventListener("keydown", (e) => {
    if (e.key === "Tab" && e.shiftKey) {
      // Handle shift+tab navigation
    } else if (e.key === "Tab") {
      // Handle tab navigation
    }
  })
}

// Utility functions for forum management
function toggleThreadPin(threadId) {
  const form = document.createElement("form")
  form.method = "POST"
  form.innerHTML = `
    <input type="hidden" name="action" value="pin_thread">
    <input type="hidden" name="thread_id" value="${threadId}">
  `
  document.body.appendChild(form)
  form.submit()
}

function toggleThreadLock(threadId) {
  const form = document.createElement("form")
  form.method = "POST"
  form.innerHTML = `
    <input type="hidden" name="action" value="lock_thread">
    <input type="hidden" name="thread_id" value="${threadId}">
  `
  document.body.appendChild(form)
  form.submit()
}

function viewThread(threadId) {
  window.open(`../thread.php?id=${threadId}`, "_blank")
}

// Add CSS for forum management enhancements
const style = document.createElement("style")
style.textContent = `
  .filter-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1rem;
    padding: 1rem;
    background: var(--background-color);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
  }

  .filter-info {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .filter-count {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-primary);
  }

  .filter-list {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }

  .filter-item {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
  }

  .clear-all-filters {
    background: var(--error-color);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  .clear-all-filters:hover {
    background: #dc2626;
  }

  .forum-item {
    transition: all 0.2s ease;
  }

  .forum-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
  }

  .view-toggle {
    position: sticky;
    top: 80px;
    z-index: 10;
    background: var(--background-color);
    padding: 1rem 0;
    margin: -1rem 0 1rem 0;
  }

  @media (max-width: 768px) {
    .filter-summary {
      flex-direction: column;
      gap: 1rem;
      align-items: stretch;
    }

    .filter-list {
      justify-content: center;
    }

    .view-toggle {
      position: static;
      justify-content: center;
    }
  }
`
document.head.appendChild(style)
