document.addEventListener('click', function(e) {
    const link = e.target.closest('a[data-method="post"]');
    
    if (link) {
        // Prevent default navigation
        e.preventDefault();
        
        // If it's a confirmation link, SweetAlert might have intercepted it,
        // but if we are here, we might need to check if it has js-confirm
        // Our SweetAlert logic sets data-confirmed="true" when confirmed.
        if (link.classList.contains('js-confirm') && link.dataset.confirmed !== 'true') {
            // SweetAlert will handle it, just return
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = link.href;
        
        // Hide the form
        form.style.display = 'none';
        
        document.body.appendChild(form);
        form.submit();
    }
});