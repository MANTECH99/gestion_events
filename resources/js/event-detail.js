/**
 * Page de détail d'événement : compte à rebours avant l'événement,
 * partage natif, lien copiable et favoris (localStorage).
 */

function eventCountdown(isoDate) {
    const target = new Date(isoDate).getTime();

    return {
        days: '00',
        hours: '00',
        minutes: '00',
        seconds: '00',
        expired: Number.isFinite(target) ? target <= Date.now() : true,

        init() {
            this.tick();

            if (!this.expired) {
                this._timer = setInterval(() => this.tick(), 1000);
            }
        },

        tick() {
            if (!Number.isFinite(target)) {
                this.expired = true;
                return;
            }

            const diff = target - Date.now();

            if (diff <= 0) {
                this.expired = true;
                this.days = '00';
                this.hours = '00';
                this.minutes = '00';
                this.seconds = '00';
                clearInterval(this._timer);
                return;
            }

            const total = Math.floor(diff / 1000);

            this.days = String(Math.floor(total / 86400)).padStart(2, '0');
            this.hours = String(Math.floor((total % 86400) / 3600)).padStart(2, '0');
            this.minutes = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
            this.seconds = String(total % 60).padStart(2, '0');
        },
    };
}

function toast(message, type = 'info') {
    const store = window.Alpine && window.Alpine.store('toast');
    if (store) store.show(message, type);
}

function eventDetailActions(config) {
    return {
        fav: false,
        copied: false,

        init() {
            try {
                this.fav = localStorage.getItem(`fav:${config.slug}`) === '1';
            } catch (e) {
                this.fav = false;
            }
        },

        async share() {
            const payload = {
                title: config.title,
                text: config.title,
                url: config.url,
            };

            if (typeof navigator.share === 'function') {
                try {
                    await navigator.share(payload);
                } catch (e) {
                    /* l'utilisateur a annulé */
                }
                return;
            }

            this.copyLink();
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(config.url);
                this.copied = true;
                toast('Lien copié dans le presse-papiers', 'success');
                setTimeout(() => { this.copied = false; }, 2500);
            } catch (e) {
                toast('Impossible de copier le lien', 'error');
            }
        },

        toggleFav() {
            this.fav = !this.fav;

            try {
                if (this.fav) {
                    localStorage.setItem(`fav:${config.slug}`, '1');
                } else {
                    localStorage.removeItem(`fav:${config.slug}`);
                }
            } catch (e) {
                /* stockage indisponible */
            }

            toast(this.fav ? 'Ajouté à vos favoris' : 'Retiré de vos favoris', this.fav ? 'success' : 'info');
        },
    };
}

window.eventCountdown = eventCountdown;
window.eventDetailActions = eventDetailActions;

/**
 * Sélecteur de quantités des billets (page événement) : boutons + / − par type
 * de ticket, sans ajout au panier. « Réserver maintenant » transmet la
 * sélection à la page de réservation via ?qty[{id}]={quantité}.
 *
 * @param {{ types: { id: number, max: number }[], url: string }} config
 */
function ticketPicker(config) {
    const qtys = {};
    config.types.forEach((type) => { qtys[type.id] = 0; });

    return {
        qtys,
        types: config.types,

        qtyOf(id) {
            return this.qtys[id] ?? 0;
        },

        inc(id) {
            const type = this.types.find((t) => t.id === id);
            if (!type) return;

            this.qtys[id] = Math.min(type.max, (Number(this.qtyOf(id)) || 0) + 1);
        },

        dec(id) {
            const qty = Number(this.qtyOf(id)) || 0;
            if (qty <= 0) return;

            this.qtys[id] = qty - 1;
        },

        total() {
            return Object.values(this.qtys).reduce((sum, qty) => sum + (Number(qty) || 0), 0);
        },

        /** URL de la page de réservation avec les quantités choisies. */
        reserveUrl() {
            const params = new URLSearchParams();

            this.types.forEach((type) => {
                const qty = Number(this.qtyOf(type.id)) || 0;
                if (qty > 0) params.set(`qty[${type.id}]`, qty);
            });

            const query = params.toString();

            return query ? `${config.url}?${query}` : config.url;
        },
    };
}

window.ticketPicker = ticketPicker;
