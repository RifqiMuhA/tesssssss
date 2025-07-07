document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");
  const dateFilter = document.getElementById("dateFilter");
  const sortFilter = document.getElementById("sortFilter");
  const loadingIndicator = document.getElementById("loadingIndicator");
  const threadListContainer = document.getElementById("threadListContainer");

  let searchTimeout;
  let currentPage = 1;

  // Live search functionality
  searchInput.addEventListener("input", function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      currentPage = 1;
      loadThreads();
    }, 300);
  });

  // Filter change handlers
  [categoryFilter, dateFilter, sortFilter].forEach((filter) => {
    filter.addEventListener("change", function () {
      currentPage = 1;
      loadThreads();
    });
  });

  // Pagination handler
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("pagination-btn")) {
      e.preventDefault();
      currentPage = parseInt(e.target.dataset.page);
      loadThreads();
    }
  });

  function loadThreads() {
    const params = new URLSearchParams({
      ajax: "1",
      search: searchInput.value,
      category: categoryFilter.value,
      date: dateFilter.value,
      sort: sortFilter.value,
      page: currentPage,
    });

    // Show loading
    loadingIndicator.style.display = "flex";
    threadListContainer.style.opacity = "0.5";

    fetch(`forum.php?${params.toString()}`)
      .then((response) => response.text())
      .then((html) => {
        threadListContainer.innerHTML = html;

        // Hide loading
        loadingIndicator.style.display = "none";
        threadListContainer.style.opacity = "1";

        // Add click handlers to new thread items
        addThreadClickHandlers();

        // Smooth scroll to top
        threadListContainer.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      })
      .catch((error) => {
        console.error("Error loading threads:", error);
        loadingIndicator.style.display = "none";
        threadListContainer.style.opacity = "1";
      });
  }

  function addThreadClickHandlers() {
    const threadItems = document.querySelectorAll(".thread-item");
    threadItems.forEach((item) => {
      item.addEventListener("click", function () {
        const threadId = this.getAttribute("onclick").match(
          /thread\.php\?id=(\d+)/
        )[1];
        window.location.href = `thread.php?id=${threadId}`;
      });
    });
  }

  // Initial setup
  addThreadClickHandlers();
});
