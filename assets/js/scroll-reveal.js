document.addEventListener('DOMContentLoaded', () => {
    const reveal = () => {
        const els = document.querySelectorAll('.scroll-reveal');
        const vh = window.innerHeight || document.documentElement.clientHeight;

        els.forEach(el => {
            const rect = el.getBoundingClientRect();
            const visible = rect.top < vh - 60;
            if (visible) el.classList.add('active');
        });
    };

    reveal();
    window.addEventListener('scroll', reveal, { passive: true });
    window.addEventListener('resize', reveal);
});
