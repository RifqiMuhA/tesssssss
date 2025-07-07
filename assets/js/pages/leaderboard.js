// Add smooth scrolling and enhanced interactions
document.addEventListener('DOMContentLoaded', function() {
// Animate stats on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions); 

// Observe stat cards
document.querySelectorAll('.stat-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = 'all 0.6s ease';
    observer.observe(card);
});

// Smooth filter form submission
const filterForm = document.querySelector('.filter-form');
if (filterForm) {
    filterForm.addEventListener('submit', function(e) {
        const submitBtn = filterForm.querySelector('.btn-primary');
        submitBtn.style.transform = 'scale(0.95)';
        setTimeout(() => {
            submitBtn.style.transform = 'scale(1)';
        }, 150);
    });
}

// Add loading effect for accuracy bars
document.querySelectorAll('.accuracy-fill').forEach((bar, index) => {
    setTimeout(() => {
        bar.style.width = bar.style.width;
    }, index * 100);
});
});