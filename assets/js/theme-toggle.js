function initThemeToggle() {
    const toggle = document.getElementById('themeToggle');
    if (!toggle || toggle.dataset.initialized === 'true') return;

    toggle.dataset.initialized = 'true';

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
    }

    toggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-theme');
        localStorage.setItem(
            'theme',
            document.body.classList.contains('dark-theme') ? 'dark' : 'light'
        );
    });
}

document.addEventListener('DOMContentLoaded', initThemeToggle);
