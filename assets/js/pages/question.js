// CBT Question Page JavaScript - Pure AJAX Navigation
class CBTNavigator {
  constructor() {
    this.sessionId = window.cbtData.sessionId;
    this.totalQuestions = window.cbtData.totalQuestions;
    this.currentQuestion = window.cbtData.currentQuestion;
    this.currentQuestionData = window.cbtData.initialQuestionData;
    this.isLoading = false;

    this.init();
  }

  init() {
    this.renderQuestionContent();
    this.attachEventListeners();
    this.initializeAnswerSelection();
  }

  renderQuestionContent() {
    const content = this.generateQuestionHTML();
    document.getElementById("question-content").innerHTML = content;
    this.updateNavigationButtons();
  }

  generateQuestionHTML() {
    const question = this.currentQuestionData;
    const selectedAnswer = window.cbtData.initialSelectedAnswer;
    const isRagu = window.cbtData.initialIsRagu;

    let imageHTML = "";
    if (question.question_image) {
      imageHTML = `
              <div class="question-image">
                  <img src="uploads/questions/${question.question_image}" 
                       alt="Gambar soal" class="img-responsive">
              </div>
          `;
    }

    let optionsHTML = "";
    const options = ["A", "B", "C", "D", "E"];
    options.forEach((option) => {
      const optionText = question[`option_${option.toLowerCase()}`];
      if (optionText) {
        const isSelected = selectedAnswer === option;
        optionsHTML += `
                  <label class="answer-option ${isSelected ? "selected" : ""}">
                      <input type="radio" name="selected_answer" value="${option}" 
                             ${isSelected ? "checked" : ""} 
                             data-question-id="${question.id}">
                      <span class="option-letter">${option}</span>
                      <span class="option-text">${this.escapeHtml(
                        optionText
                      )}</span>
                  </label>
              `;
      }
    });

    return `
          <div class="question-header">
              <div class="question-meta">
                  <span class="question-topic">${this.escapeHtml(
                    question.topic_name
                  )}</span>
                  <span class="question-points">${question.points} poin</span>
              </div>
          </div>

          <div class="question-body">
              <div class="question-text">
                  ${this.escapeHtml(question.question_text).replace(
                    /\n/g,
                    "<br>"
                  )}
              </div>
              ${imageHTML}
          </div>

          <div class="answer-options">
              ${optionsHTML}
          </div>

          <div class="question-actions">
              ${
                this.currentQuestion > 1
                  ? `<button type="button" class="btn btn-secondary" id="btn-prev">Sebelumnya</button>`
                  : ""
              }
              
              <div class="action-group">
                  <button type="button" class="btn btn-warning" id="btn-ragu">
                      <span class="btn-icon">🚩</span> Ragu-ragu
                  </button>
                  
                  <button type="button" class="btn btn-outline" id="btn-save">
                      <span class="btn-icon">💾</span> Simpan Jawaban
                  </button>
                  
                  ${
                    this.currentQuestion < this.totalQuestions
                      ? `<button type="button" class="btn btn-primary" id="btn-next">Selanjutnya</button>`
                      : `<button type="button" class="btn btn-primary" id="btn-finish">Selesai</button>`
                  }
              </div>
          </div>
      `;
  }

  updateNavigationButtons() {
    // Update navigation sidebar
    document.querySelectorAll(".question-number").forEach((btn) => {
      btn.classList.remove("current");
      if (parseInt(btn.dataset.question) === this.currentQuestion) {
        btn.classList.add("current");
      }
    });

    // Update progress display
    document.getElementById("current-question-display").textContent =
      this.currentQuestion;

    // Update page title
    document.title = `Soal ${this.currentQuestion} - CBT System`;
  }

  attachEventListeners() {
    // Navigation buttons in sidebar
    document.addEventListener("click", (e) => {
      if (e.target.classList.contains("question-number")) {
        const targetQuestion = parseInt(e.target.dataset.question);
        if (targetQuestion !== this.currentQuestion && !this.isLoading) {
          this.navigateToQuestion(targetQuestion);
        }
      }
    });

    // Action buttons (delegated event listeners)
    document.addEventListener("click", (e) => {
      if (this.isLoading) return;

      switch (e.target.id) {
        case "btn-prev":
          this.navigateToQuestion(this.currentQuestion - 1);
          break;
        case "btn-next":
          this.navigateToQuestion(this.currentQuestion + 1);
          break;
        case "btn-finish":
          if (confirm("Yakin ingin menyelesaikan sesi ini?")) {
            this.finishSession();
          }
          break;
        case "btn-save":
          this.saveAnswer("submit_answer");
          break;
        case "btn-ragu":
          this.saveAnswer("ragu_ragu");
          break;
      }
    });

    // Answer selection
    document.addEventListener("change", (e) => {
      if (e.target.name === "selected_answer") {
        this.handleAnswerSelection(e.target);
      }
    });

    // Answer option clicks
    document.addEventListener("click", (e) => {
      if (e.target.closest(".answer-option")) {
        const option = e.target.closest(".answer-option");
        const radio = option.querySelector('input[type="radio"]');
        if (radio && radio !== e.target) {
          radio.checked = true;
          this.handleAnswerSelection(radio);
        }
      }
    });

    // Keyboard navigation
    document.addEventListener("keydown", (e) => {
      if (this.isLoading) return;

      // Number keys 1-5 for selecting answers
      if (e.key >= "1" && e.key <= "5") {
        const optionIndex = parseInt(e.key) - 1;
        const radios = document.querySelectorAll(
          'input[name="selected_answer"]'
        );
        if (radios[optionIndex]) {
          radios[optionIndex].checked = true;
          this.handleAnswerSelection(radios[optionIndex]);
        }
      }

      // Arrow keys for navigation
      if (e.key === "ArrowLeft" && this.currentQuestion > 1) {
        e.preventDefault();
        this.navigateToQuestion(this.currentQuestion - 1);
      } else if (
        e.key === "ArrowRight" &&
        this.currentQuestion < this.totalQuestions
      ) {
        e.preventDefault();
        this.navigateToQuestion(this.currentQuestion + 1);
      }
    });
  }

  initializeAnswerSelection() {
    // Set initial answer if exists
    const selectedAnswer = window.cbtData.initialSelectedAnswer;
    if (selectedAnswer) {
      const radio = document.querySelector(
        `input[name="selected_answer"][value="${selectedAnswer}"]`
      );
      if (radio) {
        radio.checked = true;
        this.handleAnswerSelection(radio);
      }
    }
  }

  handleAnswerSelection(radio) {
    // Update visual selection
    document
      .querySelectorAll(".answer-option")
      .forEach((opt) => opt.classList.remove("selected"));
    const parentOption = radio.closest(".answer-option");
    if (parentOption) {
      parentOption.classList.add("selected");
    }
  }

  async navigateToQuestion(targetQuestion) {
    if (
      this.isLoading ||
      targetQuestion < 1 ||
      targetQuestion > this.totalQuestions
    ) {
      return;
    }

    this.setLoading(true);

    try {
      const formData = new FormData();
      formData.append("action", "navigate");
      formData.append("target_question", targetQuestion);

      const response = await fetch(window.location.href, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
      });

      const data = await response.json();

      if (data.status === "completed") {
        window.location.href = data.redirect_url;
        return;
      }

      if (data.status === "success") {
        this.currentQuestion = data.question_number;
        this.currentQuestionData = data.question;
        window.cbtData.initialSelectedAnswer = data.selected_answer;
        window.cbtData.initialIsRagu = data.is_ragu;

        this.renderQuestionContent();
        this.initializeAnswerSelection();
        this.showMessage(`Navigasi ke soal ${data.question_number}`, "info");
      } else {
        this.showMessage(data.message || "Gagal navigasi", "error");
      }
    } catch (error) {
      console.error("Navigation error:", error);
      this.showMessage("Terjadi kesalahan saat navigasi", "error");
    } finally {
      this.setLoading(false);
    }
  }

  async saveAnswer(action) {
    if (this.isLoading) return;

    const selectedRadio = document.querySelector(
      'input[name="selected_answer"]:checked'
    );
    const selectedAnswer = selectedRadio ? selectedRadio.value : "";

    if (action === "submit_answer" && !selectedAnswer) {
      this.showMessage("Pilih jawaban terlebih dahulu", "warning");
      return;
    }

    this.setLoading(true);

    try {
      const formData = new FormData();
      formData.append("action", action);
      formData.append("question_id", this.currentQuestionData.id);
      formData.append("selected_answer", selectedAnswer);

      const response = await fetch(window.location.href, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
      });

      const data = await response.json();

      if (data.status === "success") {
        this.updateQuestionStatus(
          this.currentQuestionData.id,
          action === "ragu_ragu",
          action === "submit_answer"
        );
        this.updateStats();
        this.showMessage(data.message, "success");
      } else {
        this.showMessage(data.message || "Gagal menyimpan", "error");
      }
    } catch (error) {
      console.error("Save error:", error);
      this.showMessage("Terjadi kesalahan saat menyimpan", "error");
    } finally {
      this.setLoading(false);
    }
  }

  async finishSession() {
    if (this.isLoading) return;

    this.setLoading(true);

    try {
      const formData = new FormData();
      formData.append("action", "navigate");
      formData.append("target_question", this.totalQuestions + 1);

      const response = await fetch(window.location.href, {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
      });

      const data = await response.json();

      if (data.status === "completed") {
        window.location.href = data.redirect_url;
      } else {
        this.showMessage("Gagal menyelesaikan sesi", "error");
      }
    } catch (error) {
      console.error("Finish error:", error);
      this.showMessage("Terjadi kesalahan saat menyelesaikan sesi", "error");
    } finally {
      this.setLoading(false);
    }
  }

  updateQuestionStatus(questionId, isRagu, isAnswered) {
    const questionBtn = document.querySelector(
      `[data-question="${this.currentQuestion}"]`
    );
    if (questionBtn) {
      questionBtn.classList.remove("answered", "ragu");
      if (isRagu) {
        questionBtn.classList.add("ragu");
      } else if (isAnswered) {
        questionBtn.classList.add("answered");
      }
    }
  }

  updateStats() {
    const answeredElements = document.querySelectorAll(
      ".question-number.answered"
    );
    const raguElements = document.querySelectorAll(".question-number.ragu");

    const answeredStat = document.getElementById("answered-count");
    const raguStat = document.getElementById("ragu-count");

    if (answeredStat) answeredStat.textContent = answeredElements.length;
    if (raguStat) raguStat.textContent = raguElements.length;
  }

  setLoading(isLoading) {
    this.isLoading = isLoading;

    // Update button states
    const buttons = document.querySelectorAll("button");
    buttons.forEach((btn) => {
      btn.disabled = isLoading;
      if (isLoading && !btn.dataset.originalText) {
        btn.dataset.originalText = btn.innerHTML;
        if (
          btn.id &&
          (btn.id.includes("prev") ||
            btn.id.includes("next") ||
            btn.classList.contains("question-number"))
        ) {
          btn.innerHTML = "Loading...";
        } else if (
          btn.id &&
          (btn.id.includes("save") || btn.id.includes("ragu"))
        ) {
          btn.innerHTML = "Menyimpan...";
        }
      } else if (!isLoading && btn.dataset.originalText) {
        btn.innerHTML = btn.dataset.originalText;
        delete btn.dataset.originalText;
      }
    });

    // Update navigation buttons opacity
    document.querySelectorAll(".question-number").forEach((btn) => {
      btn.style.opacity = isLoading ? "0.6" : "1";
      btn.style.pointerEvents = isLoading ? "none" : "auto";
    });
  }

  showMessage(message, type = "success") {
    const colors = {
      success: "#10b981",
      info: "#2563eb",
      warning: "#f59e0b",
      error: "#ef4444",
    };

    const messageDiv = document.createElement("div");
    messageDiv.style.cssText = `
          position: fixed;
          top: 20px;
          right: 20px;
          background: ${colors[type] || colors.success};
          color: white;
          padding: 12px 20px;
          border-radius: 8px;
          font-weight: 500;
          z-index: 1000;
          box-shadow: 0 4px 12px rgba(0,0,0,0.15);
          animation: slideInRight 0.3s ease;
      `;
    messageDiv.textContent = message;

    document.body.appendChild(messageDiv);

    setTimeout(
      () => {
        messageDiv.style.animation = "slideInRight 0.3s ease reverse";
        setTimeout(() => {
          if (document.body.contains(messageDiv)) {
            document.body.removeChild(messageDiv);
          }
        }, 300);
      },
      type === "info" ? 1500 : 3000
    );
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }
}

// Add CSS for slide animation
const style = document.createElement("style");
style.textContent = `
  @keyframes slideInRight {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
  }
`;
document.head.appendChild(style);

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  new CBTNavigator();
});
