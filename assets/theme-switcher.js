// theme mode switcher
const logoTop = document.querySelector('#logo-top')
const themeSwitcher = document.querySelector('#theme-switcher')
const themeSwitcherMoon = document.querySelector('#theme-switcher-moon')
const themeSwitcherSun = document.querySelector('#theme-switcher-sun')
const preferDarkMode = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : {matches: false}

function setTheme(isDarkMode) {
  if (isDarkMode) {
    logoTop.src = logoTop.src.replace('dark', 'light')
    themeSwitcher.checked = true
    themeSwitcherMoon.classList.remove('d-none')
    themeSwitcherSun.classList.add('d-none')
    document.documentElement.setAttribute('data-bs-theme', 'dark')
  } else {
    logoTop.src = logoTop.src.replace('light', 'dark')
    themeSwitcherMoon.classList.add('d-none')
    themeSwitcherSun.classList.remove('d-none')
    document.documentElement.setAttribute('data-bs-theme', 'light')
  }
}

const storedThemePreference = localStorage.getItem('themeMode')

if (storedThemePreference) {
  setTheme(storedThemePreference === 'dark')
} else {
  setTheme(preferDarkMode.matches)
}

themeSwitcher.addEventListener('click', (e) => {
  localStorage.setItem('themeMode', e.target.checked ? 'dark' : 'light')
  setTheme(e.target.checked)
})
