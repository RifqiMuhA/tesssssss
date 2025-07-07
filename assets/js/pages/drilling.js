// Drilling Page JavaScript functionality

// Section toggle functionality
function toggleSection(sectionId) {
  const section = document.getElementById(sectionId);
  section.classList.toggle("collapsed");

  // Store section state in localStorage
  const isCollapsed = section.classList.contains("collapsed");
  localStorage.setItem(sectionId + "_collapsed", isCollapsed);
}

// Initialize section states from localStorage
function initSectionStates() {
  const sections = ["tpsSection", "literasiSection"];
  sections.forEach((sectionId) => {
    const isCollapsed =
      localStorage.getItem(sectionId + "_collapsed") === "true";
    const section = document.getElementById(sectionId);
    if (section && isCollapsed) {
      section.classList.add("collapsed");
    }
  });
}

// Add loading state to drilling buttons
function initDrillingButtons() {
  document.querySelectorAll(".btn-primary").forEach((button) => {
    button.addEventListener("click", function (e) {
      if (!this.disabled) { 
        const originalContent = this.innerHTML;
        this.innerHTML =
          '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite; margin-right: 0.5rem;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>Memuat...';
        this.disabled = true;

        // Re-enable after 3 seconds if page doesn't change
        setTimeout(() => {
          if (this.disabled) {
            this.innerHTML = originalContent;
            this.disabled = false;
          }
        }, 3000);
      }
    });
  });
}

// Initialize drilling page functionality
function initDrillingPage() {
  initSectionStates();
  initDrillingButtons();

  console.log("Drilling page loaded successfully!");
}

// Auto-initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  initDrillingPage();
});
