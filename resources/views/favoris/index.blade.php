@extends('layouts.app', ['title' => 'Mes favoris'])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
    <div class="mx-auto max-w-5xl px-4 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' }} sm:px-6 lg:px-8 pb-28 lg:pb-8">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Mes favoris</h1>
            <p class="mt-1 text-sm text-white/60">Les événements que vous avez enregistrés sur cet appareil.</p>
        </div>

        <div x-data="favorisPage()" x-init="load()">
            <div x-show="slugs.length === 0" class="rounded-2xl border border-white/10 bg-white/5 p-8 text-center">
                @icon('heart', 'w-10 h-10 mx-auto text-white/30')
                <p class="mt-3 font-semibold text-white">Aucun favori pour le moment</p>
                <p class="mt-1 text-sm text-white/55">Touchez le cœur sur une page événement pour l'ajouter ici.</p>
                <a href="{{ route('home') }}#evenements" class="btn-hero-primary btn-sm mt-5 inline-flex">Voir les événements</a>
            </div>

            <div x-show="slugs.length > 0" class="grid gap-3 sm:grid-cols-2">
                <template x-for="slug in slugs" :key="slug">
                    <a :href="'/evenements/' + slug"
                       class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 transition hover:border-orange-400/40">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                            @icon('heart', 'w-5 h-5')
                        </span>
                        <span class="min-w-0 flex-1 truncate font-semibold text-white" x-text="slug"></span>
                        @icon('arrow-right', 'w-4 h-4 shrink-0 text-white/40')
                    </a>
                </template>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function favorisPage() {
    return {
        slugs: [],
        load() {
            this.slugs = Object.keys(localStorage)
                .filter(k => k.startsWith('fav:'))
                .filter(k => localStorage[k] === '1')
                .map(k => k.slice(4));
        },
    };
}
</script>
@endpush
