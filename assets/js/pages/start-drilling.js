// Start Drilling Page JavaScript - Simple and Working Version

document.addEventListener("DOMContentLoaded", () => {
  const drillingForm = document.querySelector(".drilling-form");
  const topicSelect = document.querySelector("#topic_id");
  const questionCountSelect = document.querySelector("#question_count");
  const submitBtn = drillingForm?.querySelector('button[type="submit"]');

  // Handle form submission
  if (drillingForm) {
    drillingForm.addEventListener("submit", (e) => {
      setLoading(submitBtn, true);
    });
  }

  // Update available question count based on topic selection
  if (topicSelect && questionCountSelect) {
    topicSelect.addEventListener("change", updateQuestionCount);
    updateQuestionCount(); // Initial update
  }

  function updateQuestionCount() {
    const selectedOption = topicSelect.options[topicSelect.selectedIndex];
    if (selectedOption && selectedOption.value) {
      const questionCount = selectedOption.textContent.match(/\((\d+) soal\)/);
      if (questionCount) {
        const maxQuestions = parseInt(questionCount[1]);
        updateQuestionCountOptions(maxQuestions);
      }
    } else {
      // Reset to default options when no topic is selected
      resetQuestionCountOptions();
    }
  }

  function updateQuestionCountOptions(maxQuestions) {
    const currentValue = questionCountSelect.value;
    questionCountSelect.innerHTML = "";

    const options = [10, 20, 30, 50];
    options.forEach((count) => {
      if (count <= maxQuestions) {
        const option = document.createElement("option");
        option.value = count;
        option.textContent = `${count} Soal`;
        if (
          count.toString() === currentValue ||
          (count === 20 && !currentValue)
        ) {
          option.selected = true;
        }
        questionCountSelect.appendChild(option);
      }
    });

    // If no valid options, add a custom option
    if (questionCountSelect.options.length === 0) {
      const option = document.createElement("option");
      option.value = maxQuestions;
      option.textContent = `${maxQuestions} Soal (Maksimal)`;
      option.selected = true;
      questionCountSelect.appendChild(option);
    }
  }

  function resetQuestionCountOptions() {
    questionCountSelect.innerHTML = `
      <option value="10">10 Soal</option>
      <option value="20" selected>20 Soal</option>
      <option value="30">30 Soal</option>
      <option value="50">50 Soal</option>
    `;
  }

  function setLoading(button, isLoading) {
    if (isLoading && button) {
      button.disabled = true;
      const originalText = button.textContent;
      button.textContent = "Memulai...";
      button.setAttribute("data-original-text", originalText);
    } else if (button) {
      button.disabled = false;
      const originalText =
        button.getAttribute("data-original-text") || "Mulai Drilling";
      button.textContent = originalText;
      button.removeAttribute("data-original-text");
    }
  }

  // Handle resume session form
  const resumeForm = document.querySelector(".session-form");
  if (resumeForm) {
    const resumeBtn = resumeForm.querySelector('button[type="submit"]');
    resumeForm.addEventListener("submit", (e) => {
      if (resumeBtn) {
        resumeBtn.disabled = true;
        resumeBtn.textContent = "Melanjutkan...";
      }
    });
  }

  // Add form validation including topic selection
  if (drillingForm) {
    const validateForm = () => {
      const topicSelected = topicSelect.value && topicSelect.value !== "";
      const questionCount = questionCountSelect.value;
      const questionCountValid =
        questionCount &&
        parseInt(questionCount) >= 5 &&
        parseInt(questionCount) <= 50;

      const isValid = topicSelected && questionCountValid;

      if (submitBtn) {
        submitBtn.disabled = !isValid;
      }

      return isValid;
    };

    topicSelect.addEventListener("change", validateForm);
    questionCountSelect.addEventListener("change", validateForm);
    validateForm(); // Initial validation
  }

  // Initialize progress bar animation if present
  const progressFill = document.querySelector(".progress-fill");
  if (progressFill) {
    // Animate progress bar on load
    const targetWidth = progressFill.style.width;
    progressFill.style.width = "0%";

    setTimeout(() => {
      progressFill.style.width = targetWidth;
    }, 300);
  }

  // Handle topic card clicks - SIMPLE VERSION
  setTimeout(() => {
    const topicCards = document.querySelectorAll(".topic-card");
    const topicSelect = document.getElementById("topic_id");

    if (topicCards.length > 0 && topicSelect) {
      topicCards.forEach((card) => {
        card.addEventListener("click", function () {
          const topicNameEl = this.querySelector(".topic-name");
          if (!topicNameEl) return;

          const topicName = topicNameEl.textContent.trim();

          // Try to find and select the matching option
          for (let i = 0; i < topicSelect.options.length; i++) {
            const option = topicSelect.options[i];
            if (option.textContent.indexOf(topicName) !== -1) {
              topicSelect.selectedIndex = i;

              // Trigger change event
              if (typeof updateQuestionCount === "function") {
                updateQuestionCount();
              }

              // Visual feedback
              topicCards.forEach((c) => c.classList.remove("selected"));
              this.classList.add("selected");

              // Scroll to form
              const formCard = document.querySelector(".new-card");
              if (formCard) {
                formCard.scrollIntoView({
                  behavior: "smooth",
                  block: "center",
                });
              }
              break;
            }
          }
        });
      });
    }
  }, 100); // Small delay to ensure DOM is ready
});
