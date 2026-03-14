import './bootstrap.js';
import * as bootstrap from 'bootstrap'
window.bootstrap = bootstrap

import Chocolat from 'chocolat'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

import 'chocolat/dist/css/chocolat.css'
import './styles/app/index.scss'
import './ad-rotation.js'
import './theme-switcher.js'
import './file-validator.js'

const { Collapse, Tooltip } = bootstrap

// Global scroll handler (attached once)
let lastScrollTop = 0
window.addEventListener('scroll', function () {
  const header = document.querySelector('header')
  if (!header) return

  const scroll_top = window.scrollY
  const scrolled_up = 'scrolled-up'
  const scrolled_down = 'scrolled-down'

  if (scroll_top < lastScrollTop) {
    header.classList.remove(scrolled_down)
    header.classList.add(scrolled_up)
  } else {
    header.classList.remove(scrolled_up)
    header.classList.add(scrolled_down)
  }

  lastScrollTop = scroll_top
})

// Global SweetAlert2 handlers for confirmations
document.addEventListener('click', function(e) {
  const confirmElement = e.target.closest('.js-confirm');
  
  if (confirmElement && confirmElement.dataset.confirmed !== 'true') {
      e.preventDefault();
      e.stopImmediatePropagation();
      
      const message = confirmElement.dataset['confirm'] || 'Czy na pewno chcesz wykonać tę operację?';
      
      Swal.fire({
          title: 'Potwierdzenie',
          text: message,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'Tak, usuń',
          cancelButtonText: 'Anuluj',
          reverseButtons: true
      }).then((result) => {
          if (result.isConfirmed) {
              confirmElement.dataset.confirmed = 'true';
              confirmElement.click();
          }
      });
  }
}, true);

const initApp = () => {
  // Collapse elements init
  const collapseElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="collapse"]'))
  collapseElementList.map(collapseTriggerEl => new Collapse(collapseTriggerEl))

  // Tooltip init
  const tooltipElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipElementList.map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl))

  // Header height adjustment
  const header = document.querySelector('header')
  if (header) {
    const headerHeight = header.offsetHeight
    document.body.style.paddingTop = `${headerHeight}px`
  }

  // Share button
  const shareButtonClipboard = document.getElementById('share-button-clipboard')
  const shareButtonClipboardLabel = document.getElementById('share-button-clipboard-label')

  if (shareButtonClipboard && shareButtonClipboardLabel) {
    // Remove existing listener to avoid duplicates if element persists (unlikely with Turbo replacement but safe)
    // Actually, creating a new function every time prevents removeEventListener unless we store the reference.
    // But since elements are replaced, we don't need to remove.
    shareButtonClipboard.addEventListener('click', () => {
      shareButtonClipboardLabel.innerText = 'Link skopiowany!'

      setTimeout(() => {
        shareButtonClipboardLabel.innerText = 'Skopiuj link'
      }, 2000)

      navigator.clipboard.writeText(window.location.href)
    })
  }

  // Promo popup
  const promoPopupEl = document.getElementById('promoPopup')
  if (promoPopupEl) {
    const myModal = new bootstrap.Modal(promoPopupEl)

    // Check if popup was shown in this session
    const sessionShown = sessionStorage.getItem('promoPopupShown')
    const forcePopup = new URLSearchParams(window.location.search).has('test_popup')

    if (!sessionShown || forcePopup) {
      myModal.show()
      sessionStorage.setItem('promoPopupShown', 'true')
    }
  }

  // Lightbox
  Chocolat(document.querySelectorAll('.image-lightbox'), {
    className: 'gallery-is-open',
    linkImages: false,
    pagination: () => '',
  })

  // Lightbox with gallery
  Chocolat(document.querySelectorAll('.gallery-lightbox'), {
    className: 'gallery-is-open',
    loop: true,
  })
}

// Initialize on Turbo load (covers initial load and navigation)
document.addEventListener('turbo:load', initApp)
// Fallback for initial load if turbo is not yet active or disabled
if (document.readyState !== 'loading') {
    initApp();
} else {
    document.addEventListener('DOMContentLoaded', initApp);
}
