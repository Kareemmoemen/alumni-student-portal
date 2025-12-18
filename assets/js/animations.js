// FILE: alumni-portal/assets/js/animations.js
document.addEventListener('DOMContentLoaded', function () {
    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // =============================================
    // SCROLL REVEAL
    // =============================================
    const revealElements = document.querySelectorAll('.scroll-reveal');

    const revealOnScroll = () => {
        const windowHeight = window.innerHeight;

        revealElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const revealPoint = 150;

            if (elementTop < windowHeight - revealPoint) {
                element.classList.add('active');
            }
        });
    };

    window.addEventListener('scroll', debounce(revealOnScroll, 50), { passive: true });
    revealOnScroll(); // Initial check

    // =============================================
    // STAGGER ANIMATIONS
    // =============================================
    const staggerElements = document.querySelectorAll('.stagger-animation');

    staggerElements.forEach(parent => {
        const children = parent.children;

        Array.from(children).forEach((child, index) => {
            child.style.animationDelay = `${index * 0.1}s`;
            child.classList.add('animate-fade-in-up');
        });
    });

    // =============================================
    // COUNTER ANIMATION
    // =============================================
    const counters = document.querySelectorAll('.counter');

    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target') || '0', 10);
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // ~60 FPS
        let current = 0;

        const updateCounter = () => {
            current += increment;

            if (current < target) {
                counter.textContent = String(Math.floor(current));
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = String(target);
            }
        };

        // Start when visible
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    updateCounter();
                    observer.unobserve(entry.target);
                }
            });
        });

        observer.observe(counter);
    });

    // =============================================
    // TOAST NOTIFICATIONS
    // =============================================
    window.showToast = function (message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        toast.innerHTML = `
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 20px;">
          ${type === 'success' ? '✓' : type === 'error' ? '✗' : '⚠'}
        </span>
        <span>${message}</span>
      </div>
    `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('closing');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // =============================================
    // CARD TILT EFFECT
    // =============================================
    const tiltCards = document.querySelectorAll('.card-tilt-3d');

    tiltCards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;

            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
        });
    });

    // =============================================
    // PARALLAX SCROLLING
    // =============================================
    const parallaxElements = document.querySelectorAll('.parallax');

    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;

        parallaxElements.forEach(element => {
            const speed = parseFloat(element.getAttribute('data-speed') || '0.5');
            const yPos = -(scrolled * speed);
            element.style.transform = `translateY(${yPos}px)`;
        });
    }, { passive: true });

    // =============================================
    // SMOOTH SCROLL TO ANCHOR
    // =============================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');

            if (href && href !== '#' && href !== '#!') {
                const target = document.querySelector(href);

                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // =============================================
    // TYPING EFFECT
    // =============================================
    window.typeWriter = function (element, text, speed = 50) {
        let i = 0;
        element.textContent = '';

        function type() {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(type, speed);
            }
        }

        type();
    };

    // =============================================
    // CONFETTI EFFECT (for celebrations)
    // =============================================
    window.createConfetti = function () {
        const colors = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#43e97b'];
        const confettiCount = 50;

        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');

            confetti.style.position = 'fixed';
            confetti.style.width = '10px';
            confetti.style.height = '10px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.left = Math.random() * window.innerWidth + 'px';
            confetti.style.top = '-10px';
            confetti.style.opacity = String(Math.random());
            confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
            confetti.style.zIndex = '9999';
            confetti.style.pointerEvents = 'none';

            document.body.appendChild(confetti);

            const animation = confetti.animate(
                [
                    { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(${window.innerHeight + 10}px) rotate(${Math.random() * 720}deg)`, opacity: 0 }
                ],
                {
                    duration: Math.random() * 3000 + 2000,
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
                }
            );

            animation.onfinish = () => confetti.remove();
        }
    };

    // =============================================
    // RIPPLE EFFECT ON CLICK
    // =============================================
    document.querySelectorAll('.ripple-effect').forEach(element => {
        element.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);

            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.background = 'rgba(255, 255, 255, 0.5)';
            ripple.style.transform = 'scale(0)';
            ripple.style.animation = 'ripple 0.6s ease-out';
            ripple.style.pointerEvents = 'none';

            this.style.position = 'relative';
            this.style.overflow = 'hidden';

            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
});

// Add ripple animation to CSS dynamically (as in the guide snippet)
const style = document.createElement('style');
style.textContent = `
@keyframes ripple {
  to {
    transform: scale(4);
    opacity: 0;
  }
}
`;
document.head.appendChild(style);

/* =============================================
PART 3 — GLOBAL FUNCTIONS & OPTIMIZATIONS
============================================= */

// Morph Button Logic
function morphSubmit(btn) {
    btn.classList.add('morphed');
    setTimeout(() => {
        // Form submit logic here
        if (window.showToast) showToast('Success!', 'success');
    }, 500);
}

// Haptics & Performance (run when DOM is ready)
document.addEventListener('DOMContentLoaded', () => {
    // Haptic Feedback
    function vibrate(duration = 10) {
        if ('vibrate' in navigator) navigator.vibrate(duration);
    }
    document.querySelectorAll('.btn, button').forEach(btn => {
        btn.addEventListener('click', () => vibrate(10));
    });
    document.querySelectorAll('.ripple-effect').forEach(element => {
        element.addEventListener('click', () => vibrate(15));
    });

    // Performance Logging
    window.addEventListener('load', () => {
        const perfData = performance.timing;
        const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
        console.log('Page Load Time:', pageLoadTime + 'ms');
        if (pageLoadTime > 3000) {
            console.warn('Page load is slow. Consider optimization.');
        }
    });

    // Lazy Load Images
    if ('IntersectionObserver' in window) {
        document.querySelectorAll('img[data-src]').forEach(img => {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                });
            });
            observer.observe(img);
        });
    }
});
