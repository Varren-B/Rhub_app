// RHUB Application JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Mobile Navigation Toggle
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('navLinks');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
        });

        // Close menu when clicking a link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                navLinks.classList.remove('active');
                hamburger.classList.remove('active');
            });
        });
    }

    // Hero Slider
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dot');
    let currentSlide = 0;
    let slideInterval;

    function showSlide(index) {
        slides.forEach((slide, i) => {
            slide.classList.remove('active');
            if (dots[i]) dots[i].classList.remove('active');
        });
        
        currentSlide = index;
        if (currentSlide >= slides.length) currentSlide = 0;
        if (currentSlide < 0) currentSlide = slides.length - 1;
        
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    function startSlider() {
        slideInterval = setInterval(nextSlide, 5000);
    }

    function stopSlider() {
        clearInterval(slideInterval);
    }

    // Initialize slider
    if (slides.length > 0) {
        startSlider();

        // Dot navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                stopSlider();
                showSlide(index);
                startSlider();
            });
        });
    }

    // Testimonials Slider
    const testimonialTrack = document.getElementById('testimonialTrack');
    const prevBtn = document.getElementById('prevTestimonial');
    const nextBtn = document.getElementById('nextTestimonial');
    let testimonialIndex = 0;
    const totalTestimonials = document.querySelectorAll('.testimonial-card').length;

    function showTestimonial(index) {
        if (index >= totalTestimonials) index = 0;
        if (index < 0) index = totalTestimonials - 1;
        testimonialIndex = index;
        
        if (testimonialTrack) {
            testimonialTrack.style.transform = `translateX(-${testimonialIndex * 100}%)`;
        }
    }

    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => showTestimonial(testimonialIndex - 1));
        nextBtn.addEventListener('click', () => showTestimonial(testimonialIndex + 1));
    }

    // Auto-rotate testimonials
    setInterval(() => {
        showTestimonial(testimonialIndex + 1);
    }, 7000);

    // Smooth Scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
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

    // Navbar scroll effect
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(139, 35, 50, 0.98)';
            navbar.style.boxShadow = '0 4px 20px rgba(0,0,0,0.2)';
        } else {
            navbar.style.background = 'var(--brick-red)';
            navbar.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.1)';
        }
    });

    // Animation on scroll
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

    document.querySelectorAll('.feature-card, .service-card, .about-image-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
});

// Form Validation Helper
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^(6|2)[0-9]{8}$/;
    return re.test(phone.replace(/\s/g, ''));
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#28A745' : type === 'error' ? '#DC3545' : '#17a2b8'};
        color: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
