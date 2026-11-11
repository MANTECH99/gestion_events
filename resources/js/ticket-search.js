/**
 * Recherche live sous le filtre de la liste agent.
 *
 * Frappe → debounce 250 ms (Alpine) → fetch de la même URL avec ?q=
 * → on ne remplace que le conteneur #liste-billets : le champ de
 * recherche reste hors conteneur, donc le focus n'est jamais perdu.
 */
function ticketSearch() {
    return {
        query: '',
        busy: false,
        token: 0,

        init() {
            // Valeur initiale depuis l'URL (le serveur a déjà rendu la
            // liste filtrée) : on observe sans recharger à l'ouverture.
            this.query = new URLSearchParams(window.location.search).get('q') ?? '';
            this.$watch('query', () => this.run());
        },

        async run() {
            const url = new URL(window.location.href);
            const q = this.query.trim();

            if (q) {
                url.searchParams.set('q', q);
            } else {
                url.searchParams.delete('q');
            }

            // Une nouvelle frappe repart de la page 1 (sinon on pourrait
            // atterrir sur une page hors domaine de la nouvelle recherche).
            url.searchParams.delete('page');

            // Jeton anti-course : seule la dernière frappe a le droit
            // d'écraser les résultats affichés.
            const token = ++this.token;
            this.busy = true;

            try {
                const response = await fetch(url.pathname + url.search, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'text/html',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const html = await response.text();
                const next = new DOMParser()
                    .parseFromString(html, 'text/html')
                    .querySelector('#liste-billets');
                const current = document.querySelector('#liste-billets');

                if (!next || !current || token !== this.token) {
                    return;
                }

                current.innerHTML = next.innerHTML;
                window.history.replaceState({}, '', url.pathname + url.search);
            } catch {
                // Réseau indisponible : on garde la liste déjà affichée.
            } finally {
                if (token === this.token) {
                    this.busy = false;
                }
            }
        },
    };
}

window.ticketSearch = ticketSearch;
