{{-- Aperçu du billet capturé en image + enregistrement (galerie sur mobile). --}}
<div
    x-cloak
    x-show="open"
    x-transition.opacity
    @keydown.escape.window="close()"
    class="no-print fixed inset-0 z-50 flex items-center justify-center bg-ink-950/70 p-4"
    role="dialog"
    aria-modal="true"
    aria-label="Billet enregistré"
>
    <div class="max-h-full w-full max-w-xs overflow-auto rounded-3xl bg-white p-4 shadow-2xl" @click.outside="close()">
        <img :src="dataUrl" alt="Billet" class="mx-auto max-h-[64vh] w-auto max-w-full rounded-2xl ring-1 ring-ink-200">

        <div class="mt-4 flex gap-2">
            <button type="button" x-show="canShare" @click="share()" class="btn-primary btn-sm flex-1 justify-center">
                @icon('download', 'w-4 h-4') Enregistrer
            </button>
            <button type="button" @click="close()" class="btn-outline btn-sm flex-1 justify-center">
                Fermer
            </button>
        </div>
    </div>
</div>
