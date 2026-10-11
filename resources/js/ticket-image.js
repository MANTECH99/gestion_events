import { toPng } from 'html-to-image';
import { toast } from './utils';

/**
 * Enregistre le billet affiché sous forme d'image (PNG), fidèle à l'écran.
 *
 * On capture directement l'élément DOM du billet : le navigateur le rend
 * lui-même (dégradé, icônes, QR code, police), donc le résultat est identique
 * à ce que l'utilisateur voit. On dessine ensuite ce rendu sur un fond uni
 * avec une marge identique sur les quatre côtés. Sur mobile, le bouton
 * « Enregistrer » ouvre le partage natif pour ranger l'image dans la galerie.
 */
function ticketImage() {
    return {
        open: false,
        busy: false,
        dataUrl: '',
        filename: 'billet.png',
        canShare: false,

        async capture(el, code) {
            if (!el || this.busy) return;

            this.busy = true;
            this.filename = `billet-${code || 'billeterie-express'}.png`;

            try {
                await this.ready();

                const ratio = Math.min(3, Math.max(2, window.devicePixelRatio || 1));

                // Rendu du billet, fond transparent (les coins arrondis restent nets).
                const raw = await toPng(el, { pixelRatio: ratio, cacheBust: true });

                // Recadrage : marge identique sur les 4 côtés + fond uni.
                this.dataUrl = await this.withBackground(raw, 28 * ratio, '#eef1f6');

                this.canShare = typeof navigator !== 'undefined' && !!navigator.canShare;
                this.open = true;
            } catch (e) {
                toast("Impossible de générer l'image. Réessayez.", 'error');
            } finally {
                this.busy = false;
            }
        },

        /**
         * Version organisateur : récupère le HTML des billets, le rend hors
         * écran, puis enregistre chaque billet dans une image distincte.
         * Un seul clic = un fichier par billet (2 billets = 2 images).
         */
        async captureFromUrl(url, code) {
            if (this.busy) return;

            this.busy = true;

            let holder;

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                });

                if (!response.ok) throw new Error('HTTP '+response.status);

                holder = document.createElement('div');
                holder.style.cssText = 'position:fixed;left:-10000px;top:0;width:24rem;';
                holder.innerHTML = await response.text();
                document.body.appendChild(holder);

                await this.ready();

                const ratio = Math.min(3, Math.max(2, window.devicePixelRatio || 1));

                const cards = Array.from(holder.querySelectorAll('.ticket-card-export'));
                const targets = cards.length ? cards : [holder];

                // Le conteneur est placé hors écran : on force un flux normal
                // sur le clone pour que le rendu soit bien dessiné.
                const style = { position: 'static', left: '0', top: '0', margin: '0' };

                for (let i = 0; i < targets.length; i++) {
                    const target = targets[i];
                    const raw = await toPng(target, { pixelRatio: ratio, cacheBust: true, style });
                    const image = await this.withBackground(raw, 28 * ratio, '#eef1f6');

                    const suffix = (target.dataset && target.dataset.code)
                        || (targets.length > 1 ? String(i + 1) : (code || 'reservation'));

                    this.downloadData(image, `billet-${suffix}.png`);

                    if (i < targets.length - 1) {
                        await new Promise((resolve) => setTimeout(resolve, 400));
                    }
                }

                toast(targets.length > 1 ? 'Billets enregistrés' : 'Image enregistrée', 'success');
            } catch (e) {
                toast("Impossible de générer l'image. Réessayez.", 'error');
            } finally {
                if (holder) holder.remove();
                this.busy = false;
            }
        },

        /** Dessine l'image sur un canevas avec une marge uniforme. */
        withBackground(src, margin, color) {
            return new Promise((resolve, reject) => {
                const img = new Image();

                img.onload = () => {
                    try {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.width + margin * 2;
                        canvas.height = img.height + margin * 2;

                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = color;
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                        ctx.drawImage(img, margin, margin);

                        resolve(canvas.toDataURL('image/png'));
                    } catch (e) {
                        reject(e);
                    }
                };

                img.onerror = reject;
                img.src = src;
            });
        },

        /** Attend le chargement des polices pour un rendu net. */
        async ready() {
            if (document.fonts && document.fonts.ready) {
                try {
                    await document.fonts.ready;
                } catch (e) {
                    /* les polices sont un bonus, on continue */
                }
            }
        },

        download() {
            if (!this.dataUrl) return;

            this.downloadData(this.dataUrl, this.filename);
            toast('Image enregistrée', 'success');
        },

        /** Déclenche l'enregistrement d'une image (data URL) sous le nom donné. */
        downloadData(dataUrl, filename) {
            const link = document.createElement('a');
            link.href = dataUrl;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
        },

        async share() {
            if (!this.dataUrl) return;

            try {
                const blob = await (await fetch(this.dataUrl)).blob();
                const file = new File([blob], this.filename, { type: 'image/png' });

                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({ files: [file], title: 'Billet' });
                    return;
                }
            } catch (e) {
                /* partage annulé ou indisponible : on retombe sur le téléchargement */
            }

            this.download();
        },

        close() {
            this.open = false;
            this.dataUrl = '';
        },
    };
}

window.ticketImage = ticketImage;
