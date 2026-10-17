@php
    $user = auth()->user();
    $isHome = request()->routeIs('home');
    $isTickets = request()->routeIs('reservations.*', 'tickets.*');
    $isFavoris = request()->routeIs('favoris.*');
    $isProfil = request()->routeIs('profil.*', 'login', 'register');
@endphp

<nav class="no-print fixed inset-x-0 bottom-0 z-40 lg:hidden" aria-label="Navigation principale" x-data="pwa()" x-init="init()">
    {{-- Invite d'installation --}}
    <div x-cloak x-show="canInstall && !installed" x-transition
         class="flex justify-end px-3 pb-1.5">
        <button type="button" @click="install()"
                class="inline-flex items-center gap-1.5 rounded-full bg-orange-500 px-3.5 py-1.5 text-xs font-bold text-ink-950 shadow-lg shadow-orange-500/30 transition active:scale-95">
            @icon('download', 'w-3.5 h-3.5') Installer l'app
        </button>
    </div>
    <div class="relative mx-auto max-w-md">
        {{-- Barre --}}
        <div class="flex items-end justify-between rounded-t-3xl border-x border-t border-white/10 bg-ink-950/95 px-2 pb-1 pt-2.5 shadow-[0_-8px_30px_rgba(0,0,0,.45)] backdrop-blur-lg"
             style="padding-bottom: calc(0.25rem + env(safe-area-inset-bottom));">
            <a href="{{ route('home') }}"
               class="flex w-16 flex-col items-center gap-1 py-1 {{ $isHome ? 'text-orange-400' : 'text-white/55' }}">
                @icon('home', 'w-[22px] h-[22px]')
                <span class="text-[11px] font-semibold leading-none">Accueil</span>
            </a>

            <a href="{{ route('reservations.index') }}"
               class="flex w-16 flex-col items-center gap-1 py-1 {{ $isTickets ? 'text-orange-400' : 'text-white/55' }}">
                @icon('ticket', 'w-[22px] h-[22px]')
                <span class="text-[11px] font-semibold leading-none">Mes billets</span>
            </a>

            <div class="w-14" aria-hidden="true"></div>

            <a href="{{ route('favoris.index') }}"
               class="flex w-16 flex-col items-center gap-1 py-1 {{ $isFavoris ? 'text-orange-400' : 'text-white/55' }}">
                @icon('heart', 'w-[22px] h-[22px]')
                <span class="text-[11px] font-semibold leading-none">Favoris</span>
            </a>

            <a href="{{ $user ? route('profil.show') : route('login') }}"
               class="flex w-16 flex-col items-center gap-1 py-1 {{ $isProfil ? 'text-orange-400' : 'text-white/55' }}">
                @icon('user', 'w-[22px] h-[22px]')
                <span class="text-[11px] font-semibold leading-none">Profil</span>
            </a>
        </div>

        {{-- Bouton central flottant --}}
        <a href="{{ route('home') }}#evenements"
           class="absolute -top-3 left-1/2 z-10 grid h-14 w-14 -translate-x-1/2 place-items-center rounded-full bg-orange-500 text-white shadow-lg shadow-orange-500/40 transition active:scale-95"
           aria-label="Voir les événements">
            @icon('ticket', 'w-6 h-6')
        </a>
    </div>
</nav>
