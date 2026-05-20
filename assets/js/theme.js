/**
 * Theme Manager for Liquor Store RW
 */

const themeToggle = document.getElementById('theme-toggle');
const currentTheme = localStorage.getItem('theme');

// 1. Check for saved user preference, if none, check system preference
if (currentTheme) {
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateToggleIcon(currentTheme);
} else {
    // Detect OS Preference
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const defaultTheme = prefersDark ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', defaultTheme);
    updateToggleIcon(defaultTheme);
}

// 2. Function to switch theme
function switchTheme() {
    let theme = document.documentElement.getAttribute('data-theme');
    let newTheme = (theme === 'dark') ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme); // Save preference
    updateToggleIcon(newTheme);
}

// 3. Update the icon (Sun/Moon)
function updateToggleIcon(theme) {
    const icon = document.querySelector('#theme-toggle i');
    if(!icon) return;
    if (theme === 'dark') {
        icon.classList.replace('fa-moon', 'fa-sun');
        icon.style.color = '#ffc107';
    } else {
        icon.classList.replace('fa-sun', 'fa-moon');
        icon.style.color = '#555';
    }
}

// Listen for system changes while user is on the site
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    if (!localStorage.getItem('theme')) { // Only auto-change if user hasn't set it manually
        const newTheme = e.matches ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', newTheme);
        updateToggleIcon(newTheme);
    }
});