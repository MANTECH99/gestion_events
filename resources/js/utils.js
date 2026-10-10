/** Charge un script externe une seule fois. */
const loaded = new Map();

export function loadScript(src) {
    if (loaded.has(src)) return loaded.get(src);

    const promise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve(src);
        script.onerror = () => reject(new Error(`Impossible de charger ${src}`));
        document.head.appendChild(script);
    });

    loaded.set(src, promise);

    return promise;
}

/** Montant formaté en XOF. */
export function formatXof(amount) {
    return `${new Intl.NumberFormat('fr-FR').format(Math.round(amount))} FCFA`;
}

/** Petite notification toast. */
export function toast(message, type = 'info') {
    const root = document.getElementById('toast-root');
    if (!root) return;

    const tones = {
        success: 'border-emerald-200 bg-emerald-50 text-emerald-900',
        error: 'border-rose-200 bg-rose-50 text-rose-900',
        info: 'border-ink-200 bg-white text-ink-800',
    };

    const el = document.createElement('div');
    el.className = `pointer-events-auto max-w-md rounded-2xl border px-4 py-3 text-sm font-medium shadow-lg ${tones[type] ?? tones.info}`;
    el.textContent = message;
    root.appendChild(el);

    setTimeout(() => {
        el.style.transition = 'opacity .4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 3200);
}