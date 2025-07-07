// Mobile menu functionality for main navbar
const menuToggle = document.querySelector('.menu-toggle');
const navMenu = document.querySelector('.nav-menu');

menuToggle.addEventListener('click', () => {
    menuToggle.classList.toggle('active');
    navMenu.classList.toggle('active');
});

// Mobile menu functionality for simple navbar
const simpleMenuToggle = document.querySelector('.simple-menu-toggle');
const simpleNavMenu = document.querySelector('.simple-nav-menu');

simpleMenuToggle.addEventListener('click', () => {
    simpleMenuToggle.classList.toggle('active');
    simpleNavMenu.classList.toggle('active');
});

// Close mobile menu when a link is clicked
document.querySelectorAll('.simple-nav-link, .simple-login-btn').forEach(link => {
    link.addEventListener('click', () => {
        menuToggle.classList.remove('active');
        navMenu.classList.remove('active');
        simpleMenuToggle.classList.remove('active');
        simpleNavMenu.classList.remove('active');
    });
});

// Loading Animation
window.addEventListener('load', function() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    
    setTimeout(() => {
        gsap.to(loadingOverlay, {
            opacity: 0,
            duration: 0.5,
            onComplete: () => {
                loadingOverlay.style.display = 'none';
                initAnimations();
            }
        });
    }, 2000); 
});

// Initialize animations after loading
function initAnimations() {
    // Animate video in
    gsap.to('.hero-video', {
        opacity: 1,
        scale: 1,
        duration: 1.5,
        ease: "power2.out"
    });

    // Animate mascot in after video
    gsap.to('.mascot', {
        opacity: 1,
        scale: 1,
        duration: 0.8,
        ease: "back.out(1.7)",
        delay: 1.5
    });

    // Floating animation for mascot
    gsap.to('.mascot', {
        y: -10,
        duration: 2,
        yoyo: true,
        repeat: -1,
        ease: "power2.inOut",
        delay: 2.5
    });

    // Setup scroll-based navbar switching
    setupNavbarScrolling();

    // Setup about section animations
    setupAboutAnimations();
}

// About section scroll animations
function setupAboutAnimations() {
    const aboutContent = document.querySelector('.about-content');
    const aboutVisual = document.querySelector('.about-visual');
    const statNumbers = document.querySelectorAll('.stat-number');

    // Create intersection observer for about section
    const aboutObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Animate content from left
                gsap.to(aboutContent, {
                    opacity: 1,
                    x: 0,
                    duration: 1,
                    ease: "power2.out"
                });

                // Animate visual from right (only if not hidden on mobile)
                if (window.innerWidth > 768) {
                    gsap.to(aboutVisual, {
                        opacity: 1,
                        x: 0,
                        duration: 1,
                        ease: "power2.out",
                        delay: 0.3
                    });

                    // Animate visual cards
                    gsap.from('.visual-card', {
                        scale: 0.8,
                        opacity: 0,
                        duration: 0.8,
                        stagger: 0.1,
                        ease: "back.out(1.7)",
                        delay: 0.8
                    });
                }

                // Animate statistics numbers
                statNumbers.forEach((stat, index) => {
                    const finalValue = stat.textContent;
                    const numericValue = parseInt(finalValue.replace(/\D/g, ''));
                    const suffix = finalValue.replace(/[0-9]/g, '');
                    
                    gsap.from(stat, {
                        textContent: 0,
                        duration: 2,
                        ease: "power2.out",
                        delay: 1.2 + (index * 0.2),
                        snap: { textContent: 1 },
                        onUpdate: function() {
                            stat.textContent = Math.ceil(this.targets()[0].textContent) + suffix;
                        },
                        onComplete: function() {
                            stat.textContent = finalValue;
                        }
                    });
                });

                // Only animate once
                aboutObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.3 });

    aboutObserver.observe(document.querySelector('.about-section'));
}

// Navbar scrolling functionality
function setupNavbarScrolling() {
    const header = document.querySelector('.header');
    const simpleNavbar = document.querySelector('.simple-navbar');
    const headerHeight = header.offsetHeight;
    
    let isSimpleNavbarVisible = false;

    window.addEventListener('scroll', () => {
        const scrollY = window.pageYOffset;
        
        // Show simple navbar when scrolled past header height + 50px
        if (scrollY > headerHeight + 50 && !isSimpleNavbarVisible) {
            simpleNavbar.classList.add('visible');
            isSimpleNavbarVisible = true;
        }
        // Hide simple navbar when back near top
        else if (scrollY <= headerHeight && isSimpleNavbarVisible) {
            simpleNavbar.classList.remove('visible');
            isSimpleNavbarVisible = false;
        }
    });
}

// Video error handling
const video = document.querySelector('.hero-video');
video.addEventListener('error', function() {
    // If video fails to load, show a background image instead
    const container = document.querySelector('.hero-video-container');
    container.style.background = 'linear-gradient(135deg, rgba(44, 62, 80, 0.8), rgba(52, 73, 94, 0.9)), url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Cg fill-opacity=\'0.1\'%3E%3Cpolygon fill=\'%23000\' points=\'50 0 60 40 100 50 60 60 50 100 40 60 0 50 40 40\'/%3E%3C/g%3E%3C/svg%3E")';
    container.style.backgroundSize = 'cover, 20px 20px';
    video.style.display = 'none';
});

// Smooth scrolling for navigation links (both navbars)
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Button interactions
document.querySelectorAll('.btn-daftar, .btn-contact').forEach(btn => {
    btn.addEventListener('mouseenter', function() {
        gsap.to(this, {
            scale: 1.05,
            duration: 0.3,
            ease: "power2.out"
        });
    });

    btn.addEventListener('mouseleave', function() {
        gsap.to(this, {
            scale: 1,
            duration: 0.3,
            ease: "power2.out"
        });
    });
});

// Mascot click interaction
document.querySelector('.mascot').addEventListener('click', function() {
    gsap.to(this, {
        rotation: 360,
        scale: 1.2,
        duration: 0.6,
        ease: "back.out(1.7)",
        onComplete: () => {
            gsap.to(this, {
                rotation: 0,
                scale: 1,
                duration: 0.3,
                ease: "power2.out"
            });
        }
    });
});

// Parallax effect on scroll
window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const video = document.querySelector('.hero-video');
    if (video) {
        gsap.to(video, {
            y: scrolled * 0.5,
            duration: 0.1
        });
    }
});

// Close mobile menu when clicking outside
document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-container') && !e.target.closest('.simple-navbar')) {
        menuToggle.classList.remove('active');
        navMenu.classList.remove('active');
        simpleMenuToggle.classList.remove('active');
        simpleNavMenu.classList.remove('active');
    }
});

// GSAP Animations for all sections
function initAllAnimations() {
    // Register ScrollTrigger plugin
    gsap.registerPlugin(ScrollTrigger);
    
    // Liliecomp Section Animations
    gsap.set('.topik-card', { opacity: 0, y: 100, rotationX: 45 });
    
    ScrollTrigger.create({
        trigger: '.tryout-section',
        start: 'top 70%',
        onEnter: () => {
            gsap.to('.topik-card', {
                opacity: 1,
                y: 0,
                rotationX: 0,
                duration: 1.2,
                stagger: 0.2,
                ease: "power3.out"
            });
        }
    });

    // Competition card hover animations
    document.querySelectorAll('.topik-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            gsap.to(card, {
                y: -15,
                scale: 1.02,
                duration: 0.4,
                ease: "power2.out"
            });
            
            gsap.to(card.querySelector('.card-glow'), {
                opacity: 1,
                scale: 0.8,
                duration: 0.6,
                ease: "power2.out"
            });
            
            gsap.to(card.querySelector('.competition-icon'), {
                scale: 1.1,
                rotation: 5,
                duration: 0.4,
                ease: "power2.out"
            });
        });

        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                y: 0,
                scale: 1,
                duration: 0.4,
                ease: "power2.out"
            });
            
            gsap.to(card.querySelector('.card-glow'), {
                opacity: 0,
                scale: 1,
                duration: 0.6,
                ease: "power2.out"
            });
            
            gsap.to(card.querySelector('.competition-icon'), {
                scale: 1,
                rotation: 0,
                duration: 0.4,
                ease: "power2.out"
            });
        });
    });

    // Liliefors Section Animations
    gsap.set('.speaker-frame', { opacity: 0, scale: 0.8, rotationY: 45 });
    gsap.set('.details-card', { opacity: 0, x: 100 });
    gsap.set('.achievement-item', { opacity: 0, y: 30 });

    ScrollTrigger.create({
        trigger: '.forum-section',
        start: 'top 60%',
        onEnter: () => {
            // Speaker frame animation
            gsap.to('.speaker-frame', {
                opacity: 1,
                scale: 1,
                rotationY: 0,
                duration: 1.5,
                ease: "power3.out"
            });
            
            // Details card animation
            gsap.to('.details-card', {
                opacity: 1,
                x: 0,
                duration: 1.2,
                delay: 0.5,
                ease: "power3.out"
            });
            
            // Achievement items stagger animation
            gsap.to('.achievement-item', {
                opacity: 1,
                y: 0,
                duration: 0.8,
                stagger: 0.2,
                delay: 1,
                ease: "power2.out"
            });
        }
    });

    // Floating elements continuous animation
    gsap.to('.float-element', {
        y: -20,
        duration: 3,
        stagger: 0.5,
        yoyo: true,
        repeat: -1,
        ease: "power2.inOut"
    });

    // Frame glow pulsing animation
    gsap.to('.frame-glow', {
        opacity: 0.8,
        scale: 1.05,
        duration: 3,
        yoyo: true,
        repeat: -1,
        ease: "power2.inOut"
    });

    // Footer entrance animation
    gsap.set('.footer-brand, .link-group', { opacity: 0, y: 50 });

    ScrollTrigger.create({
        trigger: '.footer',
        start: 'top 80%',
        onEnter: () => {
            gsap.to('.footer-brand', {
                opacity: 1,
                y: 0,
                duration: 1,
                ease: "power2.out"
            });
            
            gsap.to('.link-group', {
                opacity: 1,
                y: 0,
                duration: 0.8,
                stagger: 0.2,
                delay: 0.3,
                ease: "power2.out"
            });
        }
    });

    // Parallax effect for section backgrounds
    gsap.to('.tryout-section::before', {
        yPercent: -50,
        ease: "none",
        scrollTrigger: {
            trigger: '.tryout-section',
            start: 'top bottom',
            end: 'bottom top',
            scrub: true
        }
    });

    // Mouse follow effect for competition cards
    document.querySelectorAll('.topik-card').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            gsap.to(card, {
                rotationY: x / 10,
                rotationX: -y / 10,
                duration: 0.3,
                ease: "power2.out"
            });
        });

        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                rotationY: 0,
                rotationX: 0,
                duration: 0.5,
                ease: "power2.out"
            });
        });
    });

    // Continuous animations for background elements
    gsap.to('.card-pattern', {
        rotation: 360,
        duration: 20,
        repeat: -1,
        ease: "none"
    });

    // Banner content bounce animation
    gsap.to('.banner-icon', {
        y: -10,
        duration: 0.6,
        yoyo: true,
        repeat: -1,
        ease: "power2.inOut"
    });
}

// Testimoni Toggle Function
function toggleText(button) {
    const textElement = button.parentElement.querySelector('.testimoni-text');
    const btnText = button.querySelector('.btn-text');
    const arrow = button.querySelector('.arrow');
    
    const isCollapsed = textElement.classList.contains('collapsed');
    
    if (isCollapsed) {
        // Expand
        textElement.textContent = textElement.dataset.full;
        textElement.classList.remove('collapsed');
        textElement.classList.add('expanded');
        btnText.textContent = 'Tutup';
        button.classList.add('expanded');
    } else {
        // Collapse
        textElement.textContent = textElement.dataset.short;
        textElement.classList.remove('expanded');
        textElement.classList.add('collapsed');
        btnText.textContent = 'Baca Selengkapnya';
        button.classList.remove('expanded');
    }
}

// Testimoni Animation on Scroll
function animateTestimoniOnScroll() {
    const cards = document.querySelectorAll('.testimoni-card');
    
    cards.forEach((card, index) => {
        const cardRect = card.getBoundingClientRect();
        const isVisible = cardRect.top < window.innerHeight && cardRect.bottom > 0;
        
        if (isVisible && !card.classList.contains('testimoni-animated')) {
            setTimeout(() => {
                if (typeof gsap !== 'undefined') {
                    gsap.to(card, {
                        opacity: 1,
                        y: 0,
                        duration: 0.8,
                        ease: "power2.out"
                    });
                } else {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
                card.classList.add('testimoni-animated');
            }, index * 200);
        }
    });
}

// Initialize Testimoni Section
function initTestimoniSection() {
    const cards = document.querySelectorAll('.testimoni-card');
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.6s ease';
    });
    
    // Check initial visibility
    animateTestimoniOnScroll();
    
    // Add scroll listener
    window.addEventListener('scroll', animateTestimoniOnScroll);
    
    // Add hover effects
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (typeof gsap !== 'undefined') {
                gsap.to(this, {
                    y: -8,
                    duration: 0.3,
                    ease: "power2.out"
                });
            } else {
                this.style.transform = 'translateY(-8px)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            if (typeof gsap !== 'undefined') {
                gsap.to(this, {
                    y: 0,
                    duration: 0.3,
                    ease: "power2.out"
                });
            } else {
                this.style.transform = 'translateY(0)';
            }
        });
    });
}

// Initialize all animations when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Wait for GSAP to be ready
    if (typeof gsap !== 'undefined') {
        initAllAnimations();
    } else {
        // Fallback if GSAP isn't loaded
        setTimeout(initAllAnimations, 500);
    }
});

// Function to show anonymous photo when provided
function showAnonymousPhoto(imageSrc) {
    const placeholder = document.querySelector('.placeholder-content');
    const photo = document.querySelector('.anonymous-photo');
    
    if (imageSrc && photo) {
        photo.src = imageSrc;
        photo.style.display = 'block';
        placeholder.style.display = 'none';
        
        // Animate photo reveal
        gsap.fromTo(photo, 
            { opacity: 0, scale: 0.8 },
            { opacity: 1, scale: 1, duration: 1, ease: "power2.out" }
        );
    }z
}

// Intersection Observer for performance
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
}; 

// Enhanced scroll animations with performance optimization
const animateOnScroll = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate');
        }
    });
}, observerOptions);

// Observe all animated elements
document.querySelectorAll('.topik-card, .timeline-item, .speaker-frame').forEach(el => {
    animateOnScroll.observe(el);
});