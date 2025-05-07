document.addEventListener('DOMContentLoaded', function () {
    // Register Service Worker
    console.log(location.protocol, 'serviceWorker', 'serviceWorker' in navigator);
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/assets/pwa-sw.js')
                .then(function (register) {
                    console.log('PWA service worker ready');
                    register.update();
                })
                .catch(function (error) {
                    console.log('Register failed! Error:' + error);
                });

            // Check user internet status (online/offline)
            function updateOnlineStatus(event) {
                if (!navigator.onLine) {
                    alert('Internet access is not possible!')
                }
            }

            window.addEventListener('online', updateOnlineStatus);
            window.addEventListener('offline', updateOnlineStatus);
        });
    }

    let deferredPrompt;function showInstallPromotion(){document.getElementById("install-prompt").style.display="block"}window.addEventListener("load",(()=>{if(window.matchMedia("(display-mode: standalone)").matches){document.getElementById("install-prompt").style.display="none"}})),window.addEventListener("beforeinstallprompt",(e=>{e.preventDefault(),deferredPrompt=e,showInstallPromotion();document.getElementById("install-button").addEventListener("click",(()=>{deferredPrompt.prompt(),deferredPrompt.userChoice.then((e=>{deferredPrompt=null}))}))})),window.addEventListener("appinstalled",(()=>{document.getElementById("install-prompt").style.display="none"}));
});
