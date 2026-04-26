let rotationInterval;

document.addEventListener('DOMContentLoaded', () => {
    if (rotationInterval) clearInterval(rotationInterval);

    const ROTATION_INTERVAL = 60000; // 60 seconds

    rotationInterval = setInterval(() => {
        const slots = document.querySelectorAll('.promo-slot-wrapper[data-slot-name]');

        slots.forEach(slotWrapper => {
            const slotName = slotWrapper.dataset.slotName;
            if (!slotName) return;

            // Avoid rotating POPUP slot as it's meant to be shown once
            if (slotName === 'WYSKAKUJACE_OKIENKO_POPUP') return;

            fetch(`/promo/render/${slotName}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(html => {
                    if (html.trim()) {
                        // Preload images to prevent layout shifts
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        const images = tempDiv.querySelectorAll('img');
                        const promises = Array.from(images).map(img => {
                            return new Promise((resolve) => {
                                const newImg = new Image();
                                newImg.src = img.src;
                                newImg.onload = resolve;
                                newImg.onerror = resolve;
                            });
                        });

                        Promise.all(promises).then(() => {
                            slotWrapper.style.transition = 'opacity 0.5s ease-in-out';
                            slotWrapper.style.opacity = '0';

                            setTimeout(() => {
                                slotWrapper.innerHTML = html;
                                slotWrapper.style.opacity = '1';
                            }, 500);
                        });
                    }
                })
                .catch(error => {
                    console.debug('Error rotating ad for slot:', slotName, error);
                });
        });
    }, ROTATION_INTERVAL);
});


