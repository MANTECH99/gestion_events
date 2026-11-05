/**
 * Page de réservation fusionnée : récapitulatif des billets + règlement de la
 * commande (choix de l'opérateur DexPay, numéro mobile money, frais et total
 * à payer). Le formulaire POST crée les réservations et démarre la checkout —
 * le serveur redirige ensuite vers la page hébergée DexPay.
 *
 * Le checkout DexPay répond `X-Frame-Options: DENY` et refuse iframe/popup :
 * seule la navigation top-level fonctionne (voir payment.js).
 *
 * @param {{
 *   prices: Record<string, number>,
 *   limits: Record<string, number>,
 *   selected: Record<string, number>,
 *   operators?: { value: string, name: string, fee?: number|null, fee_type?: string }[],
 *   operator?: string,
 *   phone?: string,
 *   dexpayFeeEnabled?: boolean,
 * }} config
 */
window.bookingRecap = (config) => ({
    prices: config.prices,
    limits: config.limits,
    sel: { ...config.selected },
    toAdd: '',

    /* ---- Paiement : section « Réglez votre commande » ---- */
    operators: config.operators ?? [],
    operator: config.operator ?? '',
    phone: config.phone ?? '',
    // DexPay n'ajoute ses frais d'encaissement qu'avec client_support_fee=true.
    dexpayFeeEnabled: config.dexpayFeeEnabled ?? true,

    isSel(id) {
        return String(id) in this.sel;
    },

    qtyOf(id) {
        return this.sel[id] ?? 0;
    },

    add(id) {
        if (!id || this.isSel(id)) return;

        this.sel[id] = 1;
        this.toAdd = '';
    },

    remove(id) {
        delete this.sel[id];
    },

    inc(id) {
        if (!this.isSel(id)) return;

        const limit = this.limits[id] ?? 20;
        this.sel[id] = Math.min(limit, (Number(this.qtyOf(id)) || 0) + 1);
    },

    dec(id) {
        if (!this.isSel(id)) return;

        this.sel[id] = Math.max(1, (Number(this.qtyOf(id)) || 0) - 1);
    },

    total() {
        return Object.values(this.sel).reduce((sum, qty) => sum + (Number(qty) || 0), 0);
    },

    lineTotal(id) {
        return (this.prices[id] ?? 0) * (Number(this.qtyOf(id)) || 0);
    },

    grandTotal() {
        return Object.keys(this.sel).reduce((sum, id) => sum + this.lineTotal(id), 0);
    },

    /** 12500 → « 12 500 FCFA » (mêmes séparateurs que le helper money()). */
    fmt(amount) {
        return String(Math.round(amount)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' FCFA';
    },

    /* ------------------------------------------------------------------ */
    /* Paiement                                                            */
    /* ------------------------------------------------------------------ */

    get currentOperator() {
        return this.operators.find((op) => op.value === this.operator);
    },

    /** Surcharge de service (couverture du frais de retrait) selon l'opérateur. */
    get fee() {
        const op = this.currentOperator;
        const base = this.grandTotal();

        if (!op || !Number(op.fee)) return 0;

        return op.fee_type === 'fixed'
            ? Math.round(Number(op.fee))
            : Math.round((base * Number(op.fee)) / 100);
    },

    /**
     * Frais d'encaissement DexPay, prélevés au moment du paiement
     * (client_support_fee=true) au même taux que la surcharge de retrait.
     * Sans ce flag, DexPay ne les débite pas : on n'affiche rien.
     */
    get dexpayFee() {
        return this.dexpayFeeEnabled ? this.fee : 0;
    },

    /** Total des frais (service + encaissement) réunis sur une seule ligne. */
    get totalFee() {
        return this.fee + this.dexpayFee;
    },

    get totalFeeLabel() {
        return this.totalFee === 0 ? '' : this.fmt(this.totalFee);
    },

    /** Total réellement débité : billets + frais. */
    get amountLabel() {
        return this.fmt(this.grandTotal() + this.totalFee);
    },

    get phoneValid() {
        return this.phone.replace(/[^0-9]/g, '').length >= 8;
    },

    /** Prêt à payer : billets choisis, opérateur (s'il y en a) et numéro valide. */
    get canPay() {
        if (this.total() === 0) return false;

        // DexPay non configuré : on garde l'ancien bouton « Réserver »
        // (redirection vers la page de paiement en repli).
        if (this.operators.length === 0) return true;

        return this.operator !== '' && this.phoneValid;
    },
});
