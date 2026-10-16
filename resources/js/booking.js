/**
 * Récapitulatif des billets de la page de réservation :
 * ajouter un type, augmenter / réduire la quantité, supprimer une ligne.
 *
 * @param {{ prices: Record<string, number>, limits: Record<string, number>, selected: Record<string, number> }} config
 */
window.bookingRecap = (config) => ({
    prices: config.prices,
    limits: config.limits,
    sel: { ...config.selected },
    toAdd: '',

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
});
