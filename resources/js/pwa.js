/**
 * Installation de la PWA : manifeste + service worker + invite d'installation.
 */
function pwa() {
    return {
        canInstall: false,
        deferredPrompt: null,
        installed: window.matchMedia('(display-mode: standalone)').matches,

        init() {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch((e) => {
                        console.warn('Service worker non enregistré.', e);
                    });
                });
            }

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.canInstall = true;
            });

            window.addEventListener('appinstalled', () => {
                this.canInstall = false;
                this.installed = true;
            });
        },

        async install() {
            if (!this.deferredPrompt) return;

            this.deferredPrompt.prompt();
            await this.deferredPrompt.userChoice;
            this.deferredPrompt = null;
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