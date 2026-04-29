function initThemeToggle() {
    const toggle = document.getElementById('themeToggle');

    function applyTheme(theme) {
        const isDark = theme === 'dark';

        document.documentElement.setAttribute('data-theme', theme);
        document.body.setAttribute('data-theme', theme);
        document.body.classList.toggle('dark-theme', isDark);
        if (toggle) {
            toggle.classList.toggle('active', isDark);
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        }
        localStorage.setItem('theme', theme);
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
    }

    const savedTheme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light';
    applyTheme(savedTheme);

    if (!toggle || toggle.dataset.initialized === 'true') return;

    toggle.dataset.initialized = 'true';
    toggle.setAttribute('role', 'button');
    toggle.setAttribute('tabindex', '0');

    toggle.addEventListener('click', toggleTheme);
    toggle.addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            toggleTheme();
        }
    });
}

document.addEventListener('DOMContentLoaded', initThemeToggle);
