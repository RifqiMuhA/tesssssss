// Admin Users Management JavaScript

document.addEventListener("DOMContentLoaded", () => {
  // Initialize user management features
  initializeUserActions()
  initializeFilters()
  initializeBulkActions()
})

function initializeUserActions() {
  // Add confirmation for destructive actions
  const deleteButtons = document.querySelectorAll(".btn-action.delete")
  const resetButtons = document.querySelectorAll(".btn-action.reset")

  deleteButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      e.preventDefault()
      const form = button.closest("form")
      const userName = form.closest("tr").querySelector(".user-name").textContent

      if (confirm(`Are you sure you want to delete user "${userName}"? This action cannot be undone.`)) {
        form.submit()
      }
    })
  })

  resetButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      e.preventDefault()
      const form = button.closest("form")
      const userName = form.closest("tr").querySelector(".user-name").textContent

      if (confirm(`Reset password for "${userName}" to "password123"?`)) {
        form.submit()
      }
    })
  })

  // Add loading states
  const actionForms = document.querySelectorAll('form[method="POST"]')
  actionForms.forEach((form) => {
    form.addEventListener("submit", () => {
      const button = form.querySelector('button[type="submit"]')
      if (button) {
        button.disabled = true
        button.innerHTML =
          '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>'
      }
    })
  })
}

function initializeFilters() {
  const filterForm = document.querySelector(".filter-form")
  const searchInput = document.querySelector(".search-input")
  const filterSelects = document.querySelectorAll(".filter-select")

  // Auto-submit on filter change
  filterSelects.forEach((select) => {
    select.addEventListener("change", () => {
      filterForm.submit()
    })
  })

  // Search with debounce
  let searchTimeout
  searchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
      filterForm.submit()
    }, 500)
  })

  // Clear filters button
  const clearFiltersBtn = document.createElement("button")
  clearFiltersBtn.type = "button"
  clearFiltersBtn.className = "btn btn-secondary"
  clearFiltersBtn.textContent = "Clear Filters"
  clearFiltersBtn.addEventListener("click", () => {
    searchInput.value = ""
    filterSelects.forEach((select) => (select.value = ""))
    filterForm.submit()
  })

  // Add clear button if any filters are active
  const hasActiveFilters = searchInput.value || Array.from(filterSelects).some((select) => select.value)
  if (hasActiveFilters) {
    filterForm.appendChild(clearFiltersBtn)
  }
}

function initializeBulkActions() {
  // Add bulk selection functionality
  const table = document.querySelector(".admin-table")
  if (!table) return

  // Add master checkbox
  const headerRow = table.querySelector("thead tr")
  const masterCheckbox = document.createElement("th")
  masterCheckbox.innerHTML = '<input type="checkbox" id="select-all">'
  headerRow.insertBefore(masterCheckbox, headerRow.firstChild)

  // Add individual checkboxes
  const bodyRows = table.querySelectorAll("tbody tr")
  bodyRows.forEach((row) => {
    const checkbox = document.createElement("td")
    checkbox.innerHTML = `<input type="checkbox" name="selected_users[]" value="${getUserId(row)}">`
    row.insertBefore(checkbox, row.firstChild)
  })

  // Master checkbox functionality
  const selectAllCheckbox = document.getElementById("select-all")
  const individualCheckboxes = document.querySelectorAll('input[name="selected_users[]"]')

  selectAllCheckbox.addEventListener("change", () => {
    individualCheckboxes.forEach((checkbox) => {
      checkbox.checked = selectAllCheckbox.checked
    })
    updateBulkActions()
  })

  individualCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener("change", () => {
      updateBulkActions()
      updateMasterCheckbox()
    })
  })

  // Add bulk actions toolbar
  createBulkActionsToolbar()
}

function getUserId(row) {
  const deleteForm = row.querySelector('form[action*="delete_user"]')
  if (deleteForm) {
    const userIdInput = deleteForm.querySelector('input[name="user_id"]')
    return userIdInput ? userIdInput.value : ""
  }
  return ""
}

function updateMasterCheckbox() {
  const selectAllCheckbox = document.getElementById("select-all")
  const individualCheckboxes = document.querySelectorAll('input[name="selected_users[]"]')
  const checkedBoxes = document.querySelectorAll('input[name="selected_users[]"]:checked')

  if (checkedBoxes.length === 0) {
    selectAllCheckbox.indeterminate = false
    selectAllCheckbox.checked = false
  } else if (checkedBoxes.length === individualCheckboxes.length) {
    selectAllCheckbox.indeterminate = false
    selectAllCheckbox.checked = true
  } else {
    selectAllCheckbox.indeterminate = true
  }
}

function updateBulkActions() {
  const checkedBoxes = document.querySelectorAll('input[name="selected_users[]"]:checked')
  const bulkToolbar = document.querySelector(".bulk-actions-toolbar")

  if (checkedBoxes.length > 0) {
    bulkToolbar.style.display = "flex"
    bulkToolbar.querySelector(".selected-count").textContent = `${checkedBoxes.length} selected`
  } else {
    bulkToolbar.style.display = "none"
  }
}

function createBulkActionsToolbar() {
  const container = document.querySelector(".admin-container")
  const toolbar = document.createElement("div")
  toolbar.className = "bulk-actions-toolbar"
  toolbar.style.display = "none"
  toolbar.innerHTML = `
    <div class="bulk-info">
      <span class="selected-count">0 selected</span>
    </div>
    <div class="bulk-actions">
      <button type="button" class="btn btn-secondary" onclick="bulkActivate()">Activate</button>
      <button type="button" class="btn btn-secondary" onclick="bulkDeactivate()">Deactivate</button>
      <button type="button" class="btn btn-danger" onclick="bulkDelete()">Delete</button>
    </div>
  `

  // Insert after admin header
  const adminHeader = container.querySelector(".admin-header")
  adminHeader.insertAdjacentElement("afterend", toolbar)
}

function bulkActivate() {
  const selectedUsers = getSelectedUsers()
  if (selectedUsers.length === 0) return

  if (confirm(`Activate ${selectedUsers.length} users?`)) {
    performBulkAction("activate", selectedUsers)
  }
}

function bulkDeactivate() {
  const selectedUsers = getSelectedUsers()
  if (selectedUsers.length === 0) return

  if (confirm(`Deactivate ${selectedUsers.length} users?`)) {
    performBulkAction("deactivate", selectedUsers)
  }
}

function bulkDelete() {
  const selectedUsers = getSelectedUsers()
  if (selectedUsers.length === 0) return

  if (confirm(`Delete ${selectedUsers.length} users? This action cannot be undone.`)) {
    performBulkAction("delete", selectedUsers)
  }
}

function getSelectedUsers() {
  const checkedBoxes = document.querySelectorAll('input[name="selected_users[]"]:checked')
  return Array.from(checkedBoxes).map((checkbox) => checkbox.value)
}

function performBulkAction(action, userIds) {
  const form = document.createElement("form")
  form.method = "POST"
  form.innerHTML = `
    <input type="hidden" name="action" value="bulk_${action}">
    ${userIds.map((id) => `<input type="hidden" name="user_ids[]" value="${id}">`).join("")}
  `
  document.body.appendChild(form)
  form.submit()
}

// Add CSS for bulk actions toolbar
const style = document.createElement("style")
style.textContent = `
  .bulk-actions-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--primary-color);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: var(--radius-md);
    margin-bottom: 1rem;
  }

  .bulk-info {
    font-weight: 500;
  }

  .bulk-actions {
    display: flex;
    gap: 0.5rem;
  }

  .bulk-actions .btn {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    color: white;
  }

  .bulk-actions .btn:hover {
    background: rgba(255, 255, 255, 0.2);
  }

  .bulk-actions .btn-danger {
    background: var(--error-color);
    border-color: var(--error-color);
  }
`
document.head.appendChild(style)
