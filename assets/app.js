import {Collapse, Tooltip} from 'bootstrap'
import Chocolat from 'chocolat'

import 'chocolat/dist/css/chocolat.css'
import './styles/app/index.scss'
import './theme-switcher.js'


// Collapse elements init
const collapseElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="collapse"]'))
collapseElementList.map(collapseTriggerEl => new Collapse(collapseTriggerEl))

// Tooltip init
const tooltipElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
tooltipElementList.map(tooltipTriggerEl => new Tooltip(tooltipTriggerEl))

// Header with auto hide
const header = document.getElementsByTagName('header')[0]

if (header) {
  const headerHeight = header.offsetHeight
  document.body.style.paddingTop = `${headerHeight}px`

  let lastScrollTop = 0
  window.addEventListener('scroll', function () {
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
}

// Share button
const shareButtonClipboard = document.getElementById('share-button-clipboard')
const shareButtonClipboardLabel = document.getElementById('share-button-clipboard-label')

if (shareButtonClipboard) {
  shareButtonClipboard.addEventListener('click', () => {
    shareButtonClipboardLabel.innerText = 'Link skopiowany!'

    setTimeout(() => {
      shareButtonClipboardLabel.innerText = 'Skopiuj link'
    }, 2000)

    navigator.clipboard.writeText(window.location.href)
  })
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
