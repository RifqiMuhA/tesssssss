// New Thread Page JavaScript

document.addEventListener("DOMContentLoaded", () => {
  const threadForm = document.querySelector(".thread-form")
  const titleInput = document.querySelector("#title")
  const contentTextarea = document.querySelector("#content")
  const categorySelect = document.querySelector("#category_id")
  const submitBtn = threadForm.querySelector('button[type="submit"]')

  // Form validation
  threadForm.addEventListener("submit", (e) => {
    clearErrors()

    let hasError = false

    // Validate category
    if (!categorySelect.value) {
      showFieldError(categorySelect, "Kategori harus dipilih")
      hasError = true
    }

    // Validate title
    if (!titleInput.value.trim()) {
      showFieldError(titleInput, "Judul harus diisi")
      hasError = true
    } else if (titleInput.value.trim().length < 5) {
      showFieldError(titleInput, "Judul minimal 5 karakter")
      hasError = true
    }

    // Validate content
    if (!contentTextarea.value.trim()) {
      showFieldError(contentTextarea, "Konten harus diisi")
      hasError = true
    } else if (contentTextarea.value.trim().length < 10) {
      showFieldError(contentTextarea, "Konten minimal 10 karakter")
      hasError = true
    }

    if (hasError) {
      e.preventDefault()
      return false
    }

    setLoading(submitBtn, true)
  })

  // Real-time character count
  titleInput.addEventListener("input", () => {
    updateCharacterCount(titleInput, "title")
  })

  contentTextarea.addEventListener("input", () => {
    updateCharacterCount(contentTextarea, "content")
  })

  // Auto-save draft functionality
  let autoSaveTimeout
  const draftKey = "thread_draft"

  // Load draft on page load
  loadDraft()

  // Save draft on input
  titleInput.addEventListener("input", scheduleDraftSave)
  contentTextarea.addEventListener("input", scheduleDraftSave)
  categorySelect.addEventListener("change", scheduleDraftSave)

  // Clear draft on successful submission
  threadForm.addEventListener("submit", () => {
    if (!threadForm.querySelector(".field-error")) {
      clearDraft()
    }
  })

  function updateCharacterCount(element, type) {
    const maxLength = type === "title" ? 100 : 1000
    const currentLength = element.value.length

    // Remove existing counter
    const existingCounter = element.parentNode.querySelector(".char-counter")
    if (existingCounter) {
      existingCounter.remove()
    }

    // Add new counter
    const counter = document.createElement("div")
    counter.className = "char-counter"
    counter.style.fontSize = "0.75rem"
    counter.style.color = currentLength > maxLength ? "var(--error-color)" : "var(--text-secondary)"
    counter.style.textAlign = "right"
    counter.style.marginTop = "0.25rem"
    counter.textContent = `${currentLength}/${maxLength} karakter`

    element.parentNode.appendChild(counter)

    // Update border color if over limit
    if (currentLength > maxLength) {
      element.style.borderColor = "var(--error-color)"
    } else {
      element.style.borderColor = ""
    }
  }

  function scheduleDraftSave() {
    clearTimeout(autoSaveTimeout)
    autoSaveTimeout = setTimeout(saveDraft, 2000)
  }

  function saveDraft() {
    const draft = {
      category_id: categorySelect.value,
      title: titleInput.value,
      content: contentTextarea.value,
      timestamp: Date.now(),
    }

    localStorage.setItem(draftKey, JSON.stringify(draft))
    showDraftSavedIndicator()
  }

  function loadDraft() {
    const draftData = localStorage.getItem(draftKey)
    if (draftData) {
      try {
        const draft = JSON.parse(draftData)

        // Only load if draft is less than 24 hours old
        if (Date.now() - draft.timestamp < 24 * 60 * 60 * 1000) {
          if (draft.category_id) categorySelect.value = draft.category_id
          if (draft.title) titleInput.value = draft.title
          if (draft.content) contentTextarea.value = draft.content

          showDraftLoadedIndicator()
        } else {
          clearDraft()
        }
      } catch (e) {
        clearDraft()
      }
    }
  }

  function clearDraft() {
    localStorage.removeItem(draftKey)
  }

  function showDraftSavedIndicator() {
    showIndicator("Draft tersimpan otomatis", "success")
  }

  function showDraftLoadedIndicator() {
    showIndicator("Draft dimuat dari penyimpanan lokal", "info")
  }

  function showIndicator(message, type) {
    const indicator = document.createElement("div")
    indicator.textContent = message
    indicator.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: ${type === "success" ? "var(--success-color)" : "var(--primary-color)"};
      color: white;
      padding: 0.5rem 1rem;
      border-radius: var(--radius-md);
      font-size: 0.875rem;
      z-index: 1000;
      opacity: 0;
      transition: opacity 0.3s;
    `

    document.body.appendChild(indicator)

    setTimeout(() => {
      indicator.style.opacity = "1"
    }, 100)

    setTimeout(() => {
      indicator.style.opacity = "0"
      setTimeout(() => {
        if (document.body.contains(indicator)) {
          document.body.removeChild(indicator)
        }
      }, 300)
    }, 3000)
  }

  function showFieldError(field, message) {
    field.style.borderColor = "var(--error-color)"

    const errorDiv = document.createElement("div")
    errorDiv.className = "field-error"
    errorDiv.textContent = message
    errorDiv.style.color = "var(--error-color)"
    errorDiv.style.fontSize = "0.875rem"
    errorDiv.style.marginTop = "0.25rem"

    field.parentNode.appendChild(errorDiv)
  }

  function clearErrors() {
    const fieldErrors = document.querySelectorAll(".field-error")
    fieldErrors.forEach((error) => error.remove())

    const inputs = document.querySelectorAll("input, select, textarea")
    inputs.forEach((input) => {
      input.style.borderColor = ""
    })
  }

  function setLoading(button, isLoading) {
    if (isLoading) {
      button.disabled = true
      button.textContent = "Membuat Thread..."
    } else {
      button.disabled = false
      button.textContent = "Buat Thread"
    }
  }
})
