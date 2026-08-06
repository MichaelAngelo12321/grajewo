// cookie consent banner + gated Google Analytics loading
const CONSENT_KEY = 'cookieConsent' // 'accepted' | 'rejected'

const loadGoogleAnalytics = () => {
    const gaId = window.__GA4_ID__

    if (!gaId || window.__gaLoaded__) {
        return
    }
    window.__gaLoaded__ = true

    window.dataLayer = window.dataLayer || []
    window.gtag = function () {
        window.dataLayer.push(arguments)
    }
    window.gtag('js', new Date())
    window.gtag('config', gaId)

    const script = document.createElement('script')
    script.async = true
    script.src = `https://www.googletagmanager.com/gtag/js?id=${gaId}`
    document.head.appendChild(script)
}

const hideBanner = () => {
    const banner = document.querySelector('#cookie-consent')
    if (banner) banner.classList.add('d-none')
}

const initCookieConsent = () => {
    const banner = document.querySelector('#cookie-consent')
    if (!banner) {
        return
    }

    const consent = localStorage.getItem(CONSENT_KEY)

    if (consent === 'accepted') {
        loadGoogleAnalytics()
        return
    }

    if (consent === 'rejected') {
        return
    }

    banner.classList.remove('d-none')
}

document.addEventListener('click', (e) => {
    if (e.target.closest('#cookie-consent-accept')) {
        localStorage.setItem(CONSENT_KEY, 'accepted')
        loadGoogleAnalytics()
        hideBanner()
    }

    if (e.target.closest('#cookie-consent-reject')) {
        localStorage.setItem(CONSENT_KEY, 'rejected')
        hideBanner()
    }
})

document.addEventListener('DOMContentLoaded', initCookieConsent)
