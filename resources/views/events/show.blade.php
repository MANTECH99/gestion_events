@extends('layouts.app', [
    'title' => $event->title,
    'description' => $event->title.' — '.event_date_fr($event->event_date).' à '.$event->location,
    'image' => $event->image_url,
    'ogType' => 'article',
    'schema' => [\App\Support\Seo::event($event)],
])

@section('content')
    @php
        $avgRating = $event->avg_rating;
        $reviewsCount = $event->reviews_count;
        $ratingDistribution = $event->rating_distribution;
        $similarEvents = $event->similarEvents(3);
        $galleryImages = $event->images;
        $activeTicketTypes = $event->ticketTypes->where('is_active', true)->values();
        $pickerTypes = $activeTicketTypes->map(fn ($t) => [
            'id' => $t->id,
            'max' => $event->isOnSale()
                ? ($t->available_online === null ? 20 : min(20, max(0, (int) $t->available_online)))
                : 0,
        ])->values()->all();

        $typeBadges = [
            'concert'    => 'bg-violet-500 text-white',
            'festival'   => 'bg-orange-500 text-white',
            'theatre'    => 'bg-orange-500 text-white',
            'conference' => 'bg-blue-500 text-white',
            'sport'      => 'bg-sky-500 text-white',
            'evening'    => 'bg-pink-500 text-white',
            'other'      => 'bg-slate-500 text-white',
        ];

        $mapsQuery = $event->location;
    @endphp

    {{-- Héro (desktop) --}}
    <section class="relative hidden overflow-hidden bg-ink-950 lg:block">
        <div class="absolute inset-0" aria-hidden="true">
            @if ($event->image_url)
                <img src="{{ $event->image_url }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center">
                <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(10,14,23,.78) 0%, rgba(10,14,23,.72) 45%, rgba(10,14,23,.92) 82%, #0a0e17 100%);"></div>
            @else
                <div class="absolute inset-0" style="background:
                    radial-gradient(48% 46% at 72% 30%, rgba(249,115,22,.28), rgba(249,115,22,0) 70%),
                    radial-gradient(44% 42% at 28% 70%, rgba(56,160,220,.22), rgba(56,160,220,0) 72%),
                    #0a0e17;"></div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-transparent to-ink-950/60"></div>
        </div>

        @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
        <div class="relative mx-auto max-w-7xl px-4 pb-12 {{ $hasFlash ? 'pt-6 sm:pt-8' : 'pt-28 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-32' }} sm:px-6 lg:px-8 lg:pb-16 {{ $hasFlash ? 'lg:pt-10' : 'lg:pt-40' }}">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-500 px-3 py-1.5 text-xs font-bold text-white uppercase tracking-wide shadow-lg shadow-orange-500/25">
                    {{ ticket_type_icon($event->event_type) }} {{ $event->type_label }}
                </span>
                @if (! $event->isPublished())
                    @if ($event->status === 'draft')
                        <span class="rounded-full bg-amber-500 px-3 py-1.5 text-xs font-bold text-white uppercase tracking-wide">Brouillon</span>
                    @else
                        <span class="rounded-full bg-slate-500 px-3 py-1.5 text-xs font-bold text-white uppercase tracking-wide">{{ $event->status_label }}</span>
                    @endif
                @endif
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-white/85 ring-1 ring-white/20 backdrop-blur">
                    @icon('shield', 'w-3.5 h-3.5') Billets officiels
                </span>
            </div>

            <h1 class="mt-6 max-w-4xl text-4xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-5xl lg:text-6xl">
                {{ $event->title }}
            </h1>

            <div class="mt-6 flex flex-wrap items-center gap-2.5 text-sm text-white/85">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-2 ring-1 ring-white/15 backdrop-blur">
                    @icon('calendar', 'w-4 h-4') {{ event_date_fr($event->event_date, 'l j F Y') }}
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-2 ring-1 ring-white/15 backdrop-blur">
                    @icon('clock', 'w-4 h-4') {{ $event->event_date->format('H:i') }}@if ($event->duration_label) · {{ $event->duration_label }}@endif
                </span>
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-2 ring-1 ring-white/15 backdrop-blur">
                    @icon('map-pin', 'w-4 h-4') {{ $event->location }}
                </span>
            </div>

            @if ($event->description)
                <p class="mt-6 max-w-2xl text-sm leading-relaxed text-white/70 sm:text-base">
                    {{ \Illuminate\Support\Str::limit($event->description, 260) }}
                </p>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3" x-data="eventDetailActions(@js([
                'slug' => $event->slug,
                'title' => $event->title,
                'url' => route('events.show', $event),
            ]))">
                <a href="#billets" class="btn-hero-primary px-5 py-3 text-xs sm:px-7 sm:py-3.5 sm:text-sm">
                    @icon('ticket', 'w-5 h-5')
                    @if ($event->isOnSale())
                        Choisir mes billets
                    @elseif ($event->isSoldOut())
                        Billets épuisés
                    @elseif (! $event->online_sales_enabled)
                        Voir les tarifs
                    @else
                        Voir l'événement
                    @endif
                </a>

                <button type="button" @click="share()" class="btn-hero-ghost px-5 py-3 text-xs sm:px-6 sm:py-3.5 sm:text-sm">
                    @icon('share', 'w-4 h-4') Partager
                </button>

                <div class="flex items-center gap-3">
                    <button type="button" @click="toggleFav()"
                            class="grid h-11 w-11 shrink-0 place-items-center rounded-xl border transition"
                            :class="fav ? 'border-rose-400/60 bg-rose-500/15 text-rose-400' : 'border-white/30 text-white hover:bg-white/10'"
                            :aria-label="fav ? 'Retirer des favoris' : 'Ajouter aux favoris'" :title="fav ? 'Retirer des favoris' : 'Ajouter aux favoris'">
                        @icon('heart', 'w-5 h-5')
                    </button>

                    <p class="text-sm font-medium text-white/70">
                        À partir de
                        <span class="text-xl font-extrabold text-orange-400">{{ $event->min_price === null ? '—' : money($event->min_price) }}</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Héro mobile (maquette) --}}
    <section class="relative overflow-hidden bg-ink-950 lg:hidden"
             x-data="eventDetailActions(@js([
                 'slug' => $event->slug,
                 'title' => $event->title,
                 'url' => route('events.show', $event),
             ]))">
        <div class="absolute inset-0" aria-hidden="true">
            @if ($event->image_url)
                <img src="{{ $event->image_url }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center">
                <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(10,14,23,.35) 0%, rgba(10,14,23,.05) 30%, rgba(10,14,23,.70) 74%, #0a0e17 100%);"></div>
            @else
                <div class="absolute inset-0" style="background:
                    radial-gradient(48% 46% at 72% 30%, rgba(249,115,22,.28), rgba(249,115,22,0) 70%),
                    radial-gradient(44% 42% at 28% 70%, rgba(56,160,220,.22), rgba(56,160,220,0) 72%),
                    #0a0e17;"></div>
            @endif
        </div>

        <div class="relative px-4 pb-8 pt-28 [@supports(-webkit-touch-callout:none)]:pt-32 sm:pt-32">
            <div class="flex items-center justify-between">
                <button type="button" @click="history.length > 1 ? history.back() : window.location.href = @js(route('home'))"
                        aria-label="Retour"
                        class="grid h-10 w-10 place-items-center rounded-full bg-black/45 text-white backdrop-blur transition hover:bg-black/60">
                    @icon('arrow-left', 'w-5 h-5')
                </button>
                <div class="flex items-center gap-2.5">
                    <button type="button" @click="share()" aria-label="Partager"
                            class="grid h-10 w-10 place-items-center rounded-full bg-black/45 text-white backdrop-blur transition hover:bg-black/60">
                        @icon('share', 'w-5 h-5')
                    </button>
                    <button type="button" @click="toggleFav()"
                            :aria-label="fav ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                            :title="fav ? 'Retirer des favoris' : 'Ajouter aux favoris'"
                            class="grid h-10 w-10 place-items-center rounded-full bg-black/45 backdrop-blur transition hover:bg-black/60"
                            :class="fav ? 'text-rose-400' : 'text-white'">
                        @icon('heart', 'w-5 h-5')
                    </button>
                </div>
            </div>

            <div class="mt-10 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-bold text-white shadow-lg shadow-emerald-500/25">
                    {{ $event->type_label }}
                </span>
                @if (! $event->isPublished())
                    @if ($event->status === 'draft')
                        <span class="rounded-full bg-amber-500 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">Brouillon</span>
                    @else
                        <span class="rounded-full bg-slate-500 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">{{ $event->status_label }}</span>
                    @endif
                @endif
            </div>

            <h1 class="mt-2.5 text-[27px] font-extrabold leading-[1.08] tracking-tight text-white">
                {{ $event->title }}
            </h1>

            <div class="mt-2.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] font-medium text-white/90">
                <span class="inline-flex items-center gap-1.5">
                    @icon('calendar', 'w-4 h-4 text-white/80') {{ event_date_fr($event->event_date, 'j F Y') }}
                </span>
                <span class="text-white/40" aria-hidden="true">·</span>
                <span>{{ str_replace(':', 'h ', $event->event_date->format('H:i')) }}</span>
                <span class="text-white/40" aria-hidden="true">·</span>
                <span class="inline-flex min-w-0 items-center gap-1.5">
                    @icon('map-pin', 'w-4 h-4 text-white/80')
                    <span class="min-w-0 truncate">{{ $event->location }}</span>
                </span>
            </div>
        </div>
    </section>

    {{-- Statistiques mobile (maquette) --}}
    <section class="bg-ink-950 lg:hidden">
        <div class="px-4">
            <div class="grid grid-cols-3 divide-x divide-white/10 rounded-2xl bg-white/5 py-4">
                <div class="flex items-center justify-center gap-2.5 px-2">
                    <span class="shrink-0 text-amber-400">@icon('star', 'w-5 h-5')</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-extrabold leading-tight text-white">
                            {{ $avgRating !== null ? number_format($avgRating, 1, ',', '') : '—' }}<span class="text-[11px] font-bold text-white/50">/5</span>
                        </p>
                        <p class="mt-0.5 text-[10px] font-medium text-white/45">({{ $reviewsCount }} avis)</p>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-2.5 px-2">
                    <span class="shrink-0 text-white/90">@icon('users', 'w-5 h-5')</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-extrabold leading-tight text-white">{{ $event->tickets_sold_short }}</p>
                        <p class="mt-0.5 text-[10px] font-medium text-white/45">Billets vendus</p>
                    </div>
                </div>
                <div class="flex items-center justify-center gap-2.5 px-2">
                    <span class="shrink-0 text-white/90">@icon('clock', 'w-5 h-5')</span>
                    <div class="min-w-0">
                        <p class="text-[15px] font-extrabold leading-tight text-white">{{ $event->duration_label ?? '—' }}</p>
                        <p class="mt-0.5 text-[10px] font-medium text-white/45">Durée</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- À propos mobile (maquette) + CTA --}}
    <section class="bg-ink-950 lg:hidden">
        <div class="px-4 pt-3">
            @if ($event->description || $event->tags_list !== [])
                <div class="rounded-2xl bg-white/5 p-4">
                    <p class="text-[15px] font-bold text-white">À propos</p>
                    @if ($event->description)
                        <p class="mt-2 text-[13px] leading-relaxed text-white/65">{{ \Illuminate\Support\Str::limit($event->description, 260) }}</p>
                    @endif
                    @if ($event->tags_list !== [])
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($event->tags_list as $i => $tag)
                                <span class="rounded-full border px-3 py-1 text-xs font-semibold {{ $i === 0 ? 'border-emerald-400/60 bg-emerald-500/10 text-emerald-300' : 'border-white/15 bg-white/5 text-white/65' }}">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-4 flex items-center gap-2.5">
                <a href="#billets" class="btn-hero-primary min-w-0 flex-1 whitespace-nowrap px-3 py-3 text-xs">
                    @icon('ticket', 'w-4 h-4')
                    @if ($event->isOnSale())
                        Choisir mes billets
                    @elseif ($event->isSoldOut())
                        Billets épuisés
                    @elseif (! $event->online_sales_enabled)
                        Voir les tarifs
                    @else
                        Voir l'événement
                    @endif
                </a>
                <p class="shrink-0 text-right text-[11px] font-medium leading-tight text-white/60">
                    À partir de
                    <span class="block text-[15px] font-extrabold text-orange-400">{{ $event->min_price === null ? '—' : money($event->min_price) }}</span>
                </p>
            </div>
        </div>
    </section>

    {{-- Barre de statistiques --}}
    <section class="hidden bg-ink-950 lg:block">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl border border-white/10 bg-white/10 lg:grid-cols-4">
                <div class="bg-ink-950 p-5 text-center sm:p-6">
                    <p class="text-2xl font-extrabold text-white sm:text-3xl">{{ $event->tickets_sold_short }}</p>
                    <p class="mt-1 text-xs font-medium uppercase tracking-wide text-white/50">Billets vendus</p>
                </div>
                <div class="bg-ink-950 p-5 text-center sm:p-6">
                    <p class="text-2xl font-extrabold text-white sm:text-3xl">
                        {{ $avgRating !== null ? number_format($avgRating, 1, ',', '') : '—' }}
                        <span class="text-base font-bold text-orange-400">/5</span>
                    </p>
                    <p class="mt-1 text-xs font-medium uppercase tracking-wide text-white/50">
                        {{ $reviewsCount }} avis
                    </p>
                </div>
                <div class="bg-ink-950 p-5 text-center sm:p-6">
                    <p class="text-2xl font-extrabold text-white sm:text-3xl">{{ number_format($event->total_tickets, 0, ',', ' ') }}</p>
                    <p class="mt-1 text-xs font-medium uppercase tracking-wide text-white/50">Places</p>
                </div>
                <div class="bg-ink-950 p-5 text-center sm:p-6">
                    <p class="text-2xl font-extrabold text-white sm:text-3xl">{{ $event->duration_label ?? '—' }}</p>
                    <p class="mt-1 text-xs font-medium uppercase tracking-wide text-white/50">Durée</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Contenu --}}
    <div class="bg-ink-950 pb-16 pt-8 sm:pt-10">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-start gap-6 lg:grid-cols-3">

                {{-- Colonne 1 : à propos, artiste, organisateur --}}
                <div class="order-3 space-y-6 lg:order-1">
                    <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                        <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                            <span class="text-orange-400">@icon('info', 'w-4 h-4')</span>
                            À propos
                        </h2>
                        <div class="mt-4 space-y-3 text-sm leading-relaxed text-white/70">
                            @if ($event->description)
                                {!! nl2br(e($event->description)) !!}
                            @else
                                <p class="italic text-white/45">Aucune description n'a été fournie pour cet événement.</p>
                            @endif
                        </div>

                        @if ($event->tags_list !== [])
                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach ($event->tags_list as $tag)
                                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-white/65">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    @if ($event->artist)
                        <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                            <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                                <span class="text-orange-400">@icon('star', 'w-4 h-4')</span>
                                L'artiste
                            </h2>

                            <div class="mt-4 flex items-center gap-4">
                                @if ($event->artist->image_url)
                                    <img src="{{ $event->artist->image_url }}" alt="{{ $event->artist->name }}"
                                         class="h-14 w-14 shrink-0 rounded-2xl object-cover ring-1 ring-orange-400/30" loading="lazy">
                                @else
                                    <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-orange-400/15 text-lg font-extrabold text-orange-400 ring-1 ring-orange-400/30">
                                        {{ strtoupper(mb_substr($event->artist->name, 0, 1)) }}
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-white">{{ $event->artist->name }}</p>
                                    @if ($event->artist->title)
                                        <p class="mt-0.5 text-sm text-orange-400">{{ $event->artist->title }}</p>
                                    @endif
                                    <p class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-orange-500/15 px-2.5 py-1 text-xs font-semibold text-orange-300 ring-1 ring-orange-400/30">
                                        @icon('check-circle', 'w-3.5 h-3.5') Artiste vérifié
                                    </p>
                                </div>
                            </div>

                            @if ($event->artist->bio)
                                <p class="mt-4 text-sm leading-relaxed text-white/65">{{ \Illuminate\Support\Str::limit($event->artist->bio, 320) }}</p>
                            @endif
                        </section>
                    @endif

                    <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                        <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                            <span class="text-orange-400">@icon('building', 'w-4 h-4')</span>
                            Organisateur
                        </h2>

                        <div class="mt-4 flex items-center gap-4">
                            @if ($event->organizer?->avatar_url)
                                <img src="{{ $event->organizer->avatar_url }}" alt="Profil de {{ $event->organizer_display_name }}"
                                     class="h-14 w-14 shrink-0 rounded-2xl object-cover ring-1 ring-orange-400/30">
                            @else
                                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-orange-400/15 text-lg font-extrabold text-orange-400 ring-1 ring-orange-400/30">
                                    {{ $event->organizer?->initials ?? '?' }}
                                </span>
                            @endif
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-white">Organisé par {{ $event->organizer_display_name }}</p>
                                @if ($event->organizer?->is_verified)
                                    <p class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-orange-500/15 px-2.5 py-1 text-xs font-semibold text-orange-300 ring-1 ring-orange-400/30">
                                        @icon('check-circle', 'w-3.5 h-3.5') Organisateur vérifié
                                    </p>
                                @endif
                                <p class="mt-1.5 text-xs text-white/50">Réservations sécurisées sur Billeterie Express</p>
                            </div>
                        </div>

                        @if ($event->organizer?->bio)
                            <p class="mt-4 text-sm leading-relaxed text-white/65">{{ $event->organizer->bio }}</p>
                        @endif
                    </section>

                    <div class="hidden lg:block">
                        @include('events.partials.review-form', ['reviewId' => 'col1'])
                    </div>
                </div>

                {{-- Colonne 2 : galerie, lieu, avis --}}
                <div class="order-4 space-y-6 lg:order-2">
                    @if ($galleryImages->isNotEmpty())
                        <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6" x-data="{ expanded: false }">
                            <div class="flex items-center justify-between gap-4">
                                <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                                    <span class="text-orange-400">@icon('image', 'w-4 h-4')</span>
                                    Galerie
                                    <span class="text-sm font-medium text-white/45">({{ $galleryImages->count() }})</span>
                                </h2>
                                @if ($galleryImages->count() > 3)
                                    <button type="button" @click="expanded = !expanded"
                                            class="text-sm font-semibold text-orange-400 transition hover:text-orange-300"
                                            x-text="expanded ? 'Réduire' : 'Voir tout'"></button>
                                @endif
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-2">
                                @foreach ($galleryImages as $image)
                                    <a href="{{ $image->image_url }}" target="_blank" rel="noopener"
                                       class="group relative aspect-[4/3] overflow-hidden rounded-xl border border-white/10 bg-white/5"
                                       x-show="{{ $loop->iteration <= 3 ? 'true' : 'expanded' }}">
                                        <img src="{{ $image->image_url }}" alt="Photo {{ $loop->iteration }} de {{ $event->title }}"
                                             loading="lazy"
                                             class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                        <span class="absolute inset-0 bg-ink-950/0 transition group-hover:bg-ink-950/30"></span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                        @if ($event->venue_image_url)
                            <img src="{{ $event->venue_image_url }}" alt="Lieu : {{ $event->location }}"
                                 class="h-36 w-full object-cover" loading="lazy">
                        @endif

                        <div class="p-5 sm:p-6">
                            <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                                <span class="text-orange-400">@icon('map-pin', 'w-4 h-4')</span>
                                Lieu
                            </h2>

                            <p class="mt-3 text-sm font-semibold text-white">{{ $event->location }}</p>

                            <div class="mt-4 overflow-hidden rounded-xl border border-white/10">
                                <iframe src="https://maps.google.com/maps?q={{ urlencode($mapsQuery) }}&hl=fr&z=15&output=embed"
                                        title="Carte : {{ $event->location }}"
                                        class="h-56 w-full grayscale-[.35]" style="border:0" loading="lazy"
                                        referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                            </div>

                            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($mapsQuery) }}"
                               target="_blank" rel="noopener"
                               class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-orange-400 transition hover:text-orange-300">
                                Ouvrir dans Google Maps
                                <span>@icon('arrow-up-right', 'w-4 h-4')</span>
                            </a>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-4">
                            <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                                <span class="text-orange-400">@icon('star', 'w-4 h-4')</span>
                                Avis
                                <span class="text-sm font-medium text-white/45">({{ $reviewsCount }})</span>
                            </h2>
                        </div>

                        <div class="lg:hidden">
                            @include('events.partials.review-form', ['reviewId' => 'avis'])
                        </div>

                        @if ($reviewsCount === 0)
                            <div class="mt-4 rounded-xl border border-dashed border-white/15 bg-white/[.03] px-4 py-8 text-center">
                                <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-white/10 text-white/60">@icon('star', 'w-6 h-6')</span>
                                <p class="mt-3 text-sm font-semibold text-white">Aucun avis pour le moment</p>
                                <p class="mx-auto mt-1 max-w-xs text-xs leading-relaxed text-white/50">
                                    Après l'événement, les participants pourront partager leur expérience ici.
                                </p>
                            </div>
                        @else
                            <div class="mt-4 flex items-center gap-5">
                                <div class="text-center">
                                    <p class="text-4xl font-extrabold text-white">{{ number_format($avgRating, 1, ',', '') }}</p>
                                    <p class="mt-1 text-xs font-medium uppercase tracking-wide text-white/50">sur 5</p>
                                </div>

                                <div class="min-w-0 flex-1 space-y-1.5">
                                    @for ($stars = 5; $stars >= 1; $stars--)
                                        @php $pct = $ratingDistribution[$stars] ?? 0; @endphp
                                        <div class="flex items-center gap-2 text-[11px] text-white/55">
                                            <span class="w-3 text-right font-medium">{{ $stars }}</span>
                                            <span class="text-orange-400/80">@icon('star', 'w-3 h-3')</span>
                                            <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-white/10">
                                                <span class="block h-full rounded-full bg-orange-400" style="width: {{ $pct }}%"></span>
                                            </span>
                                            <span class="w-8 text-right font-medium text-white/70">{{ $pct }}%</span>
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            <ul class="mt-5 space-y-3">
                                @foreach ($event->reviews->take(4) as $review)
                                    <li class="rounded-xl border border-white/10 bg-white/[.03] p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="flex min-w-0 items-center gap-3">
                                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-orange-400/15 text-sm font-bold text-orange-400 ring-1 ring-orange-400/25">
                                                    {{ strtoupper(mb_substr($review->author_name, 0, 1)) }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="truncate text-sm font-semibold text-white">{{ $review->author_name }}</p>
                                                    <p class="text-xs text-white/45">{{ $review->created_at->diffForHumans() }}</p>
                                                </div>
                                            </div>
                                            <span class="flex shrink-0 items-center gap-0.5 rounded-full bg-orange-500/15 px-2.5 py-1 text-xs font-bold text-orange-300 ring-1 ring-orange-400/25">
                                                {{ $review->rating }}
                                                <span class="ml-0.5 text-orange-400">@icon('star', 'w-3 h-3')</span>
                                            </span>
                                        </div>
                                        @if ($review->comment)
                                            <p class="mt-2.5 text-sm leading-relaxed text-white/65">{{ $review->comment }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>

                            @if ($reviewsCount > 4)
                                <p class="mt-4 text-center text-xs text-white/45">
                                    Affichage des 4 derniers avis sur {{ $reviewsCount }}.
                                </p>
                            @endif
                        @endif
                    </section>
                </div>

                {{-- Colonne 3 : billets + compte à rebours (en premier sur mobile) --}}
                <div class="order-2 space-y-6 lg:order-3 lg:sticky lg:top-24"
                     x-data="ticketPicker({ types: @js($pickerTypes), url: @js(route('booking.create', $event)) })">
                    <section id="billets" class="scroll-mt-24 rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                        <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                            <span class="text-orange-400">@icon('ticket', 'w-4 h-4')</span>
                            Choisir vos billets
                        </h2>
                        <p class="mt-1 text-xs text-white/50">Sélectionnez vos quantités, puis réservez en un clic.</p>

                        @if ($activeTicketTypes->isEmpty())
                            <div class="mt-4 rounded-xl border border-dashed border-white/15 px-4 py-8 text-center">
                                <p class="text-sm font-semibold text-white">Aucun type de ticket disponible pour le moment.</p>
                                <p class="mt-1 text-xs text-white/50">Revenez bientôt ou contactez l'organisateur.</p>
                            </div>
                        @elseif (! $event->isOnSale() && ! $event->isSoldOut() && $event->online_sales_enabled && $event->event_date->isPast())
                            <div class="mt-4 rounded-xl border border-white/10 bg-white/[.03] px-4 py-6 text-center">
                                <p class="text-sm font-semibold text-white">Événement passé</p>
                                <p class="mt-1 text-xs text-white/50">Les réservations en ligne sont fermées.</p>
                            </div>
                        @else
                            <div class="mt-4 space-y-3">
                                @foreach ($activeTicketTypes as $type)
                                    @php
                                        $available = $type->available_online;
                                        $maxQty = $available === null ? 20 : min(20, max(0, $available));
                                        $pickable = $event->isOnSale() && $maxQty > 0;
                                        $gate = $pickable ? 'false' : 'true';
                                    @endphp
                                    <div class="rounded-xl border border-white/10 bg-white/[.04] p-4 transition hover:border-white/25">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="flex items-center gap-2 text-sm font-bold text-white">
                                                    <span class="text-orange-400">@icon('ticket', 'w-4 h-4')</span>
                                                    {{ $type->name }}
                                                </p>
                                                @if ($type->description)
                                                    <p class="mt-1 text-xs leading-relaxed text-white/55">{{ $type->description }}</p>
                                                @endif
                                                <p class="mt-1.5 text-[11px] font-medium {{ $available === 0 ? 'text-rose-400' : 'text-white/45' }}">
                                                    @if ($available === null)
                                                        Stock illimité
                                                    @elseif ($available > 0)
                                                        {{ $available }} ticket(s) disponible(s) en ligne
                                                    @else
                                                        Rupture de stock
                                                    @endif
                                                </p>
                                            </div>
                                            <p class="shrink-0 text-lg font-extrabold text-orange-400">{{ money($type->price) }}</p>
                                        </div>

                                        <div class="mt-3 flex items-center gap-2">
                                            <button type="button" aria-label="Diminuer la quantité pour {{ $type->name }}"
                                                    @click="dec({{ $type->id }})"
                                                    :disabled="{{ $gate }} || qtyOf({{ $type->id }}) <= 0"
                                                    class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-white/15 bg-white/5 text-white transition enabled:hover:border-orange-400/60 enabled:hover:text-orange-300 disabled:cursor-not-allowed disabled:opacity-40">
                                                @icon('minus', 'w-4 h-4')
                                            </button>

                                            <input type="number" x-model.number="qtys[{{ $type->id }}]"
                                                   value="0" min="0" max="{{ $maxQty }}"
                                                   @disabled(! $pickable)
                                                   aria-label="Quantité pour {{ $type->name }}"
                                                   class="h-10 w-16 min-w-0 flex-1 rounded-lg border border-white/15 bg-white/5 text-center text-sm font-semibold text-white focus:border-orange-400 focus:ring-orange-400">

                                            <button type="button" aria-label="Augmenter la quantité pour {{ $type->name }}"
                                                    @click="inc({{ $type->id }})"
                                                    :disabled="{{ $gate }} || qtyOf({{ $type->id }}) >= {{ $maxQty }}"
                                                    class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-white/15 bg-white/5 text-white transition enabled:hover:border-orange-400/60 enabled:hover:text-orange-300 disabled:cursor-not-allowed disabled:opacity-40">
                                                @icon('plus', 'w-4 h-4')
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-5 space-y-2.5 border-t border-white/10 pt-5 text-sm">
                                @foreach ([
                                    ['Vérification instantanée du billet', 'check-circle'],
                                    ['Paiement Wave ou Orange Money', 'smartphone'],
                                    ['Réservation avec ou sans compte', 'user'],
                                ] as [$text, $icon])
                                    <p class="flex items-center gap-2 text-white/70">
                                        <span class="text-orange-400">@icon($icon, 'w-4 h-4')</span>{{ $text }}
                                    </p>
                                @endforeach
                            </div>

                            @if ($event->isSoldOut())
                                <p class="mt-4 rounded-xl border border-rose-400/40 bg-rose-500/10 px-3 py-2.5 text-center text-xs font-semibold text-rose-300">
                                    Billets épuisés — cet événement affiche complet.
                                </p>
                            @elseif (! $event->online_sales_enabled)
                                <p class="mt-4 rounded-xl border border-amber-400/40 bg-amber-500/10 px-3 py-2.5 text-center text-xs font-semibold text-amber-300">
                                    Vente en boutique uniquement — billets disponibles en point de vente.
                                </p>
                            @endif
                        @endif

                        <div class="mt-5 flex items-start gap-2 rounded-xl border border-white/10 bg-white/[.03] p-3.5 text-[11px] leading-relaxed text-white/50">
                            <span class="mt-0.5 shrink-0 text-orange-400">@icon('shield', 'w-4 h-4')</span>
                            <span>
                                Paiement traité par <strong class="font-semibold text-white/80">DexPay (DEXCHANGE PAY)</strong>.
                                Aucun ticket n'est généré avant confirmation du paiement.
                            </span>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-orange-500/25 bg-gradient-to-b from-orange-500/10 to-transparent p-5 sm:p-6"
                             x-data="eventCountdown(@js($event->event_date->toIso8601String()))">
                        <h2 class="text-lg font-bold text-white">Ne rate pas cet événement</h2>

                        <div x-show="!expired" x-cloak>
                            <p class="mt-1 text-xs font-medium uppercase tracking-wide text-white/50">Commence dans</p>
                            <div class="mt-3 grid grid-cols-4 gap-2 text-center">
                                <div class="rounded-xl border border-white/10 bg-white/5 py-2.5">
                                    <p class="text-xl font-extrabold text-white sm:text-2xl" x-text="days">00</p>
                                    <p class="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-white/45">Jours</p>
                                </div>
                                <div class="rounded-xl border border-white/10 bg-white/5 py-2.5">
                                    <p class="text-xl font-extrabold text-white sm:text-2xl" x-text="hours">00</p>
                                    <p class="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-white/45">Heures</p>
                                </div>
                                <div class="rounded-xl border border-white/10 bg-white/5 py-2.5">
                                    <p class="text-xl font-extrabold text-white sm:text-2xl" x-text="minutes">00</p>
                                    <p class="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-white/45">Min</p>
                                </div>
                                <div class="rounded-xl border border-white/10 bg-white/5 py-2.5">
                                    <p class="text-xl font-extrabold text-orange-400 sm:text-2xl" x-text="seconds">00</p>
                                    <p class="mt-0.5 text-[10px] font-medium uppercase tracking-wide text-white/45">Sec</p>
                                </div>
                            </div>
                        </div>

                        <div x-show="expired" x-cloak class="mt-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-center">
                            <p class="text-sm font-semibold text-white">
                                @if ($event->event_date->isFuture())
                                    Le compte à rebours est terminé — c'est maintenant !
                                @else
                                    Cet événement a eu lieu. Suivez-nous pour la suite !
                                @endif
                            </p>
                        </div>

                        @if ($event->isOnSale())
                            <a href="{{ route('booking.create', $event) }}" :href="reserveUrl()"
                               class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-ink-950 transition hover:bg-orange-100">
                                @icon('ticket', 'w-4 h-4') Réserver maintenant
                            </a>
                            <p class="mt-2 text-center text-[11px] text-white/45">Places limitées — paiement sécurisé.</p>
                        @endif
                    </section>
                </div>
            </div>

            {{-- Événements similaires --}}
            @if ($similarEvents->isNotEmpty())
                <section class="mt-12">
                    <div class="flex items-end justify-between gap-4">
                        <h2 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                            Événements <span class="text-orange-400">similaires</span>
                        </h2>
                        <a href="{{ route('home') }}#evenements" class="group inline-flex shrink-0 items-center gap-1.5 text-sm font-semibold text-orange-400 transition hover:text-orange-300">
                            Voir tout
                            <span class="transition group-hover:translate-x-0.5">@icon('arrow-right', 'w-4 h-4')</span>
                        </a>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-3">
                        @foreach ($similarEvents as $similar)
                            <a href="{{ route('events.show', $similar) }}"
                               class="group flex overflow-hidden rounded-2xl border border-white/10 bg-white/5 transition hover:border-white/25">
                                <div class="relative w-[36%] shrink-0 sm:w-[38%]">
                                    @if ($similar->image_url)
                                        <img src="{{ $similar->image_url }}" alt="{{ $similar->title }}" loading="lazy"
                                             class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @else
                                        <div class="absolute inset-0 grid place-items-center gradient-brand text-3xl">{{ ticket_type_icon($similar->event_type) }}</div>
                                    @endif
                                    <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(10,13,20,.25) 0%, rgba(10,13,20,0) 55%);"></div>
                                </div>

                                <div class="flex min-w-0 flex-1 flex-col justify-center gap-1.5 px-4 py-3.5">
                                    <span class="w-fit rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $typeBadges[$similar->event_type] ?? $typeBadges['other'] }}">
                                        {{ $similar->type_label }}
                                    </span>
                                    <h3 class="line-clamp-1 text-base font-bold text-white sm:text-lg">{{ $similar->title }}</h3>
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-medium text-white/75">
                                        <span class="inline-flex items-center gap-1.5">
                                            @icon('calendar', 'w-3.5 h-3.5')
                                            {{ ucfirst(event_date_fr($similar->event_date, 'd F Y')) }}
                                        </span>
                                        <span class="inline-flex min-w-0 items-center gap-1.5">
                                            @icon('map-pin', 'w-3.5 h-3.5')
                                            <span class="min-w-0 truncate">{{ $similar->location }}</span>
                                        </span>
                                    </div>
                                    <p class="text-sm font-bold text-orange-400">
                                        À partir de {{ $similar->min_price === null ? '—' : money($similar->min_price) }}
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
                </section>
            @endif
        </div>
    </div>
@endsection
