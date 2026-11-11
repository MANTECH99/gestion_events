@extends('layouts.app', ['title' => 'Espace agent'])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
    <div class="mx-auto max-w-7xl px-4 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-24 sm:pt-28' }} sm:px-6 lg:px-8 pb-28 lg:pb-8">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Espace agent</h1>
                <p class="mt-1 text-sm text-white/60">
                    Tickets valides{{ $event ? ' pour « '.$event->title.' »' : '' }}
                </p>
            </div>

            {{-- Une seule ligne sur mobile : filtre à gauche (il rétrécit), Scanner figé à droite. --}}
            <div class="flex w-full items-center gap-2 sm:w-auto">
                @if ($events->count() > 1)
                    <form method="GET" class="flex min-w-0 flex-1 items-center gap-2">
                        @if ($liste === 'utilises')
                            <input type="hidden" name="liste" value="utilises">
                        @endif
                        @if ($q !== '')
                            <input type="hidden" name="q" value="{{ $q }}">
                        @endif
                        <select name="event" onchange="this.form.submit()" class="input-dark py-2 text-sm">
                            <option value="">Tous les événements</option>
                            @foreach ($events as $e)
                                <option value="{{ $e->id }}" @selected($event?->id === $e->id)>
                                    {{ $e->title }}
                                </option>
                            @endforeach
                        </select>
                        <noscript><button class="btn-invert btn-sm !px-3 !py-1.5 text-xs">Filtrer</button></noscript>
                    </form>
                @endif

                <a href="{{ route('agent.scan', $event ? ['event' => $event->id] : []) }}" class="btn-hero-primary btn-sm shrink-0 !px-3 !py-1.5 text-xs">
                    @icon('scan', 'w-4 h-4') Scanner
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            @include('partials.stat', [
                'label' => 'Billets valides',
                'value' => $validCount,
                'icon' => 'ticket',
                'tone' => 'brand',
                'dark' => true,
            ])
            @include('partials.stat', [
                'label' => 'Déjà utilisés',
                'value' => $usedCount,
                'icon' => 'check-circle',
                'tone' => 'emerald',
                'dark' => true,
            ])
            @include('partials.stat', [
                'label' => 'Événements',
                'value' => $events->count(),
                'icon' => 'calendar',
                'tone' => 'orange',
                'dark' => true,
            ])
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/5" x-data="ticketSearch">
            {{-- Filtre des listes : attente d'entrée (défaut) ou déjà utilisés. --}}
            <form method="GET" class="px-5 pt-4">
                @if ($event)
                    <input type="hidden" name="event" value="{{ $event->id }}">
                @endif
                <select name="liste" onchange="this.form.submit()" class="input-dark py-2 text-sm sm:max-w-xs">
                    <option value="" @selected($liste !== 'utilises')>Billets en attente d'entrée</option>
                    <option value="utilises" @selected($liste === 'utilises')>Billets déjà utilisés</option>
                </select>

                {{-- Recherche live : le tableau est rechargé au fil de la
                     frappe (debounce 250 ms) sans perdre le focus. --}}
                <div class="relative mt-2">
                    @icon('search', 'pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/40')
                    <input type="search"
                           name="q"
                           value="{{ $q }}"
                           x-model.debounce.250ms="query"
                           placeholder="Rechercher : code, nom, référence…"
                           autocomplete="off"
                           aria-label="Rechercher un billet"
                           class="input-dark py-2 pl-9 text-sm">
                </div>
            </form>

            <div class="mt-3 flex items-center justify-between border-b border-white/10 px-5 py-4">
                <h2 class="font-semibold text-white">{{ $liste === 'utilises' ? 'Billets déjà utilisés' : "Billets en attente d'entrée" }}</h2>
                <span class="max-sm:hidden text-xs text-white/50">Les billets sont à usage unique.</span>
            </div>

            {{-- Résultats remplacés en direct par ticket-search.js (le
                 champ de recherche reste hors conteneur : focus conservé). --}}
            <div id="liste-billets" :class="busy ? 'opacity-60' : ''">
                @if ($q !== '')
                    <p class="border-b border-white/10 px-5 py-3 text-sm text-white/60">
                        {{ $tickets->total() }} résultat{{ $tickets->total() === 1 ? '' : 's' }} pour « {{ $q }} »
                    </p>
                @endif

                @if ($tickets->isEmpty())
                    @if ($q !== '')
                        @include('partials.empty', [
                            'icon' => 'search',
                            'title' => 'Aucun résultat',
                            'message' => 'Aucun billet ne correspond à votre recherche.',
                            'dark' => true,
                        ])
                    @else
                        @include('partials.empty', [
                            'icon' => 'ticket',
                            'title' => $liste === 'utilises' ? 'Aucun billet utilisé' : 'Aucun billet valide',
                            'message' => $liste === 'utilises'
                                ? 'Les billets validés à l\'entrée apparaîtront ici.'
                                : 'Les billets apparaîtront ici après confirmation du paiement.',
                            'dark' => true,
                        ])
                    @endif
                @else
                    <div class="overflow-x-auto">
                        {{-- max-sm:min-w-0 : sur mobile, le tableau prend la largeur
                             disponible (sinon le min-w de 42rem force un défilement
                             horizontal jusqu'à la colonne Actions). --}}
                        <table class="table table-dark max-sm:min-w-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Titulaire</th>
                                    {{-- La liste « déjà utilisés » n'affiche que
                                         Code, Titulaire et Actions. --}}
                                    @if ($liste !== 'utilises')
                                        <th class="hidden sm:table-cell">Type</th>
                                        <th class="hidden sm:table-cell">Réservation</th>
                                    @endif
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tickets as $ticket)
                                    <tr>
                                        <td class="font-mono text-xs font-semibold text-white">{{ $ticket->code }}</td>
                                        <td>
                                            <p class="font-medium text-white">{{ $ticket->holder_name }}</p>
                                            {{-- Email masqué : non obligatoire. --}}
                                        </td>
                                        @if ($liste !== 'utilises')
                                            <td class="hidden sm:table-cell"><span class="badge bg-white/10 text-white/80">{{ $ticket->ticketType->name }}</span></td>
                                            <td class="hidden sm:table-cell font-mono text-xs text-white/60">{{ $ticket->reservation->reference }}</td>
                                        @endif
                                        <td class="text-right">
                                            <a href="{{ route('tickets.show', $ticket) }}" class="btn-invert btn-sm !px-3 !py-1.5 text-xs">
                                                @icon('eye', 'w-4 h-4') Voir
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-white/10 px-5 py-3">
                        {{ $tickets->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
