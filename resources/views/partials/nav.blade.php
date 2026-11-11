@php
    $user = auth()->user();
    $hasSidebar = $hasSidebar ?? false;
    $onHome = request()->routeIs('home');
    // Espaces de gestion : sombre par défaut, basculable en clair via le cookie « theme ».
    $backoffice = request()->routeIs('organizer.*', 'admin.*', 'treasurer.*');
    $themeDark = $backoffice && request()->cookie('theme') !== 'light';
    // Header transparent sur l'accueil, la page détail d'événement et la page de réservation (thèmes sombres).
    $darkHeader = request()->routeIs('home', 'events.show', 'booking.create', 'payment.show', 'login', 'register', 'reservations.index', 'reservations.show', 'tickets.show', 'tickets.reservation', 'pos.dashboard', 'agent.index', 'agent.scan', 'favoris.*', 'profil.*', 'partners.*') || $themeDark;
@endphp

<header class="app-header no-print z-50 backdrop-blur-lg {{ $darkHeader ? 'absolute inset-x-0 top-0 bg-transparent' : 'sticky top-0 border-b border-ink-200/60 bg-white/85' }}">
    {{-- Sur les interfaces de gestion, la colonne fixe occupe la bande gauche du header. --}}
    <div class="{{ $hasSidebar ? 'lg:pl-64' : '' }}">
        <div class="relative flex h-16 {{ $hasSidebar ? 'w-full' : 'mx-auto max-w-7xl' }} items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <img src="{{ asset('images/icon.svg') }}?v={{ config('app.asset_version') }}" alt="" width="36" height="36"
                     class="h-9 w-9 shrink-0 rounded-xl shadow-lg {{ $darkHeader ? 'shadow-black/40' : 'shadow-orange-500/40' }}">
                <span class="text-lg font-extrabold tracking-tight {{ $darkHeader ? 'text-white' : 'text-ink-950' }}">
                    Billeterie <span class="{{ $darkHeader ? 'text-white' : 'text-gradient' }}">Express</span>
                </span>
            </a>

            @if ($darkHeader)
                @php $homeAnchor = $onHome ? '' : route('home'); @endphp
                <nav class="ml-4 hidden items-center gap-1 lg:flex">
                    <a href="{{ route('home') }}"
                       class="{{ $onHome ? 'border-b-2 border-orange-400' : 'border-b-2 border-transparent' }} px-3 py-2 text-sm font-medium text-white transition">
                        Accueil
                    </a>
                    <a href="{{ $homeAnchor }}#evenements"
                       class="{{ request()->routeIs('events.show') ? 'border-b-2 border-orange-400' : 'border-b-2 border-transparent' }} rounded-lg px-3 py-2 text-sm font-medium text-white/75 transition hover:bg-white/10 hover:text-white">
                        Événements
                    </a>
                    <a href="{{ $homeAnchor }}#evenements"
                       class="border-b-2 border-transparent rounded-lg px-3 py-2 text-sm font-medium text-white/75 transition hover:bg-white/10 hover:text-white">
                        Catégories
                    </a>
                    <a href="{{ $homeAnchor }}#apropos"
                       class="border-b-2 border-transparent rounded-lg px-3 py-2 text-sm font-medium text-white/75 transition hover:bg-white/10 hover:text-white">
                        À propos
                    </a>
                    <a href="{{ route('partners.become') }}"
                       class="{{ request()->routeIs('partners.*') ? 'border-b-2 border-orange-400' : 'border-b-2 border-transparent' }} rounded-lg px-3 py-2 text-sm font-medium text-white/75 transition hover:bg-white/10 hover:text-white">
                        Partenariat
                    </a>
                </nav>
            @else
            <nav class="ml-4 hidden items-center gap-1 lg:flex">
                <a href="{{ route('home') }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-ink-100 hover:text-ink-900 {{ request()->routeIs('home') ? 'bg-ink-100 text-ink-900' : '' }}">
                    Événements
                </a>

                <a href="{{ route('partners.become') }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-ink-100 hover:text-ink-900 {{ request()->routeIs('partners.*') ? 'bg-ink-100 text-ink-900' : '' }}">
                    Partenariat
                </a>

                @auth
                    <a href="{{ route('reservations.index') }}"
                       class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-ink-100 hover:text-ink-900 {{ request()->routeIs('reservations.*') ? 'bg-ink-100 text-ink-900' : '' }}">
                        Mes réservations
                    </a>

                    @if ($user->isAgent())
                        <a href="{{ route('agent.index') }}"
                           class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-ink-100 hover:text-ink-900 {{ request()->routeIs('agent.*') ? 'bg-ink-100 text-ink-900' : '' }}">
                            Espace agent
                        </a>
                    @endif

                    @if ($user->isManagedPointOfSale())
                        <a href="{{ route('pos.dashboard') }}"
                           class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-ink-100 hover:text-ink-900 {{ request()->routeIs('pos.*') ? 'bg-ink-100 text-ink-900' : '' }}">
                            Point de vente
                        </a>
                    @endif
                @endauth
            </nav>
            @endif

            <div class="ml-auto flex items-center gap-2">
                @auth
                    @if ($user->isOrganizer() || $user->isAdmin())
                        <a href="{{ route('organizer.dashboard') }}" class="btn btn-sm hidden sm:inline-flex {{ $darkHeader ? 'bg-orange-400 text-ink-950 shadow-lg shadow-orange-400/25 hover:bg-orange-300' : 'btn-primary' }}">
                            @icon('chart-bar', 'w-4 h-4') Tableau de bord
                        </a>
                    @endif

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 rounded-xl border py-1.5 pl-1.5 pr-2.5 text-sm transition {{ $darkHeader ? 'border-orange-400/50 bg-orange-400/15 text-white hover:bg-orange-400/25' : 'border-orange-300 bg-orange-50 text-orange-700 hover:bg-orange-100' }}">
                            <span class="grid h-7 w-7 place-items-center rounded-lg text-xs font-bold {{ $darkHeader ? 'bg-orange-500 text-white' : 'bg-orange-500 text-white' }}">
                                {{ $user->initials }}
                            </span>
                            <span class="hidden max-w-[9rem] truncate font-medium sm:block {{ $darkHeader ? 'text-white' : 'text-ink-800' }}">{{ $user->first_name }}</span>
                            <span class="{{ $darkHeader ? 'text-orange-300' : 'text-orange-500' }}">@icon('chevron-down', 'w-4 h-4')</span>
                        </button>

                        <div x-cloak x-show="open" x-transition
                             class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border shadow-xl {{ $darkHeader ? 'border-white/15 bg-ink-950/95 backdrop-blur-lg' : 'border-ink-200 bg-white' }}">
                            <div class="border-b px-4 py-3 {{ $darkHeader ? 'border-white/10' : 'border-ink-100' }}">
                                <p class="truncate text-sm font-semibold {{ $darkHeader ? 'text-white' : 'text-ink-900' }}">{{ $user->full_name }}</p>
                                <p class="truncate text-xs {{ $darkHeader ? 'text-white/50' : 'text-ink-500' }}">{{ $user->email }}</p>
                                <span class="badge mt-2 {{ $darkHeader ? 'bg-orange-500/20 text-orange-300' : 'bg-brand-50 text-brand-700' }}">{{ $user->role_label }}</span>
                            </div>
                            <div class="p-1.5 text-sm">
                                <a href="{{ route('reservations.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 {{ $darkHeader ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-ink-700 hover:bg-ink-50' }}">
                                    @icon('ticket', 'w-4 h-4') Mes réservations
                                </a>
                                @if ($user->isManagedPointOfSale())
                                    <a href="{{ route('pos.dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 {{ $darkHeader ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-ink-700 hover:bg-ink-50' }}">
                                        @icon('store', 'w-4 h-4') Point de vente
                                    </a>
                                @endif
                                @if ($user->isAgent())
                                    <a href="{{ route('agent.scan') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 {{ $darkHeader ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-ink-700 hover:bg-ink-50' }}">
                                        @icon('scan', 'w-4 h-4') Scanner un ticket
                                    </a>
                                @endif
                                @if ($user->isTreasurer())
                                    <a href="{{ route('treasurer.payouts.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 {{ $darkHeader ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-ink-700 hover:bg-ink-50' }}">
                                        @icon('wallet', 'w-4 h-4') Espace trésorière
                                    </a>
                                @endif
                                @if ($user->isOrganizer() || $user->isAdmin())
                                    <a href="{{ route('organizer.events.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 {{ $darkHeader ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-ink-700 hover:bg-ink-50' }}">
                                        @icon('calendar', 'w-4 h-4') Mes événements
                                    </a>
                                    <a href="{{ route('organizer.stocks.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 {{ $darkHeader ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-ink-700 hover:bg-ink-50' }}">
                                        @icon('coins', 'w-4 h-4') Stock physique
                                    </a>
                                @endif
                                @if ($user->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 {{ $darkHeader ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-ink-700 hover:bg-ink-50' }}">
                                        @icon('shield', 'w-4 h-4') Administration
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t pt-1 {{ $darkHeader ? 'border-white/10' : 'border-ink-100' }}">
                                    @csrf
                                    <button class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left {{ $darkHeader ? 'text-rose-400 hover:bg-rose-500/15' : 'text-rose-600 hover:bg-rose-50' }}">
                                        @icon('logout', 'w-4 h-4') Se déconnecter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    @if ($backoffice)
                        <form method="POST" action="{{ route('theme.update') }}">
                            @csrf
                            <button type="submit"
                                    class="hidden h-9 w-9 place-items-center rounded-xl border transition sm:grid {{ $darkHeader ? 'border-white/25 bg-white/10 text-white hover:bg-white/20' : 'border-ink-200 bg-white text-ink-700 hover:border-ink-300' }}"
                                    title="{{ $darkHeader ? 'Passer en mode clair' : 'Passer en mode sombre' }}"
                                    aria-label="{{ $darkHeader ? 'Passer en mode clair' : 'Passer en mode sombre' }}">
                                @icon($darkHeader ? 'sun' : 'moon', 'w-5 h-5')
                            </button>
                        </form>
                    @endif
                @else
                    @if ($darkHeader)
                        <a href="{{ $homeAnchor }}#evenements" class="hidden h-9 w-9 place-items-center rounded-xl text-white/80 transition hover:bg-white/10 hover:text-white sm:grid"
                           aria-label="Rechercher un événement">
                            @icon('search', 'w-5 h-5')
                        </a>
                        {{-- Desktop : inchangé (Se connecter + S'inscrire). Mobile : « Connexion ». --}}
                        <a href="{{ route('login') }}"
                           class="btn btn-sm hidden border border-white/30 text-white hover:bg-white/10 focus-visible:ring-white focus-visible:ring-offset-0 sm:inline-flex">
                            Se connecter
                        </a>
                        <a href="{{ route('login') }}"
                           class="btn btn-sm bg-orange-400 text-ink-950 shadow-lg shadow-orange-400/25 hover:bg-orange-300 sm:hidden">
                            Connexion
                        </a>
                        <a href="{{ route('register') }}"
                           class="btn btn-sm hidden bg-orange-400 text-ink-950 shadow-lg shadow-orange-400/25 hover:bg-orange-300 sm:inline-flex">
                            S'inscrire
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost btn-sm hidden sm:inline-flex">Connexion</a>
                        <a href="{{ route('login') }}" class="btn-primary btn-sm sm:hidden">Connexion</a>
                        <a href="{{ route('register') }}" class="btn-primary btn-sm hidden sm:inline-flex">S'inscrire</a>
                    @endif
                @endauth
            </div>
        </div>
    </div>
</header>