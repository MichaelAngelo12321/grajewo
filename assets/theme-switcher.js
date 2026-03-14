// theme mode switcher
const setTheme = (isDarkMode) => {
    const logoTop = document.querySelector('#logo-top')
    const themeSwitcher = document.querySelector('#theme-switcher')
    const themeSwitcherMoon = document.querySelector('#theme-switcher-moon')
    const themeSwitcherSun = document.querySelector('#theme-switcher-sun')

    if (isDarkMode) {
        if (logoTop && logoTop.src.includes('dark')) logoTop.src = logoTop.src.replace('dark', 'light')
        if (themeSwitcher) themeSwitcher.checked = true
        if (themeSwitcherMoon) themeSwitcherMoon.classList.remove('d-none')
        if (themeSwitcherSun) themeSwitcherSun.classList.add('d-none')
        document.documentElement.setAttribute('data-bs-theme', 'dark')
    } else {
        if (logoTop && logoTop.src.includes('light')) logoTop.src = logoTop.src.replace('light', 'dark')
        if (themeSwitcher) themeSwitcher.checked = false
        if (themeSwitcherMoon) themeSwitcherMoon.classList.add('d-none')
        if (themeSwitcherSun) themeSwitcherSun.classList.remove('d-none')
        document.documentElement.setAttribute('data-bs-theme', 'light')
    }
}

const initThemeSwitcher = () => {
    const themeSwitcher = document.querySelector('#theme-switcher')
    if (!themeSwitcher) {
        return
    }

    const preferDarkMode = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : {matches: false}
    const storedThemePreference = localStorage.getItem('themeMode')

    if (storedThemePreference) {
        setTheme(storedThemePreference === 'dark')
    } else {
        setTheme(preferDarkMode.matches)
    }
}

// Event Delegation for Toggle
document.addEventListener('change', (e) => {
    if (e.target && e.target.id === 'theme-switcher') {
        localStorage.setItem('themeMode', e.target.checked ? 'dark' : 'light')
        setTheme(e.target.checked)
    }
})

document.addEventListener('turbo:load', initThemeSwitcher)

// Fallback for initial load if turbo is not yet active or disabled
if (document.readyState !== 'loading') {
    initThemeSwitcher();
} else {
    document.addEventListener('DOMContentLoaded', initThemeSwitcher);
}
