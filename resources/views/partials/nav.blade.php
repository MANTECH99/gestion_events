@php
    $user = auth()->user();
    $hasSidebar = $hasSidebar ?? false;
@endphp

<header class="app-header sticky top-0 z-50 border-b border-ink-200/60 bg-white/85 backdrop-blur-lg no-print">
    {{-- Sur les interfaces de gestion, la colonne fixe occupe la bande gauche du header. --}}
    <div class="{{ $hasSidebar ? 'lg:pl-64' : '' }}">
        <div x-data="{ open: false }" @click.outside="open = false" class="relative flex h-16 {{ $hasSidebar ? 'w-full' : 'mx-auto max-w-7xl' }} items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">
                <img src="{{ asset('images/icon.svg') }}" alt="" width="36" height="36"
                     class="h-9 w-9 shrink-0 rounded-xl shadow-lg shadow-brand-600/30">
                <span class="text-lg font-extrabold tracking-tight text-ink-950">
                    Billeterie <span class="text-gradient">Express</span>
                </span>
            </a>

            {{-- Menu mobile : la navigation principale est masquée sous lg --}}
            <div class="lg:hidden">
                <button @click="open = !open"
                        class="grid h-9 w-9 place-items-center rounded-xl border border-ink-200 bg-white text-ink-700 transition hover:border-ink-300"
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
                    <a href="{{ route('login') }}" class="btn-ghost btn-sm hidden sm:inline-flex">Connexion</a>
                    <a href="{{ route('register') }}" class="btn-primary btn-sm">S'inscrire</a>
                @endauth
            </div>
        </div>
    </div>
</header>