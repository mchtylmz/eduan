document.addEventListener('DOMContentLoaded', function () {
    try {
        const userName = document.querySelector('meta[name="pwa-user-name"]');
        if (userName?.content) localStorage.setItem('pwa-user-name', userName.content);
        else localStorage.removeItem('pwa-user-name');
    } catch (error) {
        // Local storage can be disabled by the browser.
    }

    // Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/pwa-sw.js', { scope: '/', updateViaCache: 'none' })
                .then(async function (register) {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    await Promise.all(registrations
                        .filter(item => item.scope === new URL('/assets/', location.href).href)
                        .map(item => item.unregister()));
                    await register.update();
                })
                .catch(function (error) {
                    console.warn('PWA registration/update failed:', error);
                });

            // Check user internet status (online/offline)
            function updateOnlineStatus(event) {
                if (!navigator.onLine) {
                    const message = document.querySelector('meta[name="pwa-offline-message"]')?.content;
                    if (message) alert(message);
                }
            }

            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
        });
    }

    let deferredPrompt;
    const installPrompt = document.getElementById('install-prompt');
    const installButton = document.getElementById('install-button');
    window.addEventListener('load', () => {
        if (installPrompt && window.matchMedia('(display-mode: standalone)').matches) {
            installPrompt.style.display = 'none';
        }
    });
    window.addEventListener('beforeinstallprompt', event => {
        if (!installPrompt || !installButton) return;
        event.preventDefault();
        deferredPrompt = event;
        installPrompt.style.display = 'block';
    });
    installButton?.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        await deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        deferredPrompt = null;
    });
    window.addEventListener('appinstalled', () => {
        if (installPrompt) installPrompt.style.display = 'none';
    });

    const isInStandaloneMode = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    if (isInStandaloneMode()) {
        $('.pwa-app-install').addClass('d-none');
    } else {
        $('.pwa-app-install').removeClass('d-none').addClass('d-flex');
    }

    document.querySelectorAll('.star-rating:not(.readonly) label').forEach(star => {
        star.addEventListener('click', function() {
            this.style.transform = 'scale(1.2)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
        });
    });
});
