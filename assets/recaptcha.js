document.addEventListener('DOMContentLoaded', () => {
    const recaptchaInputs = document.querySelectorAll('[data-recaptcha-site-key-value]');
    
    recaptchaInputs.forEach(input => {
        const siteKey = input.dataset.recaptchaSiteKeyValue;
        const actionName = input.dataset.recaptchaActionNameValue;
        const host = input.dataset.recaptchaHostValue || 'www.google.com';
        const locale = input.dataset.recaptchaLocaleValue || '';
        const form = input.closest('form');
        let refreshInterval = null;

        const loadRecaptcha = () => {
            return new Promise((resolve) => {
                if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.execute !== 'undefined') {
                    resolve();
                    return;
                }

                const scriptSrc = `https://${host}/recaptcha/api.js?render=${siteKey}${locale ? '&hl=' + locale : ''}`;
                let script = document.querySelector(`script[src*="recaptcha/api.js"]`);

                if (!script) {
                    script = document.createElement('script');
                    script.src = scriptSrc;
                    script.async = true;
                    script.defer = true;
                    document.head.appendChild(script);
                }

                script.addEventListener('load', () => {
                    resolve();
                });

                const checkInterval = setInterval(() => {
                    if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.execute !== 'undefined') {
                        clearInterval(checkInterval);
                        resolve();
                    }
                }, 100);
                
                setTimeout(() => clearInterval(checkInterval), 5000);
            });
        };

        const executeRecaptcha = async () => {
            await loadRecaptcha();
            
            return new Promise((resolve) => {
                grecaptcha.ready(() => {
                    grecaptcha.execute(siteKey, { action: actionName }).then((token) => {
                        input.value = token;
                        resolve(token);
                    });
                });
            });
        };

        executeRecaptcha();
        
        // Odświeżanie tokenu co 100 sekund
        refreshInterval = setInterval(() => {
            executeRecaptcha();
        }, 100000);

        if (form) {
            form.addEventListener('submit', (e) => {
                if (!input.value) {
                    e.preventDefault();
                    executeRecaptcha().then(() => {
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    });
                }
            });
        }
    });
});
