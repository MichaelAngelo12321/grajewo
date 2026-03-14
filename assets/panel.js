import '@hotwired/turbo'
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
import 'tinymce/skins/ui/oxide/skin'
import 'tinymce/themes/silver'
import './styles/panel/index.scss'
import './theme-switcher.js'
import './file-validator.js'

const initPanel = () => {
    // Content editor
    tinymce.remove() // Ensure cleanup before re-init
    tinymce.init({
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

document.addEventListener('turbo:load', initPanel)
document.addEventListener('turbo:render', initPanel)
document.addEventListener('turbo:before-cache', () => {
    tinymce.remove()
})

// Fallback for initial load
if (document.readyState !== 'loading') {
    initPanel();
} else {
    document.addEventListener('DOMContentLoaded', initPanel);
}

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
