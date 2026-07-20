// theme mode switcher
const setTheme = (isDarkMode) => {
    const themeLogos = document.querySelectorAll('[data-light-src][data-dark-src]')
    const themeSwitcher = document.querySelector('#theme-switcher')
    const themeSwitcherMoon = document.querySelector('#theme-switcher-moon')
    const themeSwitcherSun = document.querySelector('#theme-switcher-sun')

    themeLogos.forEach((logo) => {
        const src = isDarkMode ? logo.dataset.darkSrc : logo.dataset.lightSrc
        if (src) logo.src = src
    })

    if (isDarkMode) {
        if (themeSwitcher) themeSwitcher.checked = true
        if (themeSwitcherMoon) themeSwitcherMoon.classList.remove('d-none')
        if (themeSwitcherSun) themeSwitcherSun.classList.add('d-none')
        document.documentElement.setAttribute('data-bs-theme', 'dark')
    } else {
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

document.addEventListener('DOMContentLoaded', initThemeSwitcher);
