// Result Page JavaScript

document.addEventListener("DOMContentLoaded", () => {
  // Initialize expand/collapse functionality for review items
  initializeReviewToggle()

  // Initialize animations
  initializeAnimations()

  // Initialize share functionality 
  initializeShareButtons()
})

function initializeReviewToggle() {
  // Remove existing event listeners to prevent duplicates
  const expandButtons = document.querySelectorAll(".expand-btn");

  expandButtons.forEach((button, index) => {
    // Remove onclick attribute to prevent conflicts
    button.removeAttribute("onclick");

    // Add click event listener
    button.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      toggleReview(index);
    });
  });

  // Also add click to review header
  const reviewHeaders = document.querySelectorAll(".review-header-item");
  reviewHeaders.forEach((header, index) => {
    header.addEventListener("click", (e) => {
      // Don't trigger if clicking the button itself
      if (!e.target.closest(".expand-btn")) {
        toggleReview(index);
      }
    });
  });
}

function toggleReview(index) {
  const reviewContent = document.getElementById(`review-${index}`);
  const expandButton = document.querySelectorAll(".expand-btn")[index];

  if (!reviewContent || !expandButton) {
    console.error(`Review content or button not found for index ${index}`);
    return;
  }

  const isExpanded = reviewContent.classList.contains("expanded");

  if (isExpanded) {
    reviewContent.classList.remove("expanded");
    expandButton.classList.remove("expanded");
  } else {
    reviewContent.classList.add("expanded");
    expandButton.classList.add("expanded");
  }
}

function initializeAnimations() {
  // Animate score circle
  const scoreCircle = document.querySelector(
    ".score-circle svg circle:last-child"
  );
  if (scoreCircle) {
    setTimeout(() => {
      scoreCircle.style.transition = "stroke-dashoffset 2s ease-in-out";
    }, 500);
  }

  // Animate progress bars
  const progressBars = document.querySelectorAll(".progress-fill");
  progressBars.forEach((bar, index) => {
    setTimeout(() => {
      bar.style.transition = "width 1s ease-in-out";
      const width = bar.style.width;
      bar.style.width = "0%";
      setTimeout(() => {
        bar.style.width = width;
      }, 100);
    }, 1000 + index * 200);
  });

  // Animate stat numbers
  animateNumbers();
}

function animateNumbers() {
  const statNumbers = document.querySelectorAll(".stat-number");

  statNumbers.forEach((element) => {
    const finalValue = Number.parseInt(element.textContent.replace(/,/g, ""));
    if (isNaN(finalValue)) return;

    let currentValue = 0;
    const increment = finalValue / 50;
    const timer = setInterval(() => {
      currentValue += increment;
      if (currentValue >= finalValue) {
        currentValue = finalValue;
        clearInterval(timer);
      }
      element.textContent = Math.floor(currentValue).toLocaleString();
    }, 30);
  });
}

function initializeShareButtons() {
  // Add share functionality if needed
  const resultActions = document.querySelector(".result-actions");
  if (resultActions && !resultActions.querySelector(".share-btn")) {
    const shareButton = document.createElement("button");
    shareButton.className = "btn btn-outline share-btn";
    shareButton.innerHTML = `
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="18" cy="5" r="3"/>
        <circle cx="6" cy="12" r="3"/>
        <circle cx="18" cy="19" r="3"/>
        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
      </svg>
      Bagikan Hasil
    `;

    shareButton.addEventListener("click", shareResult);
    resultActions.appendChild(shareButton);
  }
}

function shareResult() {
  const accuracy = document.querySelector(".score-percentage")?.textContent || "0%"
  const category = document.querySelector(".result-title p")?.textContent || "UTBK"

  const shareText = `Saya baru saja menyelesaikan drilling ${category} dengan akurasi ${accuracy}! 🎯\n\nBergabunglah dengan DrillPTN untuk latihan UTBK gratis: ${window.location.origin}`

  if (navigator.share) {
    navigator.share({
      title: "Hasil Drilling DrillPTN",
      text: shareText,
      url: window.location.origin,
    })
  } else {
    // Fallback: copy to clipboard
    navigator.clipboard
      .writeText(shareText)
      .then(() => {
        showAlert("Teks berhasil disalin ke clipboard!", "success")
      })
      .catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement("textarea")
        textArea.value = shareText
        document.body.appendChild(textArea)
        textArea.select()
        document.execCommand("copy")
        document.body.removeChild(textArea)
        showAlert("Teks berhasil disalin ke clipboard!", "success")
      })
  }
}

// Global function for toggling review items (called from PHP)
window.toggleReview = toggleReview

// Declare showAlert function
function showAlert(message, type) {
  alert(message)
}
