import {Toast} from 'bootstrap'
import Sortable from 'sortablejs'
import tinymce from 'tinymce'
import 'tinymce/icons/default'
import 'tinymce/models/dom/model.min'
import 'tinymce/plugins/link'
import 'tinymce/plugins/lists'
import 'tinymce/plugins/table'
import 'tinymce/skins/ui/oxide/skin'
import 'tinymce/themes/silver'
import './styles/panel/index.scss'
import './theme-switcher.js'
import './file-validator.js'

// Content editor
const editor = tinymce.init({
  content_css: false,
  height: 500,
  menubar: false,
  plugins: ['link', 'lists', 'table'],
  promotion: false,
  selector: '.content-editor',
  table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
  toolbar: 'styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | lists | forecolor backcolor | link table',
})

// Toasts
const toastsList = document.querySelectorAll('.toast')
const toasts = [...toastsList].map(element => new Toast(element).show())

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
    const message = event.target.dataset['confirm'] || 'Potwierdź operację, którą chcesz wykonać'

    if (!confirm(message)) {
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
