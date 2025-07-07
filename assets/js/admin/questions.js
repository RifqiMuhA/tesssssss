// Admin Questions Management JavaScript

document.addEventListener("DOMContentLoaded", () => {
  // Initialize question management features
  initializeQuestionActions()
  initializeFormValidation()
  initializeFilters()
})

function initializeQuestionActions() {
  // Add confirmation for delete actions
  const deleteButtons = document.querySelectorAll(".btn-action.delete")

  deleteButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      e.preventDefault()
      const form = button.closest("form")

      if (confirm("Are you sure you want to delete this question? This action cannot be undone.")) {
        form.submit()
      }
    })
  })

  // Add loading states for all action buttons
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
}

function initializeFormValidation() {
  const addForm = document.querySelector(".admin-form")
  if (!addForm) return

  const topicSelect = addForm.querySelector("#topic_id")
  const questionText = addForm.querySelector("#question_text")
  const optionInputs = addForm.querySelectorAll('input[name^="option_"]')
  const correctAnswerSelect = addForm.querySelector("#correct_answer")
  const submitButton = addForm.querySelector('button[type="submit"]')

  // Real-time validation
  addForm.addEventListener("input", validateForm)
  addForm.addEventListener("change", validateForm)

  // Character count for question text
  questionText.addEventListener("input", updateCharacterCount)

  // Auto-populate correct answer options
  correctAnswerSelect.addEventListener("focus", updateCorrectAnswerOptions)

  // Form submission validation
  addForm.addEventListener("submit", (e) => {
    if (!validateForm()) {
      e.preventDefault()
      showValidationErrors()
    } else {
      setLoading(submitButton, true)
    }
  })

  function validateForm() {
    const isValid =
      topicSelect.value &&
      questionText.value.trim().length >= 10 &&
      Array.from(optionInputs).every((input) => input.value.trim()) &&
      correctAnswerSelect.value

    submitButton.disabled = !isValid
    return isValid
  }

  function updateCharacterCount() {
    const maxLength = 1000
    const currentLength = questionText.value.length

    let counter = questionText.parentNode.querySelector(".char-counter")
    if (!counter) {
      counter = document.createElement("div")
      counter.className = "char-counter"
      counter.style.fontSize = "0.75rem"
      counter.style.textAlign = "right"
      counter.style.marginTop = "0.25rem"
      questionText.parentNode.appendChild(counter)
    }

    counter.textContent = `${currentLength}/${maxLength} characters`
    counter.style.color = currentLength > maxLength ? "var(--error-color)" : "var(--text-secondary)"

    if (currentLength > maxLength) {
      questionText.style.borderColor = "var(--error-color)"
    } else {
      questionText.style.borderColor = ""
    }
  }

  function updateCorrectAnswerOptions() {
    const options = ["A", "B", "C", "D", "E"]
    const currentValue = correctAnswerSelect.value

    // Clear existing options except the first one
    correctAnswerSelect.innerHTML = '<option value="">Select Correct Answer</option>'

    options.forEach((option) => {
      const optionElement = document.createElement("option")
      optionElement.value = option
      optionElement.textContent = `${option} - ${getOptionText(option)}`
      if (option === currentValue) {
        optionElement.selected = true
      }
      correctAnswerSelect.appendChild(optionElement)
    })
  }

  function getOptionText(option) {
    const input = addForm.querySelector(`#option_${option.toLowerCase()}`)
    const text = input ? input.value.trim() : ""
    return text.length > 30 ? text.substring(0, 30) + "..." : text || `Option ${option}`
  }

  function showValidationErrors() {
    const errors = []

    if (!topicSelect.value) errors.push("Please select a topic")
    if (questionText.value.trim().length < 10) errors.push("Question text must be at least 10 characters")
    if (!Array.from(optionInputs).every((input) => input.value.trim())) errors.push("All answer options must be filled")
    if (!correctAnswerSelect.value) errors.push("Please select the correct answer")

    if (errors.length > 0) {
      alert("Please fix the following errors:\n\n" + errors.join("\n"))
    }
  }
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

  // Add filter indicators
  addFilterIndicators()
}

function addFilterIndicators() {
  const activeFilters = []
  const searchInput = document.querySelector(".search-input")
  const filterSelects = document.querySelectorAll(".filter-select")

  if (searchInput.value) {
    activeFilters.push(`Search: "${searchInput.value}"`)
  }

  filterSelects.forEach((select) => {
    if (select.value) {
      const label = select.previousElementSibling?.textContent || select.name
      const optionText = select.options[select.selectedIndex].textContent
      activeFilters.push(`${label}: ${optionText}`)
    }
  })

  if (activeFilters.length > 0) {
    const indicator = document.createElement("div")
    indicator.className = "filter-indicators"
    indicator.innerHTML = `
      <div class="filter-tags">
        ${activeFilters.map((filter) => `<span class="filter-tag">${filter}</span>`).join("")}
        <button type="button" class="clear-filters" onclick="clearAllFilters()">Clear All</button>
      </div>
    `

    const filterForm = document.querySelector(".admin-filters")
    filterForm.appendChild(indicator)
  }
}

function clearAllFilters() {
  const searchInput = document.querySelector(".search-input")
  const filterSelects = document.querySelectorAll(".filter-select")

  searchInput.value = ""
  filterSelects.forEach((select) => (select.value = ""))

  document.querySelector(".filter-form").submit()
}

function setLoading(button, isLoading) {
  if (isLoading) {
    button.disabled = true
    button.dataset.originalText = button.textContent
    button.textContent = "Adding Question..."
  } else {
    button.disabled = false
    button.textContent = button.dataset.originalText || "Add Question"
  }
}

// Add CSS for filter indicators
const style = document.createElement("style")
style.textContent = `
  .filter-indicators {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
  }

  .filter-tags {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
  }

  .filter-tag {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
  }

  .clear-filters {
    background: var(--error-color);
    color: white;
    border: none;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-sm);
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
  }

  .clear-filters:hover {
    background: #dc2626;
  }

  .char-counter {
    font-size: 0.75rem;
    text-align: right;
    margin-top: 0.25rem;
    color: var(--text-secondary);
  }
`
document.head.appendChild(style)
