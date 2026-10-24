import { toPng } from 'html-to-image';
import { toast } from './utils';

/**
 * Fond de la maquette : noir chaud + halos orange derrière le billet.
 * Partagé par « Enregistrer l'image » (ticketImage) et l'envoi automatique
 * WhatsApp (ticketWhatsApp) — les deux rendus restent identiques.
 */
function paintBackdrop(ctx, w, h) {
    ctx.fillStyle = '#0a0705';
    ctx.fillRect(0, 0, w, h);

    const halos = [
        {
            x: w * 0.95,
            y: h * 1.02,
            r: Math.max(w, h) * 0.5,
            stops: [
                [0, 'rgba(234,88,12,0.30)'],
                [0.5, 'rgba(234,88,12,0.10)'],
                [1, 'rgba(234,88,12,0)'],
            ],
        },
        {
            x: w * 0.05,
            y: h * 1.02,
            r: Math.max(w, h) * 0.5,
            stops: [
                [0, 'rgba(234,88,12,0.30)'],
                [0.5, 'rgba(234,88,12,0.10)'],
                [1, 'rgba(234,88,12,0)'],
            ],
        },
        {
            x: w * 0.7,
            y: -h * 0.05,
            r: Math.max(w, h) * 0.35,
            stops: [
                [0, 'rgba(249,115,22,0.12)'],
                [0.5, 'rgba(249,115,22,0.04)'],
                [1, 'rgba(249,115,22,0)'],
            ],
        },
    ];

    for (const halo of halos) {
        const grad = ctx.createRadialGradient(halo.x, halo.y, 0, halo.x, halo.y, halo.r);
        for (const [offset, color] of halo.stops) {
            grad.addColorStop(offset, color);
        }
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, w, h);
    }
}

/**
 * Attend que toutes les images de l'élément soient chargées (load ou erreur),
 * avec un garde-fou de 2,5 s. html-to-image décode les images raster au
 * moment du rendu : un lancer avant le chargement les produit transparentes.
 */
function waitForImages(root) {
    if (!root || !root.querySelectorAll) return Promise.resolve();

    return Promise.all(Array.from(root.querySelectorAll('img')).map((img) => {
        // Chargée ou cassée : on n'attend pas (l'image de repli reste visible).
        if (img.complete) return null;

        return new Promise((resolve) => {
            const done = () => resolve();
            img.addEventListener('load', done, { once: true });
            img.addEventListener('error', done, { once: true });
            setTimeout(done, 2500);
        });
    }));
}

/**
 * Capture fiable : bug connu de html-to-image — le PREMIER rendu
 * (foreignObject SVG) peut rater les images raster alors que le second
 * les inclut. On re-tant tant que le résultat grossit (les photos
 * apparaissent), plafonné à 3 essais.
 *
 * @see https://github.com/bubkoo/html-to-image/issues/488
 */
async function renderToPng(el, options) {
    let best = await toPng(el, options);

    for (let attempt = 1; attempt < 3; attempt++) {
        await new Promise((resolve) => setTimeout(resolve, 120));

        const next = await toPng(el, options);

        if (next.length > best.length) {
            best = next;
        } else {
            break;
        }
    }

    return best;
}

/**
 * Enregistre le billet affiché sous forme d'image (PNG), fidèle à l'écran.
 *
 * On capture directement l'élément DOM du billet : le navigateur le rend
 * lui-même (dégradé, icônes, QR code, police), donc le résultat est identique
 * à ce que l'utilisateur voit. On dessine ensuite ce rendu sur le fond de la
 * maquette (noir chaud + halo orange) avec une marge identique sur les quatre
 * côtés. Sur mobile, le bouton
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
                await waitForImages(el);

                const ratio = Math.min(3, Math.max(2, window.devicePixelRatio || 1));

                // Rendu du billet, fond transparent (les coins arrondis restent nets).
                const raw = await renderToPng(el, { pixelRatio: ratio, cacheBust: true });

                // Recadrage : marge identique sur les 4 côtés + fond de la maquette.
                this.dataUrl = await this.withBackground(raw, 28 * ratio);

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
                await waitForImages(holder);

                const ratio = Math.min(3, Math.max(2, window.devicePixelRatio || 1));

                const cards = Array.from(holder.querySelectorAll('.ticket-card-export'));
                const targets = cards.length ? cards : [holder];

                // Le conteneur est placé hors écran : on force un flux normal
                // sur le clone pour que le rendu soit bien dessiné.
                const style = { position: 'static', left: '0', top: '0', margin: '0' };

                for (let i = 0; i < targets.length; i++) {
                    const target = targets[i];
                    const raw = await renderToPng(target, { pixelRatio: ratio, cacheBust: true, style });
                    const image = await this.withBackground(raw, 28 * ratio);

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

        /** Dessine l'image sur un canevas avec une marge + fond de la maquette. */
        withBackground(src, margin) {
            return new Promise((resolve, reject) => {
                const img = new Image();

                img.onload = () => {
                    try {
                        const canvas = document.createElement('canvas');
                        canvas.width = img.width + margin * 2;
                        canvas.height = img.height + margin * 2;

                        const ctx = canvas.getContext('2d');
                        paintBackdrop(ctx, canvas.width, canvas.height);
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

/**
 * Envoi automatique du billet par WhatsApp après paiement.
 *
 * Le client a déjà le billet affiché : on réutilise le même rendu DOM que le
 * bouton « Enregistrer l'image » pour produire une image (fusion des cartes
 * présentes sur la page) et on l'envoie au serveur, qui la transmet à l'API
 * WhatsApp Cloud. Un seul envoi par réservation : indicateur `sent` renvoyé par
 * le serveur + garde-fou côté base de données.
 */
function ticketWhatsApp(config = {}) {
    return {
        url: config.url || '',
        sent: !!config.sent,
        selector: config.selector || '.js-ticket-card',
        running: false,

        async init() {
            if (this.sent || this.running || !this.url) return;

            this.running = true;

            try {
                await this.ready();

                const cards = Array.from(document.querySelectorAll(this.selector))
                    .filter((el) => el.offsetParent !== null || el.getClientRects().length > 0);

                if (!cards.length) return;

                const ratio = Math.min(3, Math.max(2, window.devicePixelRatio || 1));
                const parts = [];

                for (const card of cards) {
                    await waitForImages(card);
                    parts.push(await renderToPng(card, { pixelRatio: ratio, cacheBust: true }));
                }

                const image = await this.stitch(parts, ratio);
                const csrf = document.querySelector('meta[name="csrf-token"]');

                const response = await fetch(this.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf ? csrf.content : '',
                    },
                    body: JSON.stringify({ image }),
                });

                const data = await response.json().catch(() => ({}));

                if (response.ok && data.sent) {
                    toast('Votre billet vous a été envoyé sur WhatsApp', 'success');
                } else if (!response.ok) {
                    console.warn('Envoi WhatsApp du billet refusé', response.status, data);
                    toast('Envoi WhatsApp du billet impossible', 'error');
                }
            } catch (e) {
                /* envoi automatique en arrière-plan : on n'interrompt pas le
                   client, mais on laisse une trace pour diagnostiquer. */
                console.warn('Envoi automatique WhatsApp impossible', e);
            } finally {
                this.running = false;
            }
        },

        /** Empile les cartes dans une seule image (marge uniforme, fond maquette). */
        stitch(sources, ratio) {
            const loaded = sources.map((src) => new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = src;
            }));

            return Promise.all(loaded).then((images) => {
                const margin = 28 * ratio;
                const gap = 24 * ratio;
                const width = Math.max(...images.map((i) => i.width)) + margin * 2;
                const height = images.reduce((sum, i) => sum + i.height, 0) + gap * (images.length - 1) + margin * 2;

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                paintBackdrop(ctx, width, height);

                let y = margin;

                for (const img of images) {
                    ctx.drawImage(img, (width - img.width) / 2, y);
                    y += img.height + gap;
                }

                // PNG, marge uniforme, aucun redimensionnement : identique à
                // l'image enregistrée manuellement depuis la page billet. Repli
                // JPEG uniquement si le PNG dépasserait la limite WhatsApp (5 Mo).
                const png = canvas.toDataURL('image/png');

                return png.length * 0.75 > 4_500_000 ? canvas.toDataURL('image/jpeg', 0.92) : png;
            });
        },

        async ready() {
            if (document.fonts && document.fonts.ready) {
                try {
                    await document.fonts.ready;
                } catch (e) {
                    /* les polices sont un bonus, on continue */
                }
            }
        },
    };
}

window.ticketWhatsApp = ticketWhatsApp;
