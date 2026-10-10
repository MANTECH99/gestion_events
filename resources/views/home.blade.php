@extends('layouts.app', [
    'description' => 'Découvrez les concerts, festivals et événements à venir au Sénégal. Réservez en ligne et payez avec Wave ou Orange Money.',
    'schema' => [\App\Support\Seo::website()],
])

@section('content')

    {{-- Héro --}}
    <section class="relative overflow-hidden gradient-brand">
        {{-- Décor --}}
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]" style="background-image: linear-gradient(rgba(255,255,255,.7) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.7) 1px, transparent 1px); background-size: 46px 46px;"></div>
        <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -left-20 bottom-0 h-80 w-80 rounded-full bg-accent-400/25 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                {{-- Texte --}}
                <div class="text-center lg:text-left">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-white ring-1 ring-white/25 backdrop-blur">
                        @icon('check-circle', 'w-3.5 h-3.5') Plateforme officielle de billetterie
                    </span>

                    <h1 class="mt-6 text-3xl font-extrabold leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Achetez vos billets en ligne,<br class="hidden sm:block">
                        en toute <span class="text-accent-300">confiance</span>
                    </h1>

                    <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg lg:mx-0">
                        Concerts, festivals, sport et spectacles au Sénégal : réservez votre place en
                        quelques secondes et payez avec Wave ou Orange Money.
                    </p>

                    <div class="mt-8 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-center lg:justify-start">
                        <a href="#evenements" class="btn-accent sm:w-auto">
                            @icon('ticket', 'w-5 h-5') Voir les événements
                        </a>
                        <a href="{{ route('register') }}" class="btn-invert sm:w-auto">
                            @icon('user', 'w-5 h-5') S'inscrire
                        </a>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-white/75 lg:justify-start">
                        <span class="flex items-center gap-2">@icon('shield', 'w-4 h-4') Paiement sécurisé</span>
                        <span class="hidden h-4 w-px bg-white/25 sm:block"></span>
                        <span class="flex items-center gap-2">@icon('qr-code', 'w-4 h-4') Billets à QR code</span>
                    </div>
                </div>

                {{-- Carte de recherche --}}
                <div class="mx-auto w-full max-w-md">
                    <div class="rounded-3xl bg-white p-6 shadow-2xl shadow-ink-950/30 ring-1 ring-black/5 sm:p-7">
                        <h2 class="text-lg font-bold text-ink-950">Trouvez votre prochain événement</h2>
                        <p class="mt-1 text-sm text-ink-500">Par nom, ville ou type d'événement.</p>

                        <form method="GET" action="{{ route('home') }}" class="mt-5 space-y-3.5">
                            <label class="block">
                                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-ink-500">Recherche</span>
                                <div class="flex items-center gap-2 rounded-xl border border-ink-200 bg-ink-50/60 px-3.5 py-2.5 transition focus-within:border-brand-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-500/20">
                                    <span class="text-ink-400">@icon('search', 'w-4 h-4')</span>
                                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Nom de l'événement, ville…"
                                           class="w-full border-0 bg-transparent text-sm text-ink-900 placeholder:text-ink-400 focus:outline-none">
                                </div>
                            </label>

                            <label class="block">
                                <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-ink-500">Type d'événement</span>
                                <div class="flex items-center gap-2 rounded-xl border border-ink-200 bg-ink-50/60 px-3.5 py-2.5 transition focus-within:border-brand-400 focus-within:bg-white focus-within:ring-2 focus-within:ring-brand-500/20">
                                    <span class="text-ink-400">@icon('filter', 'w-4 h-4')</span>
                                    <select name="type" class="w-full border-0 bg-transparent text-sm text-ink-700 focus:outline-none">
                                        <option value="">Tous les types</option>
                                        @foreach ($types as $key => $label)
                                            <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </label>

                            <button type="submit" class="btn-primary w-full">
                                @icon('search', 'w-4 h-4') Rechercher
                            </button>
                        </form>

                        <p class="mt-4 flex items-center justify-center gap-1.5 text-xs text-ink-500">
                            @icon('shield', 'w-3.5 h-3.5') Paiement 100 % sécurisé via DexPay
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Filtres --}}
    <section id="evenements" class="mx-auto max-w-7xl scroll-mt-20 px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">Événements à venir</h2>
                <p class="mt-1.5 text-sm text-ink-600">
                    <span class="font-semibold text-ink-900">{{ $events->total() }}</span>
                    événement{{ $events->total() > 1 ? 's' : '' }} disponible{{ $events->total() > 1 ? 's' : '' }}
                    @if (request()->filled('q') || request()->filled('type'))
                        pour « <span class="font-semibold text-ink-900">{{ request('q') ?: request('type') }}</span> »
                    @endif
                </p>
            </div>

            @if (request()->filled('q') || request()->filled('type'))
                <a href="{{ route('home') }}" class="btn-outline btn-sm w-fit">
                    @icon('close', 'w-4 h-4') Réinitialiser les filtres
                </a>
            @endif
        </div>

        @if ($events->isEmpty())
            <div class="card mt-8 flex flex-col items-center px-6 py-16 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-ink-100 text-3xl">🎭</span>
                <h3 class="mt-5 text-lg font-bold text-ink-900">Aucun événement trouvé</h3>
                <p class="mt-1.5 max-w-sm text-sm text-ink-600">
                    Modifiez vos filtres ou revenez plus tard : de nouveaux événements sont ajoutés régulièrement.
                </p>
                <a href="{{ route('home') }}" class="btn-outline btn-sm mt-5">Réinitialiser les filtres</a>
            </div>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    @include('partials.event-card', ['event' => $event])
                @endforeach
            </div>

            <div class="mt-10">{{ $events->links() }}</div>
        @endif
    </section>

    {{-- Réassurance --}}
    <section class="border-y border-ink-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'shield', 'title' => 'Paiement 100 % sécurisé', 'text' => 'Vos paiements passent par DexPay (DEXCHANGE PAY). Aucune donnée bancaire ne transite par nos serveurs.'],
                    ['icon' => 'ticket', 'title' => 'Ticket à QR code unique', 'text' => 'Chaque billet possède son propre QR code, à présenter à l\'entrée. Valable une seule fois.'],
                    ['icon' => 'scan', 'title' => 'Contrôle instantané', 'text' => 'Les agents valident les billets en quelques secondes. Un billet déjà utilisé est automatiquement refusé.'],
                ] as $item)
                    <div>
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                            @icon($item['icon'], 'w-6 h-6')
                        </span>
                        <h3 class="mt-4 font-bold text-ink-900">{{ $item['title'] }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink-600">{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

@endsection