import Sortable from 'sortablejs'
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
const confirmElements = document.getElementsByClassName('js-confirm')

for (let element of confirmElements) {
  element.addEventListener('click', (event) => {
    if (!confirm('Potwierdź operację, którą chcesz wykonać')) {
      event.preventDefault()
    }
  })
}

// Sortable elements
const sortableElements = document.getElementsByClassName('js-sortable')

for (let element of sortableElements) {
  Sortable.create(element, {
    animation: 150,
    handle: '.js-drag-handler',
    onEnd: (event) => {
      if (event.newIndex === event.oldIndex) {
        return
      }

      const url = event.target.dataset.url
      const elementsOrder = {}

      for (let index = 0; index < event.to.children.length; index++) {
        let child = event.to.children[index]
        elementsOrder[child.dataset.elementId] = index
      }

      fetch(url, {
        method: 'POST',
        body: JSON.stringify({elementsOrder}),
        headers: {
          'Content-Type': 'application/json',
        },
      }).then((response) => {
        if (!response.ok) {
          alert('Wystąpił błąd podczas zapisu kolejności elementów')
        } else {
          alert('Kolejność elementów została zapisana')
        }
      }).catch(() => {
        alert('Wystąpił błąd podczas zapisu kolejności elementów')
      })
    },
  })
}
