// Mobile Menu + Smooth Scroll + Basic Form Checks + Image Preview + Auto-hide Alerts
document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.navbar-menu');
    const overlay = document.querySelector('.mobile-overlay');

    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            navMenu.classList.toggle('active');
            if (overlay) overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            navMenu.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
});

// Smooth Scroll
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

// Form Validation Enhancement
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function (e) {
        const requiredInputs = form.querySelectorAll('[required]');
        let isValid = true;

        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('error');
            } else {
                input.classList.remove('error');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
});



// Auto-hide Alerts
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s';
        setTimeout(() => alert.remove(), 500);
    }, 5000);
});


// Loading overlay on form submit
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function () {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.classList.add('active');
    });
});


// Real-time email validation + password strength + error helpers
document.querySelectorAll('input[type="email"]').forEach(input => {
    input.addEventListener('blur', function () {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value && !emailRegex.test(this.value)) {
            this.style.borderColor = 'var(--danger-color)';
            showError(this, 'Please enter a valid email address');
        } else {
            this.style.borderColor = '';
            hideError(this);
        }
    });
});

// Real-time Password Strength
document.querySelectorAll('input[type="password"]').forEach(input => {
    if (input.name === 'password') {
        input.addEventListener('input', function () {
            const strength = checkPasswordStrength(this.value);
            const indicator = this.parentElement.querySelector('.password-strength');
            if (indicator) {
                indicator.className = 'password-strength ' + strength;
                indicator.textContent = 'Password Strength: ' + strength.toUpperCase();
            }
        });
    }
});

function checkPasswordStrength(password) {
    if (password.length < 8) return 'weak';
    let strength = 0;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    if (strength < 2) return 'weak';
    if (strength < 3) return 'medium';
    return 'strong';
}

function showError(input, message) {
    let error = input.parentElement.querySelector('.error-message');
    if (!error) {
        error = document.createElement('div');
        error.className = 'error-message';
        input.parentElement.appendChild(error);
    }
    error.textContent = message;
}

function hideError(input) {
    const error = input.parentElement.querySelector('.error-message');
    if (error) error.remove();
}
