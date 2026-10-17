/**
 * Installation de la PWA : manifeste + service worker + invite d'installation.
 */

/** Capturé tôt, avant que Alpine n'ait chargé. */
let deferredInstallPrompt = null;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredInstallPrompt = e;
    document.dispatchEvent(new CustomEvent('pwa:caninstall'));
});

window.addEventListener('appinstalled', () => {
    deferredInstallPrompt = null;
    document.dispatchEvent(new CustomEvent('pwa:installed'));
});

function pwa() {
    return {
        canInstall: !!deferredInstallPrompt,
        deferredPrompt: deferredInstallPrompt,
        installed: window.matchMedia('(display-mode: standalone)').matches,

        init() {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch((e) => {
                        console.warn('Service worker non enregistré.', e);
                    });
                });
            }

            document.addEventListener('pwa:caninstall', () => {
                this.canInstall = true;
                this.deferredPrompt = deferredInstallPrompt;
            });

            document.addEventListener('pwa:installed', () => {
                this.canInstall = false;
                this.installed = true;
            });
        },

        async install() {
            if (!deferredInstallPrompt) return;

            deferredInstallPrompt.prompt();
            await deferredInstallPrompt.userChoice;
            deferredInstallPrompt = null;
            this.canInstall = false;
        },

        updateAvailable: false,

        async update() {
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) await registration.update();
            window.location.reload();
        },
    };
}

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('controllerchange', () => {
        /* le nouveau SW prend le relais */
    });
}

window.pwa = pwa;
window.Alpine = window.Alpine || null;