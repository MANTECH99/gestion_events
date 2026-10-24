<footer class="{{ request()->routeIs('home', 'events.show', 'booking.create', 'payment.show', 'login', 'register', 'reservations.index', 'reservations.show', 'tickets.show', 'tickets.reservation', 'pos.dashboard', 'agent.index', 'agent.scan', 'favoris.*', 'profil.*') || (request()->routeIs('organizer.*', 'admin.*') && request()->cookie('theme') !== 'light') ? '' : 'mt-20' }} border-t border-white/10 bg-ink-950 no-print {{ ($hasSidebar ?? false) ? '' : 'pb-24 lg:pb-0' }}">
    <div class="{{ ($hasSidebar ?? false) ? 'w-full' : 'mx-auto max-w-7xl' }} px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/icon.svg') }}?v={{ config('app.asset_version') }}" alt="" width="36" height="36"
                         class="h-9 w-9 shrink-0 rounded-xl">
                    <span class="text-lg font-extrabold tracking-tight text-white">
                        Billeterie <span class="text-white">Express</span>
                    </span>
                </div>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/60">
                    Votre billetterie en ligne au Sénégal. Achetez vos billets en toute sécurité et payez
                    avec <strong class="font-semibold text-white">Wave</strong> ou <strong class="font-semibold text-white">Orange Money</strong>.
                </p>
                <div class="mt-5 flex items-center gap-2 text-xs text-white/50">
                    @icon('shield', 'w-4 h-4') Paiement sécurisé via DexPay (DEXCHANGE PAY)
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-white">Navigation</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/65">
                    <li><a href="{{ route('home') }}" class="transition hover:text-orange-400">Tous les événements</a></li>
                    <li><a href="{{ route('agent.scan') }}" class="transition hover:text-orange-400">Scanner un ticket</a></li>
                    @auth
                        <li><a href="{{ route('reservations.index') }}" class="transition hover:text-orange-400">Mes réservations</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="transition hover:text-orange-400">Connexion</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-white">Espaces</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-white/65">
                    <li><a href="{{ route('login') }}" class="transition hover:text-orange-400">Espace organisateur</a></li>
                    <li><a href="{{ route('login') }}" class="transition hover:text-orange-400">Espace agent</a></li>
                    <li><a href="{{ route('login') }}" class="transition hover:text-orange-400">Point de vente</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-white/45 sm:flex-row">
            <p>© {{ now()->year }} Billeterie Express. Tous droits réservés.</p>
            <p>Mobile money : Wave · Orange Money</p>
        </div>
    </div>
</footer>
