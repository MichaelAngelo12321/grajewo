import './styles/panel/index.scss'
import './theme-switcher.js'

// List forms
const forms = document.getElementsByClassName('list-form')

for (let form of forms) {
  for (let element of form.elements) {
    const resetFilter = element.classList.contains('reset-filter');
    element.addEventListener(resetFilter ? 'click' : 'change', () => {
      if (resetFilter) {
        for (let element of form.elements) {
          switch (element.type) {
            case 'radio':
            case 'checkbox':
              element.checked = false;
              break;
            case 'select-one':
              element.selectedIndex = -1;
              break;
            case 'text':
            case 'number':
              element.value = 0;
              break;
          }
        }
      } else {
        form.submit();
      }
    });
  }
}


