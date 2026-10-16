@php
    $user = auth()->user();
    $hasSidebar = $hasSidebar ?? false;
    $onHome = request()->routeIs('home');
    // Header transparent sur l'accueil, la page détail d'événement et la page de réservation (thèmes sombres).
    $darkHeader = request()->routeIs('home', 'events.show', 'booking.create', 'payment.show', 'login', 'register');
@endphp

<header class="app-header no-print z-50 backdrop-blur-lg {{ $darkHeader ? 'absolute inset-x-0 top-0 bg-transparent' : 'sticky top-0 border-b border-ink-200/60 bg-white/85' }}">
    {{-- Sur les interfaces de gestion, la colonne fixe occupe la bande gauche du header. --}}
    <div class="{{ $hasSidebar ? 'lg:pl-64' : '' }}">
        <div x-data="{ open: false }" @click.outside="open = false" class="relative flex h-16 {{ $hasSidebar ? 'w-full' : 'mx-auto max-w-7xl' }} items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <img src="{{ asset('images/icon.svg') }}" alt="" width="36" height="36"
                     class="h-9 w-9 shrink-0 rounded-xl shadow-lg {{ $darkHeader ? 'shadow-black/40' : 'shadow-orange-500/40' }}">
                <span class="text-lg font-extrabold tracking-tight {{ $darkHeader ? 'text-white' : 'text-ink-950' }}">
                    Billeterie <span class="{{ $darkHeader ? 'text-white' : 'text-gradient' }}">Express</span>
                </span>
            </a>

            {{-- Menu mobile : la navigation principale est masquée sous lg --}}
            <div class="lg:hidden">
                <button @click="open = !open"
                        class="grid h-9 w-9 place-items-center rounded-xl border transition {{ $darkHeader ? 'border-white/25 bg-white/10 text-white hover:bg-white/20' : 'border-ink-200 bg-white text-ink-700 hover:border-ink-300' }}"
                        :aria-expanded="open ? 'true' : 'false'" aria-controls="menu-mobile" aria-label="Ouvrir le menu">
                    <span x-show="!open">@icon('menu', 'w-5 h-5')</span>
                    <span x-cloak x-show="open">@icon('close', 'w-5 h-5')</span>
                </button>

                <div id="menu-mobile" x-cloak x-show="open" x-transition
                     class="absolute left-4 top-full mt-px w-64 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-xl">
                    <div class="p-1.5 text-sm">
                        <a href="{{ route('home') }}"
                           class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-medium text-ink-700 hover:bg-ink-50 {{ request()->routeIs('home') ? 'bg-brand-50 text-brand-700' : '' }}">
                            @icon('ticket', 'w-4 h-4') Événements
                        </a>

                        @auth
                            <a href="{{ route('reservations.index') }}"
                               class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-medium text-ink-700 hover:bg-ink-50 {{ request()->routeIs('reservations.*') ? 'bg-brand-50 text-brand-700' : '' }}">
                                @icon('calendar', 'w-4 h-4') Mes réservations
                            </a>

                            @if ($user->isAgent())
                                <a href="{{ route('agent.index') }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-medium text-ink-700 hover:bg-ink-50 {{ request()->routeIs('agent.*') ? 'bg-brand-50 text-brand-700' : '' }}">
                                    @icon('scan', 'w-4 h-4') Espace agent
                                </a>
                            @endif

                            @if ($user->isOrganizer() || $user->isAdmin())
                                <a href="{{ route('organizer.dashboard') }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-medium text-ink-700 hover:bg-ink-50">
                                    @icon('chart-bar', 'w-4 h-4') Tableau de bord
                                </a>
                            @endif

                            @if ($user->isManagedPointOfSale())
                                <a href="{{ route('pos.dashboard') }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-medium text-ink-700 hover:bg-ink-50">
                                    @icon('store', 'w-4 h-4') Point de vente
                                </a>
                            @endif

                            @if ($user->isAdmin())
                                <a href="{{ route('admin.dashboard') }}"
                                   class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-medium text-ink-700 hover:bg-ink-50">
                                    @icon('shield', 'w-4 h-4') Administration
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-medium text-ink-700 hover:bg-ink-50">
                                @icon('user', 'w-4 h-4') Connexion
                            </a>
                        @endauth
                    </div>
                </div>
            </div>

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
                </nav>
            @else
            <nav class="ml-4 hidden items-center gap-1 lg:flex">
                <a href="{{ route('home') }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-ink-100 hover:text-ink-900 {{ request()->routeIs('home') ? 'bg-ink-100 text-ink-900' : '' }}">
                    Événements
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
                        <a href="{{ route('organizer.dashboard') }}" class="btn-primary btn-sm hidden sm:inline-flex">
                            @icon('chart-bar', 'w-4 h-4') Tableau de bord
                        </a>
                    @endif

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 rounded-xl border border-ink-200 bg-white py-1.5 pl-1.5 pr-2.5 text-sm transition hover:border-ink-300">
                            <span class="grid h-7 w-7 place-items-center rounded-lg bg-brand-100 text-xs font-bold text-brand-700">
                                {{ $user->initials }}
                            </span>
                            <span class="hidden max-w-[9rem] truncate font-medium text-ink-800 sm:block">{{ $user->first_name }}</span>
                            @icon('chevron-down', 'w-4 h-4 text-ink-400')
                        </button>

                        <div x-cloak x-show="open" x-transition
                             class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-ink-200 bg-white shadow-xl">
                            <div class="border-b border-ink-100 px-4 py-3">
                                <p class="truncate text-sm font-semibold text-ink-900">{{ $user->full_name }}</p>
                                <p class="truncate text-xs text-ink-500">{{ $user->email }}</p>
                                <span class="badge mt-2 bg-brand-50 text-brand-700">{{ $user->role_label }}</span>
                            </div>
                            <div class="p-1.5 text-sm">
                                <a href="{{ route('reservations.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink-700 hover:bg-ink-50">
                                    @icon('ticket', 'w-4 h-4') Mes réservations
                                </a>
                                @if ($user->isManagedPointOfSale())
                                    <a href="{{ route('pos.dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink-700 hover:bg-ink-50">
                                        @icon('store', 'w-4 h-4') Point de vente
                                    </a>
                                @endif
                                @if ($user->isAgent())
                                    <a href="{{ route('agent.scan') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink-700 hover:bg-ink-50">
                                        @icon('scan', 'w-4 h-4') Scanner un ticket
                                    </a>
                                @endif
                                @if ($user->isOrganizer() || $user->isAdmin())
                                    <a href="{{ route('organizer.events.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink-700 hover:bg-ink-50">
                                        @icon('calendar', 'w-4 h-4') Mes événements
                                    </a>
                                    <a href="{{ route('organizer.stocks.index') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink-700 hover:bg-ink-50">
                                        @icon('coins', 'w-4 h-4') Stock physique
                                    </a>
                                @endif
                                @if ($user->isAdmin())
                                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-ink-700 hover:bg-ink-50">
                                        @icon('shield', 'w-4 h-4') Administration
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-ink-100 pt-1">
                                    @csrf
                                    <button class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-rose-600 hover:bg-rose-50">
                                        @icon('logout', 'w-4 h-4') Se déconnecter
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    @if ($darkHeader)
                        <a href="{{ $homeAnchor }}#evenements" class="hidden h-9 w-9 place-items-center rounded-xl text-white/80 transition hover:bg-white/10 hover:text-white sm:grid"
                           aria-label="Rechercher un événement">
                            @icon('search', 'w-5 h-5')
                        </a>
                        <a href="{{ route('login') }}"
                           class="btn btn-sm hidden border border-white/30 text-white hover:bg-white/10 focus-visible:ring-white focus-visible:ring-offset-0 sm:inline-flex">
                            Se connecter
                        </a>
                        <a href="{{ route('register') }}"
                           class="btn btn-sm bg-orange-400 text-ink-950 shadow-lg shadow-orange-400/25 hover:bg-orange-300">
                            S'inscrire
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost btn-sm hidden sm:inline-flex">Connexion</a>
                        <a href="{{ route('register') }}" class="btn-primary btn-sm">S'inscrire</a>
                    @endif
                @endauth
            </div>
        </div>
    </div>
</header>