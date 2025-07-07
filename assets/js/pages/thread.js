// Thread Page JavaScript

document.addEventListener("DOMContentLoaded", () => {
  const replyForm = document.querySelector(".reply-form");

  // Handle reply form submission
  if (replyForm) {
    replyForm.addEventListener("submit", (e) => {
      const submitBtn = replyForm.querySelector('button[type="submit"]');
      const content = replyForm.querySelector("textarea").value.trim();

      if (!content) {
        e.preventDefault();
        showAlert("Konten balasan harus diisi", "error");
        return false;
      }

      if (content.length < 5) {
        e.preventDefault();
        showAlert("Konten balasan minimal 5 karakter", "error");
        return false;
      }

      setLoading(submitBtn, true);
    });

    // Auto-resize textarea
    const textarea = replyForm.querySelector("textarea");
    if (textarea) {
      textarea.addEventListener("input", autoResizeTextarea);
      autoResizeTextarea.call(textarea); // Initial resize
    }
  }

  // Handle like button clicks with AJAX
  document.addEventListener("click", function (e) {
    if (e.target.closest(".like-btn")) {
      e.preventDefault();
      const likeBtn = e.target.closest(".like-btn");
      const postId = likeBtn.dataset.postId;

      // Prevent multiple clicks
      if (likeBtn.disabled) return;

      likeBtn.disabled = true;

      // Send AJAX request
      fetch("", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `action=like_post&post_id=${postId}`,
      })
        .then((response) => response.text())
        .then((data) => {
          // Toggle like state
          const isLiked = likeBtn.classList.contains("liked");
          const likeCount = likeBtn.querySelector(".like-count");
          const heartIcon = likeBtn.querySelector("svg");

          if (isLiked) {
            likeBtn.classList.remove("liked");
            heartIcon.setAttribute("fill", "none");
            likeCount.textContent = parseInt(likeCount.textContent) - 1;
          } else {
            likeBtn.classList.add("liked");
            heartIcon.setAttribute("fill", "#ef4444");
            likeCount.textContent = parseInt(likeCount.textContent) + 1;
          }

          likeBtn.disabled = false;
        })
        .catch((error) => {
          console.error("Error:", error);
          likeBtn.disabled = false;
          showAlert("Terjadi kesalahan saat memproses like", "error");
        });
    }
  });

  // Initialize post interactions
  initializePostInteractions();

  // Initialize keyboard shortcuts
  initializeKeyboardShortcuts();

  // Initialize scroll to reply form
  initializeScrollToReply();
});

function autoResizeTextarea() {
  this.style.height = "auto";
  this.style.height = this.scrollHeight + "px";
}

function initializePostInteractions() {
  const posts = document.querySelectorAll(".post");

  posts.forEach((post, index) => {
    // Add click to focus functionality
    post.addEventListener("click", (e) => {
      if (!e.target.closest(".post-actions") && !e.target.closest("button")) {
        post.classList.add("focused");
        setTimeout(() => {
          post.classList.remove("focused");
        }, 2000);
      }
    });
  });
}

function initializeKeyboardShortcuts() {
  document.addEventListener("keydown", (e) => {
    // Ctrl/Cmd + Enter to submit reply
    if ((e.ctrlKey || e.metaKey) && e.key === "Enter") {
      const replyForm = document.querySelector(".reply-form");
      if (replyForm) {
        const textarea = replyForm.querySelector("textarea");
        if (textarea === document.activeElement) {
          replyForm.submit();
        }
      }
    }

    // R key to focus reply textarea
    if (e.key === "r" || e.key === "R") {
      if (!e.target.matches("input, textarea, select")) {
        e.preventDefault();
        const textarea = document.querySelector(".reply-form textarea");
        if (textarea) {
          textarea.focus();
          textarea.scrollIntoView({
            behavior: "smooth",
            block: "center",
          });
        }
      }
    }
  });
}

function initializeScrollToReply() {
  // Check if URL has #reply-form hash
  if (window.location.hash === "#reply-form") {
    setTimeout(() => {
      const replyForm = document.querySelector("#reply-form");
      if (replyForm) {
        replyForm.scrollIntoView({
          behavior: "smooth",
        });
        const textarea = replyForm.querySelector("textarea");
        if (textarea) {
          textarea.focus();
        }
      }
    }, 500);
  }
}

function setLoading(button, isLoading) {
  if (isLoading) {
    button.disabled = true;
    button.dataset.originalText = button.textContent;
    button.textContent = "Loading...";
  } else {
    button.disabled = false;
    button.textContent = button.dataset.originalText || "Submit";
  }
}

function showAlert(message, type = "info") {
  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type}`;
  alertDiv.textContent = message;

  const container = document.querySelector(".container");
  container.insertBefore(alertDiv, container.firstChild);

  setTimeout(() => {
    alertDiv.remove();
  }, 5000);
}
