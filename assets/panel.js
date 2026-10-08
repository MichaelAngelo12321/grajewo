import {Toast} from 'bootstrap'
import Sortable from 'sortablejs'
import tinymce from 'tinymce'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'
import 'tinymce/icons/default'
import 'tinymce/models/dom/model.min'
import 'tinymce/plugins/link'
import 'tinymce/plugins/lists'
import 'tinymce/plugins/table'
import 'tinymce/plugins/image'
import 'tinymce/skins/ui/oxide/skin'
import 'tinymce/themes/silver'
import './styles/panel/index.scss'
import './theme-switcher.js'
import './file-validator.js'
import './recaptcha.js'
import './post-link.js'

const initPanel = () => {
    // Content editor
    tinymce.remove() // Ensure cleanup before re-init
    tinymce.init({
        content_css: false,
        entity_encoding: 'raw', // store Polish characters as UTF-8, not as &oacute; entities
        height: 500,
        menubar: false,
        plugins: ['link', 'lists', 'table', 'image'],
        promotion: false,
        selector: '.content-editor',
        table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
        toolbar: 'styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | lists | link image table',
    })

    // Toasts
    const toastsList = document.querySelectorAll('.toast')
    const toasts = [...toastsList].map(element => new Toast(element).show())

    // List forms
    const forms = document.getElementsByClassName('list-form')

    for (let form of forms) {
        for (let element of form.elements) {
            const isResetFilter = element.classList.contains('reset-filter')

            // Avoid multiple listeners if re-initialized (though elements are replaced)
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

    // Searchable selects - text input above the select that filters its options
    const normalize = (text) => text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ł/g, 'l')

    for (let select of document.querySelectorAll('select[data-searchable-select]')) {
        const search = document.createElement('input')
        search.type = 'search'
        search.className = 'form-control mb-2'
        search.placeholder = select.dataset.searchableSelect || 'Szukaj...'
        select.before(search)

        const options = [...select.options]

        search.addEventListener('input', () => {
            const query = normalize(search.value.trim())
            for (let option of options) {
                const matches = option.value === '' || option.selected || normalize(option.text).includes(query)
                option.hidden = !matches
            }

            select.size = query ? Math.min(10, Math.max(2, options.filter(o => !o.hidden).length)) : 0
        })

        select.addEventListener('change', () => {
            if (search.value) {
                search.value = ''
                search.dispatchEvent(new Event('input'))
            }
        })
    }

    // Confirm elements (removed from initPanel, handled globally below)

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
                        Swal.fire({
                            icon: 'error',
                            title: 'Błąd',
                            text: 'Wystąpił błąd podczas zapisu kolejności elementów',
                        })
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Sukces',
                            text: 'Kolejność elementów została zapisana',
                            timer: 2000,
                            showConfirmButton: false
                        })
                    }
                }).catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Błąd',
                        text: 'Wystąpił błąd podczas zapisu kolejności elementów',
                    })
                })
            },
        })
    }
}

document.addEventListener('DOMContentLoaded', initPanel)

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
            confirmButtonText: 'Tak, wykonaj',
            cancelButtonText: 'Anuluj',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                confirmElement.dataset.confirmed = 'true';
                confirmElement.click(); // Re-trigger the click
                
                // Reset the flag after a short delay in case of page not reloading
                setTimeout(() => {
                    delete confirmElement.dataset.confirmed;
                }, 1000);
            }
        });
    }
}, true);

document.addEventListener('submit', function(e) {
    const form = e.target.closest('.js-form-confirm');
    
    if (form && form.dataset.confirmed !== 'true') {
        e.preventDefault();
        
        const message = form.dataset['confirm'] || 'Czy na pewno chcesz wykonać tę operację?';
        
        Swal.fire({
            title: 'Potwierdzenie',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Tak, wykonaj',
            cancelButtonText: 'Anuluj',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.dataset.confirmed = 'true';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
                
                // Reset the flag after a short delay in case of page not reloading
                setTimeout(() => {
                    delete form.dataset.confirmed;
                }, 1000);
            }
        });
    }
});
