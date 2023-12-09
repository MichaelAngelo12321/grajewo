import './styles/panel/index.scss'
import './theme-switcher.js'

// List forms
const forms = document.getElementsByClassName('list-form')

for (let form of forms) {
  for (let element of form.elements) {
    const isResetFilter = element.classList.contains('reset-filter')

    element.addEventListener(isResetFilter ? 'click' : 'change', () => {
      if (isResetFilter) {
        const elements = form.querySelectorAll('input, select, textarea')

        for (let element of elements) {
          element.disabled = true
        }

        form.submit()
      } else {
        form.submit()
      }
    })
  }
}

// Confirm elements
const confirmElements = document.getElementsByClassName('confirm')

for (let element of confirmElements) {
  element.addEventListener('click', (event) => {
    if (!confirm('Potwierdź operację, którą chcesz wykonać')) {
      event.preventDefault()
    }
  })
}

