<footer class="mt-20 border-t border-ink-200 bg-white no-print">
    <div class="{{ ($hasSidebar ?? false) ? 'w-full' : 'mx-auto max-w-7xl' }} px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/icon.svg') }}" alt="" width="36" height="36"
                         class="h-9 w-9 shrink-0 rounded-xl">
                    <span class="text-lg font-extrabold tracking-tight text-ink-950">
                        Billeterie <span class="text-gradient">Express</span>
                    </span>
                </div>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-600">
                    Votre billetterie en ligne au Sénégal. Achetez vos billets en toute sécurité et payez
                    avec <strong class="font-semibold text-ink-800">Wave</strong> ou <strong class="font-semibold text-ink-800">Orange Money</strong>.
                </p>
                <div class="mt-5 flex items-center gap-2 text-xs text-ink-500">
                    @icon('shield', 'w-4 h-4') Paiement sécurisé via DexPay (DEXCHANGE PAY)
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-ink-900">Navigation</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-600">
                    <li><a href="{{ route('home') }}" class="hover:text-brand-600">Tous les événements</a></li>
                    <li><a href="{{ route('agent.scan') }}" class="hover:text-brand-600">Scanner un ticket</a></li>
                    @auth
                        <li><a href="{{ route('reservations.index') }}" class="hover:text-brand-600">Mes réservations</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="hover:text-brand-600">Connexion</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-ink-900">Espaces</h3>
                <ul class="mt-4 space-y-2.5 text-sm text-ink-600">
                    <li><a href="{{ route('login') }}" class="hover:text-brand-600">Espace organisateur</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-brand-600">Espace agent</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-brand-600">Point de vente</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-ink-100 pt-6 text-xs text-ink-500 sm:flex-row">
            <p>© {{ now()->year }} Billeterie Express. Tous droits réservés.</p>
            <p>Mobile money : Wave · Orange Money</p>
        </div>
    </div>
</footer>