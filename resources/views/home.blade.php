@extends('layouts.app', [
    'description' => 'Découvrez les concerts, festivals et événements à venir au Sénégal. Réservez en ligne et payez avec Wave ou Orange Money.',
    'schema' => [\App\Support\Seo::website()],
])

@section('content')

    {{-- Héro --}}
    <section class="relative overflow-hidden bg-ink-950">
        {{-- Décor --}}
        <div class="absolute inset-0" aria-hidden="true">
            <div class="absolute inset-0" style="background:
                linear-gradient(180deg, rgba(6,18,44,.85) 0%, rgba(14,48,96,.62) 40%, rgba(10,28,64,.35) 60%, rgba(8,12,28,0) 78%);"></div>
            <div class="absolute inset-0" style="background:
                radial-gradient(50% 36% at 44% 22%, rgba(40,110,200,.35), rgba(40,110,200,0) 72%);"></div>

            <div class="absolute bottom-0 right-[calc((100vw-min(80rem,100vw))/2+2rem)] hidden h-[80%] lg:block"
                 style="aspect-ratio: 1112 / 658;
                        -webkit-mask-image: linear-gradient(to right, transparent 0%, #000 24%, #000 80%, transparent 100%), linear-gradient(to bottom, transparent 0%, #000 15%, #000 74%, transparent 97%);
                        mask-image: linear-gradient(to right, transparent 0%, #000 24%, #000 80%, transparent 100%), linear-gradient(to bottom, transparent 0%, #000 15%, #000 74%, transparent 97%);
                        -webkit-mask-composite: source-in;
                        mask-composite: intersect;">
                <img src="{{ asset('images/hero-fans-v3.jpg') }}" alt=""
                     class="h-full w-full object-cover" style="filter: brightness(1.14) saturate(1.05);">
                <div class="absolute inset-0" style="background:
                    linear-gradient(180deg, rgba(6,18,44,.32) 0%, rgba(10,28,60,.14) 42%, rgba(8,12,28,.03) 78%);"></div>
            </div>

            <div class="absolute inset-0 bg-gradient-to-r from-ink-950 from-5% via-ink-950/55 via-45% to-ink-950/5"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-ink-950/70 via-transparent to-ink-950/35"></div>
        </div>

        @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
        <div class="relative mx-auto max-w-7xl px-4 pb-14 {{ $hasFlash ? 'pt-6 sm:pt-8' : 'pt-28 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-32' }} sm:px-6 lg:px-8 lg:pb-20 {{ $hasFlash ? 'lg:pt-10' : 'lg:pt-40' }}">
            <div class="grid items-center gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-6">
                {{-- Texte --}}
                <div class="text-center lg:text-left">
                    <span class="inline-flex items-center gap-2.5 rounded-full bg-white/10 py-2 pl-3 pr-4 text-sm font-medium text-white ring-1 ring-white/20 backdrop-blur">
                        <svg viewBox="0 0 60 40" class="h-4 w-6 shrink-0 rounded-[3px] ring-1 ring-black/20" aria-hidden="true">
                            <rect width="20" height="40" fill="#00853F"/>
                            <rect x="20" width="20" height="40" fill="#FDEF42"/>
                            <rect x="40" width="20" height="40" fill="#E31B23"/>
                            <path d="M30 12.5 31.76 17.57 37.13 17.68 32.85 20.93 34.41 26.07 30 23 25.59 26.07 27.15 20.93 22.87 17.68 28.24 17.57Z" fill="#00853F"/>
                        </svg>
                        Les meilleurs événements au Sénégal
                    </span>

                    <h1 class="mt-7 text-4xl font-extrabold leading-[1.06] tracking-tight text-white sm:text-5xl lg:text-[3.4rem]">
                        Tes événements<br class="hidden sm:block">
                        préférés, en un<br>
                        <span class="relative inline-block text-orange-400">seul clic !
                            <svg class="absolute -bottom-2.5 left-0 h-3.5 w-full" viewBox="0 0 220 14" fill="none"
                                 preserveAspectRatio="none" aria-hidden="true">
                                <path d="M4 9 C 70 3, 160 3, 216 8" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                            </svg>
                        </span>
                    </h1>

                    <p class="mx-auto mt-7 max-w-xl leading-relaxed text-white/70 sm:text-lg lg:mx-0">
                        Concerts, festivals, spectacles, soirées, conférences…
                        Réserve tes billets en toute simplicité et ne rate plus
                        aucun événement au Sénégal !
                    </p>

                    <div class="mt-9 flex flex-col items-center gap-4 sm:flex-row sm:justify-center lg:justify-start">
                        <a href="#evenements" class="btn-hero-primary">
                            Découvrir les événements @icon('arrow-right', 'w-5 h-5')
                        </a>
                        <button type="button" class="btn-hero-ghost">
                            <span class="grid h-7 w-7 place-items-center rounded-full bg-white/15">
                                <svg viewBox="0 0 24 24" class="h-3 w-3" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5z"/></svg>
                            </span>
                            Voir la vidéo
                        </button>
                    </div>

                    {{-- Desktop : statistiques --}}
                    <div class="mt-12 hidden flex-wrap items-start justify-center gap-x-10 gap-y-6 lg:flex lg:justify-start">
                        <div class="flex items-start gap-3">
                            <span class="text-orange-400">@icon('ticket', 'w-7 h-7')</span>
                            <div>
                                <p class="text-2xl font-extrabold text-white sm:text-3xl">+20</p>
                                <p class="mt-1 text-sm leading-snug text-white/60">Événements<br>par an</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 border-l border-white/10 pl-10">
                            <span class="text-orange-400">@icon('users', 'w-7 h-7')</span>
                            <div>
                                <p class="text-2xl font-extrabold text-white sm:text-3xl">+50K</p>
                                <p class="mt-1 text-sm leading-snug text-white/60">Billets vendus<br>au Sénégal</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3 border-l border-white/10 pl-10">
                            <span class="text-orange-400">@icon('shield', 'w-7 h-7')</span>
                            <div>
                                <p class="font-bold text-white">Paiement sécurisé</p>
                                <div class="mt-1.5">@include('partials.payment-methods')</div>
                            </div>
                        </div>
                    </div>

                    {{-- Mobile : paiement sécurisé --}}
                    <div class="mt-12 rounded-2xl border border-white/10 bg-white/5 p-5 text-center lg:hidden">
                        <h2 class="flex items-center justify-center gap-2.5 text-base font-bold text-white">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                                @icon('shield', 'w-5 h-5')
                            </span>
                            Paiement sécurisé
                        </h2>
                        <p class="mx-auto mt-3 max-w-xs text-sm leading-relaxed text-white/60">
                            Vos informations sont protégées par notre système de cryptage SSL.
                        </p>
                        <div class="mt-4 flex justify-center">@include('partials.payment-methods')</div>
                    </div>
                </div>
            </div>
        </div>

    </section>

    @php
        $allEvents      = collect($events->items());
        $featuredEvents = $allEvents->take(3)->values();
        $upcomingEvents = $allEvents->slice(3)->values();
        $filterActive   = request()->filled('q') || request()->filled('type');

        $typeBadges = [
            'concert'    => 'bg-violet-500 text-white',
            'festival'   => 'bg-orange-500 text-white',
            'theatre'    => 'bg-orange-500 text-white',
            'conference' => 'bg-blue-500 text-white',
            'sport'      => 'bg-sky-500 text-white',
            'evening'    => 'bg-pink-500 text-white',
            'other'      => 'bg-slate-500 text-white',
        ];

        $categories = [
            ['key' => 'concert', 'label' => 'Concerts', 'sub' => 'Artistes, live, musique…', 'bg' => 'bg-violet-500', 'icon' => 'music'],
            ['key' => 'festival', 'label' => 'Festivals', 'sub' => 'Culture, art, ambiance…', 'bg' => 'bg-orange-500', 'icon' => 'calendar'],
            ['key' => 'theatre', 'label' => 'Spectacles', 'sub' => 'Théâtre, humour, danse…', 'bg' => 'bg-pink-500', 'icon' => 'masks'],
            ['key' => 'conference', 'label' => 'Conférences', 'sub' => 'Éducation, business, innovation…', 'bg' => 'bg-blue-500', 'icon' => 'mic'],
            ['key' => 'evening', 'label' => 'Soirées', 'sub' => 'After, nightlife, club…', 'bg' => 'bg-orange-500', 'icon' => 'star'],
        ];
    @endphp

    {{-- Suite de la page : catégories, vedettes, newsletter, prochains événements --}}
    <section class="bg-ink-950 pb-16 pt-2 sm:pt-14">
        <div class="mx-auto max-w-7xl space-y-11 px-4 sm:px-6 lg:px-8">

            {{-- Explorer par catégorie --}}
            <div>
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-lg font-extrabold tracking-tight text-white sm:text-3xl">
                        Explorer par <span class="text-white">catégorie</span>
                    </h2>
                    <a href="{{ route('home') }}" class="group inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-orange-400 transition hover:text-orange-300">
                        Voir tout
                        <span class="transition group-hover:translate-x-0.5">@icon('arrow-right', 'w-4 h-4')</span>
                    </a>
                </div>

                <div class="mt-5 flex gap-3 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden pb-1 -mx-4 px-4 snap-x sm:mx-0 sm:px-0 sm:overflow-visible sm:grid sm:grid-cols-2 sm:snap-none lg:grid-cols-5">
                    @foreach ($categories as $cat)
                        <a href="{{ route('home', ['type' => $cat['key']]) }}#evenements"
                           class="group flex w-24 aspect-square shrink-0 snap-start flex-col items-center justify-center gap-1.5 rounded-2xl border border-white/10 bg-white/5 p-2 text-center transition hover:border-white/25 hover:bg-white/[.07] sm:aspect-auto sm:w-auto sm:flex-row sm:gap-3 sm:rounded-xl sm:px-3.5 sm:py-3 sm:text-left">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full {{ $cat['bg'] }} text-white">
                                @if ($cat['icon'] === 'calendar')
                                    @icon('calendar', 'w-5 h-5')
                                @elseif ($cat['icon'] === 'star')
                                    @icon('star', 'w-5 h-5')
                                @elseif ($cat['icon'] === 'music')
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>
                                    </svg>
                                @elseif ($cat['icon'] === 'masks')
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 3.5c-4.6 0-8.3 3.1-8.3 7.2S7.4 17.9 12 17.9s8.3-3.1 8.3-7.2S16.6 3.5 12 3.5Z"/>
                                        <path d="M8.5 9.5h.01M15.5 9.5h.01" stroke-width="2.6"/>
                                        <path d="M8.6 13c.85 1.2 2 1.9 3.4 1.9s2.55-.7 3.4-1.9"/>
                                        <path d="M6.8 4.6 5.4 2.6M17.2 4.6l1.4-2"/>
                                    </svg>
                                @else
                                    <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 2.5a2.7 2.7 0 0 0-2.7 2.7v6a2.7 2.7 0 0 0 5.4 0v-6A2.7 2.7 0 0 0 12 2.5Z"/>
                                        <path d="M18.5 10.5v1a6.5 6.5 0 0 1-13 0v-1"/>
                                        <path d="M12 18v3.5M8.5 21.5h7"/>
                                    </svg>
                                @endif
                            </span>
                            <span class="min-w-0 w-full sm:w-auto sm:flex-1">
                                <span class="block truncate text-sm font-semibold text-white">{{ $cat['label'] }}</span>
                                <span class="block truncate text-xs text-white/45">{{ $cat['sub'] }}</span>
                            </span>
                            <span class="hidden shrink-0 text-white/40 transition group-hover:translate-x-0.5 group-hover:text-white/70 sm:block">@icon('chevron-right', 'w-4 h-4')</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Événements en vedette --}}
            <div id="evenements" class="scroll-mt-20">
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-lg font-extrabold tracking-tight text-white sm:text-3xl">
                        Événements <span class="text-white">en vedette</span>
                    </h2>
                    <a href="{{ route('home') }}" class="group inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-orange-400 transition hover:text-orange-300">
                        Voir tout
                        <span class="transition group-hover:translate-x-0.5">@icon('arrow-right', 'w-4 h-4')</span>
                    </a>
                </div>

                @if ($filterActive)
                    <a href="{{ route('home') }}" class="mt-4 inline-flex w-fit items-center gap-1.5 rounded-full border border-white/20 bg-white/5 px-3.5 py-1.5 text-xs font-semibold text-white/75 transition hover:border-orange-400/60 hover:text-orange-400">
                        @icon('close', 'w-3.5 h-3.5')
                        {{ $events->total() }} événement{{ $events->total() > 1 ? 's' : '' }} pour « {{ request('q') ?: request('type') }} » — Réinitialiser
                    </a>
                @endif

                @if ($featuredEvents->isEmpty())
                    <div class="mt-6 rounded-2xl border border-dashed border-white/15 bg-white/5 px-6 py-14 text-center">
                        <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-white/10 text-3xl">🎭</span>
                        <h3 class="mt-4 text-lg font-bold text-white">Aucun événement trouvé</h3>
                        <p class="mx-auto mt-1.5 max-w-sm text-sm text-white/55">
                            Modifiez vos filtres ou revenez plus tard : de nouveaux événements sont ajoutés régulièrement.
                        </p>
                        <a href="{{ route('home') }}" class="btn-hero-primary mt-6">Réinitialiser les filtres</a>
                    </div>
                @else
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($featuredEvents as $event)
                            <a href="{{ route('events.show', $event) }}"
                               class="group relative block aspect-[16/9] overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                                @if ($event->image_url)
                                    <img src="{{ $event->image_url }}" alt="{{ $event->title }}" loading="lazy"
                                         class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                @else
                                    <div class="absolute inset-0 grid place-items-center gradient-brand text-5xl">{{ ticket_type_icon($event->event_type) }}</div>
                                @endif
                                <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(10,13,20,0) 26%, rgba(10,13,20,.82) 68%, rgba(10,13,20,.97) 100%);"></div>

                                <span class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-semibold shadow-sm {{ $typeBadges[$event->event_type] ?? $typeBadges['other'] }}">
                                    {{ $event->type_label }}
                                </span>

                                <div class="absolute inset-x-0 bottom-0 flex flex-col gap-2 p-4">
                                    <h3 class="line-clamp-1 text-lg font-bold leading-tight text-white">{{ $event->title }}</h3>
                                    <div class="flex items-center gap-4 text-xs font-medium text-white/80">
                                        <span class="inline-flex items-center gap-1.5">
                                            @icon('calendar', 'w-3.5 h-3.5')
                                            {{ ucfirst(event_date_fr($event->event_date, 'd F Y')) }}
                                        </span>
                                        <span class="inline-flex min-w-0 items-center gap-1.5">
                                            @icon('map-pin', 'w-3.5 h-3.5')
                                            <span class="truncate">{{ $event->location }}</span>
                                        </span>
                                    </div>
                                    <div class="mt-0.5 flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-orange-400">
                                            À partir de {{ $event->min_price === null ? '—' : money($event->min_price) }}
                                        </p>
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-white/20 bg-white/10 text-white transition group-hover:border-orange-400 group-hover:bg-orange-400 group-hover:text-ink-950">
                                            @icon('arrow-right', 'w-4 h-4')
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Newsletter --}}
            <div id="apropos" class="relative scroll-mt-20 overflow-hidden rounded-3xl border border-orange-500/25" style="background: #160d08;">
                <div class="absolute inset-y-0 right-0 hidden w-[70%] sm:block" style="isolation: isolate;" aria-hidden="true">
                    <img src="{{ asset('images/hero-fans-v2.jpg') }}" alt=""
                         class="h-full w-full object-cover"
                         style="object-position: center 32%;
                                -webkit-mask-image: linear-gradient(to right, transparent 0%, #000 42%);
                                mask-image: linear-gradient(to right, transparent 0%, #000 42%);">
                    <div class="absolute inset-0" style="background: #ea580c; mix-blend-mode: color; opacity: .8;"></div>
                    <div class="absolute inset-0" style="background: radial-gradient(55% 90% at 58% 50%, rgba(249,115,22,.42), rgba(249,115,22,0) 72%);"></div>
                    <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(20,10,6,1) 0%, rgba(22,11,6,.92) 26%, rgba(54,25,8,.55) 58%, rgba(18,10,6,.42) 100%);"></div>
                </div>

                <div class="relative z-10 flex max-w-2xl items-start gap-4 p-6 sm:p-8 lg:p-10">
                    <svg viewBox="0 0 64 64" class="mt-1 h-11 w-11 shrink-0" fill="none" aria-hidden="true">
                        <g transform="translate(-4 -9) rotate(-20 32 32)">
                            <path d="M14 18h36a5 5 0 0 1 5 5v3.5a6.5 6.5 0 0 0 0 13V43a5 5 0 0 1-5 5H14a5 5 0 0 1-5-5v-3.5a6.5 6.5 0 0 0 0-13V23a5 5 0 0 1 5-5Z" fill="#f97316" opacity=".55"/>
                        </g>
                        <g>
                            <path d="M16 20h34a5 5 0 0 1 5 5v3.5a6.5 6.5 0 0 0 0 13V45a5 5 0 0 1-5 5H16a5 5 0 0 1-5-5v-3.5a6.5 6.5 0 0 0 0-13V25a5 5 0 0 1 5-5Z" fill="#fb923c"/>
                            <path d="M36 24.5v3.5M36 32v3.5M36 39.5v3.5" stroke="#431407" stroke-width="3.2" stroke-linecap="round" stroke-dasharray=".5 7"/>
                        </g>
                    </svg>

                    <div class="min-w-0">
                        <h2 class="text-2xl font-extrabold leading-tight tracking-tight text-white sm:text-3xl">
                            Ne rate plus aucun <span class="text-orange-400">événement !</span>
                        </h2>
                        <p class="mt-2 text-sm text-white/70 sm:text-base">
                            Inscris-toi et reçois les dernières actualités et offres exclusives.
                        </p>
                        <a href="{{ route('register') }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-ink-950 transition hover:bg-orange-100">
                            S'inscrire @icon('arrow-right', 'w-4 h-4')
                        </a>
                    </div>
                </div>

                <div class="pointer-events-none absolute right-6 top-1/2 hidden -translate-y-1/2 rotate-[-6deg] lg:block">
                    <p class="font-handwritten text-3xl font-bold leading-none text-white">Le Sénégal<br>vit en grand !</p>
                    <svg class="mt-1.5 h-2.5 w-36" viewBox="0 0 144 10" fill="none" aria-hidden="true">
                        <path d="M2 6 C 40 2, 100 2, 142 5" stroke="url(#sn-trait-news)" stroke-width="4" stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="sn-trait-news" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0" stop-color="#34d399"/>
                                <stop offset=".5" stop-color="#fdef42"/>
                                <stop offset="1" stop-color="#e31b23"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>

            {{-- Prochains événements --}}
            @if ($upcomingEvents->isNotEmpty())
                <div>
                    <div class="flex items-end justify-between gap-4">
                        <h2 class="text-lg font-extrabold tracking-tight text-white sm:text-3xl">
                            Prochains <span class="text-white">événements</span>
                        </h2>
                        <a href="{{ route('home') }}" class="group inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-orange-400 transition hover:text-orange-300">
                            Voir tout
                            <span class="transition group-hover:translate-x-0.5">@icon('arrow-right', 'w-4 h-4')</span>
                        </a>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-2">
                        @foreach ($upcomingEvents as $event)
                            <a href="{{ route('events.show', $event) }}"
                               class="group flex overflow-hidden rounded-2xl border border-white/10 bg-white/5 transition hover:border-white/25">
                                <div class="relative w-[36%] shrink-0 sm:w-[38%]">
                                    @if ($event->image_url)
                                        <img src="{{ $event->image_url }}" alt="{{ $event->title }}" loading="lazy"
                                             class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @else
                                        <div class="absolute inset-0 grid place-items-center gradient-brand text-3xl">{{ ticket_type_icon($event->event_type) }}</div>
                                    @endif
                                    <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(10,13,20,.25) 0%, rgba(10,13,20,0) 55%);"></div>
                                </div>

                                <div class="flex min-w-0 flex-1 flex-col justify-center gap-1.5 px-4 py-3.5">
                                    <span class="w-fit rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $typeBadges[$event->event_type] ?? $typeBadges['other'] }}">
                                        {{ $event->type_label }}
                                    </span>
                                    <h3 class="line-clamp-1 text-base font-bold text-white sm:text-lg">{{ $event->title }}</h3>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-medium text-white/75">
                                        <span class="inline-flex items-center gap-1.5">
                                            @icon('calendar', 'w-3.5 h-3.5')
                                            {{ ucfirst(event_date_fr($event->event_date, 'd F Y')) }}
                                        </span>
                                        <span class="inline-flex min-w-0 items-center gap-1.5">
                                            @icon('map-pin', 'w-3.5 h-3.5')
                                            <span class="truncate">{{ $event->location }}</span>
                                        </span>
                                    </div>
                                    <p class="truncate text-sm font-bold text-orange-400 whitespace-nowrap">
                                        À partir de {{ $event->min_price === null ? '—' : money($event->min_price) }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 items-center pr-4">
                                    <span class="grid h-10 w-10 place-items-center rounded-full border border-white/20 bg-white/10 text-white transition group-hover:border-orange-400 group-hover:bg-orange-400 group-hover:text-ink-950">
                                        @icon('arrow-right', 'w-4 h-4')
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Pourquoi nous choisir --}}
            <div class="mt-16 sm:mt-20">
                <div class="grid items-center gap-10 lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
                    {{-- Visuel --}}
                    <div class="relative hidden lg:block">
                        <img src="{{ asset('images/pourquoi-femme-v2.png') }}" alt="" loading="lazy"
                             class="w-full">
                        <div class="absolute left-[72%] top-[10%] rotate-[-6deg] text-white/90">
                            <p class="font-handwritten text-xl font-bold leading-tight">Simple,<br>rapide,<br>sécurisé</p>
                            <svg viewBox="0 0 24 24" class="ml-1 mt-0.5 h-4 w-4 text-orange-400" fill="currentColor" aria-hidden="true">
                                <path d="M5 16 3 6l5.5 4L12 4l3.5 6L21 6l-2 10H5Zm0 2h14v2H5v-2Z"/>
                            </svg>
                            <svg viewBox="0 0 32 24" class="ml-1 h-6 w-8 text-orange-400" fill="none" aria-hidden="true">
                                <path d="M2 2c4 8 12 14 28 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                <path d="M24 14l6 4-6 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-400">Pourquoi nous choisir ?</p>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Une expérience simple<br>et sécurisée</h2>

                        <div class="mt-8 grid gap-6 sm:grid-cols-2">
                            @foreach ([
                                ['icon' => 'zap', 'title' => 'Réservation en quelques clics', 'text' => 'Gagnez du temps et réservez facilement.'],
                                ['icon' => 'smartphone', 'title' => 'Accès mobile', 'text' => "Réservez depuis n'importe où, à tout moment."],
                                ['icon' => 'shield', 'title' => 'Paiement sécurisé', 'text' => 'Vos transactions sont protégées et fiables.'],
                                ['icon' => 'headset', 'title' => 'Service client réactif', 'text' => 'Une équipe à votre écoute 24/7.'],
                            ] as $f)
                                <div class="flex gap-4">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl border-2 border-orange-400/40 bg-orange-400/10 text-orange-400">
                                        @icon($f['icon'], 'w-5 h-5')
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-white">{{ $f['title'] }}</h3>
                                        <p class="mt-1 text-sm text-white/60">{{ $f['text'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- CTA --}}
                <div class="relative mt-12 overflow-hidden rounded-3xl border border-orange-500/25" style="background: #160d08;">
                    <div class="absolute inset-y-0 right-0 hidden w-[55%] sm:block" aria-hidden="true">
                        <img src="{{ asset('images/hero-fans-v2.jpg') }}" alt="" loading="lazy"
                             class="h-full w-full object-cover"
                             style="object-position: center 35%;
                                    -webkit-mask-image: linear-gradient(to right, transparent 0%, #000 35%);
                                    mask-image: linear-gradient(to right, transparent 0%, #000 35%);">
                        <div class="absolute inset-0" style="background: linear-gradient(90deg, #160d08 0%, rgba(22,13,8,.85) 30%, rgba(234,88,12,.45) 70%, rgba(249,115,22,.55) 100%);"></div>
                        <div class="absolute inset-0" style="background: radial-gradient(50% 80% at 70% 50%, rgba(249,115,22,.5), transparent 70%);"></div>
                    </div>

                    <div class="relative z-10 flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8 lg:p-10">
                        <div class="flex items-start gap-4">
                            <svg viewBox="0 0 64 64" class="mt-1 h-11 w-11 shrink-0" fill="none" aria-hidden="true">
                                <g>
                                    <path d="M16 20h34a5 5 0 0 1 5 5v3.5a6.5 6.5 0 0 0 0 13V45a5 5 0 0 1-5 5H16a5 5 0 0 1-5-5v-3.5a6.5 6.5 0 0 0 0-13V25a5 5 0 0 1 5-5Z" fill="#fb923c"/>
                                    <path d="M36 24.5v3.5M36 32v3.5M36 39.5v3.5" stroke="#431407" stroke-width="3.2" stroke-linecap="round" stroke-dasharray=".5 7"/>
                                </g>
                            </svg>
                            <div class="min-w-0">
                                <h3 class="text-lg font-extrabold text-white sm:text-xl">Prêt à vivre des moments inoubliables ?</h3>
                                <p class="mt-1 text-sm text-white/70">Rejoignez des milliers de fans et réservez dès maintenant vos billets !</p>
                            </div>
                        </div>
                        <a href="{{ route('home') }}#evenements" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-orange-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-orange-400">
                            Explorer les événements @icon('arrow-right', 'w-4 h-4')
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>

@endsection