/**
 * Page de paiement : crée la checkout session DexPay puis redirige vers le
 * checkout hébergé.
 *
 * Le checkout DexPay répond `X-Frame-Options: DENY` et
 * `Content-Security-Policy: frame-ancestors 'none'` : il refuse tout affichage
 * dans une iframe ou une popup, quel que soit le domaine. On ne peut donc pas
 * utiliser leur SDK en mode popup, seule la navigation top-level fonctionne.
 * Le retour est assuré par `success_url` et par la page /succes qui attend la
 * confirmation avant d'afficher les billets.
 */
function paymentPage(config) {
    return {
        config,
        loading: false,
        error: '',
        phone: '',
        operator: '',

        init() {
            this.phone = this.config.phone ?? '';
            this.operator = this.config.operator ?? '';
        },

        get currentOperator() {
            return (this.config.operators ?? []).find((op) => op.value === this.operator);
        },

        /** Surcharge de service (couverture du frais de retrait) selon l'opérateur. */
        get fee() {
            const op = this.currentOperator;
            const base = this.config.amount ?? 0;

            if (!op || !Number(op.fee)) return 0;

            return op.fee_type === 'fixed'
                ? Math.round(Number(op.fee))
                : Math.round((base * Number(op.fee)) / 100);
        },

        /**
         * Frais d'encaissement DexPay, prélevés au moment du paiement
         * (client_support_fee=true) au même taux que la surcharge de retrait.
         */
        get dexpayFee() {
            return this.fee;
        },

        /** Total des frais (service + encaissement) réunis sur une seule ligne. */
        get totalFee() {
            return this.fee + this.dexpayFee;
        },

        get totalFeeLabel() {
            return this.totalFee === 0 ? '' : moneyLabel(this.totalFee);
        },

        get amountLabel() {
            return moneyLabel((this.config.amount ?? 0) + this.fee + this.dexpayFee);
        },

        get phoneValid() {
            return this.phone.replace(/[^0-9]/g, '').length >= 8;
        },

        get canPay() {
            return this.operator !== '' && this.phoneValid && !this.loading;
        },

        async pay() {
            this.error = '';
            this.loading = true;

            try {
                const response = await fetch(this.config.startUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        customer_phone: this.phone,
                        operator: this.operator,
                    }),
                });

                const data = await response.json();

                if (!response.ok || !data.ok || !data.payment_url) {
                    throw new Error(data.message || 'Impossible de démarrer le paiement.');
                }

                this.openCheckout(data.payment_url);
            } catch (e) {
                this.error = e.message || 'Une erreur est survenue.';
                this.loading = false;
            }
        },

        openCheckout(paymentUrl) {
            window.location.href = paymentUrl;
        },
    };
}

/** Montant formaté en "1 500 FCFA" (même format que money() côté serveur). */
function moneyLabel(amount) {
    return `${Math.round(amount).toLocaleString('fr-FR').replace(/\u202f/g, ' ')} FCFA`;
}

/**
 * Page de retour paiement : attend la confirmation serveur (webhook).
 */
function paymentWatcher(config) {
    return {
        config,
        attempts: 0,

        init() {
            this.poll();
        },

        async poll() {
            if (this.attempts > 45) return; // ~3 min

            this.attempts += 1;

            try {
                const response = await fetch(this.config.statusUrl, { headers: { Accept: 'application/json' } });

                if (response.ok) {
                    const data = await response.json();

                    if (data.paid) {
                        window.location.href = this.config.ticketsUrl;
                        return;
                    }
                }
            } catch (e) {
                /* on réessaie */
            }

            setTimeout(() => this.poll(), 4000);
        },
    };
}

window.paymentPage = paymentPage;
window.paymentWatcher = paymentWatcher;