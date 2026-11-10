<footer class="{{ request()->routeIs('home', 'events.show', 'booking.create', 'payment.show', 'login', 'register', 'reservations.index', 'reservations.show', 'tickets.show', 'tickets.reservation', 'pos.dashboard', 'agent.index', 'agent.scan', 'favoris.*', 'profil.*', 'partners.*') || (request()->routeIs('organizer.*', 'admin.*') && request()->cookie('theme') !== 'light') ? '' : 'mt-20' }} border-t border-white/10 bg-ink-950 no-print {{ ($hasSidebar ?? false) ? '' : 'pb-24 lg:pb-0' }}">

    {{-- =================================================================
         MOBILE — maquette dédiée (bloquée sous lg)
         ================================================================= --}}
    <div class="relative overflow-hidden bg-[#0A0D15] lg:hidden">
        {{-- Décor : halo orange en haut à droite --}}
        <div class="pointer-events-none absolute -right-16 -top-14 h-44 w-44 rounded-full bg-orange-500/20 blur-3xl" aria-hidden="true"></div>

        <div class="relative px-4 pt-10 sm:px-5">

            {{-- Marque --}}
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/icon.svg') }}?v={{ config('app.asset_version') }}" alt="" width="44" height="44"
                     class="h-11 w-11 shrink-0">
                <span class="text-lg font-extrabold leading-none tracking-tight text-white">
                    Billeterie <span class="text-orange-500">Express</span>
                </span>
            </div>

            <p class="mt-5 text-[15px] leading-relaxed text-white/70">
                Votre billetterie en ligne au Sénégal. Achetez vos billets en toute sécurité et payez
                avec <strong class="font-semibold text-white">Wave</strong> ou
                <strong class="font-semibold text-white">Orange Money</strong>.
            </p>

            {{-- Pastille paiement sécurisé --}}
            <div class="mt-5 flex flex-wrap items-center gap-x-2.5 gap-y-1.5 rounded-full border border-orange-500/60 bg-orange-500/[.06] px-3.5 py-2.5 shadow-[0_0_24px_rgba(249,115,22,.12)]">
                <span class="flex min-w-0 items-center gap-2">
                    <span class="shrink-0 text-orange-500">@icon('shield', 'w-[18px] h-[18px]')</span>
                    <span class="text-[10.5px] font-medium leading-tight text-white/80">Paiement sécurisé via DexPay</span>
                </span>
                <span class="h-5 w-px shrink-0 bg-white/20" aria-hidden="true"></span>
                <img src="{{ asset('images/logos/wave.png') }}" alt="Wave" class="h-[22px] w-[22px] shrink-0 rounded-[6px]">
                <span class="h-5 w-px shrink-0 bg-white/20" aria-hidden="true"></span>
                <svg viewBox="0 0 24 24" class="h-[22px] w-[22px] shrink-0" role="img" aria-label="Orange Money">
                    <rect width="24" height="24" rx="4" fill="#FF7900"/>
                    <text x="12" y="19.5" text-anchor="middle" font-size="7.2" font-weight="500" fill="#fff"
                          font-family="Helvetica, Arial, sans-serif">orange</text>
                </svg>
                <span class="h-5 w-px shrink-0 bg-white/20" aria-hidden="true"></span>
                <img src="{{ asset('images/logos/mixx.png') }}" alt="Mixx by Yas" class="h-[22px] w-[22px] shrink-0 rounded-[6px]">
            </div>

            {{-- Cartes Navigation / Espaces --}}
            <div class="mt-7 grid grid-cols-2 gap-3 sm:gap-4">
                <div class="relative overflow-hidden rounded-2xl border border-[#1D2433] bg-[#101523] p-3 sm:p-4">
                    <span class="absolute left-0 top-[14%] h-[42%] w-[3px] rounded-full bg-gradient-to-b from-transparent via-orange-500 to-transparent drop-shadow-[0_0_10px_rgba(249,115,22,.8)]" aria-hidden="true"></span>

                    <span class="grid h-11 w-11 place-items-center rounded-full bg-gradient-to-br from-orange-400 to-orange-600 text-white shadow-[0_0_18px_rgba(249,115,22,.5)]">
                        @icon('send', 'w-5 h-5')
                    </span>
                    <h3 class="mt-3.5 text-xl font-extrabold tracking-tight text-white">Navigation</h3>
                    <span class="mt-2 block h-1 w-12 rounded-full bg-orange-500" aria-hidden="true"></span>

                    <ul class="mt-4 space-y-3 text-[12px] leading-snug text-white/85">
                        <li>
                            <a href="{{ route('home') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[2px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Tous les événements</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('agent.scan') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[2px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Scanner un ticket</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('partners.become') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[2px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Devenir partenaire</span>
                            </a>
                        </li>
                        @auth
                            <li>
                                <a href="{{ route('reservations.index') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                    <span class="mt-[2px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                    <span>Mes réservations</span>
                                </a>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('login') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                    <span class="mt-[2px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                    <span>Connexion</span>
                                </a>
                            </li>
                        @endauth
                    </ul>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-[#1D2433] bg-[#101523] p-3 sm:p-4">
                    <span class="absolute left-0 top-[14%] h-[42%] w-[3px] rounded-full bg-gradient-to-b from-transparent via-orange-500 to-transparent drop-shadow-[0_0_10px_rgba(249,115,22,.8)]" aria-hidden="true"></span>

                    <span class="grid h-11 w-11 place-items-center rounded-full bg-gradient-to-br from-orange-400 to-orange-600 text-white shadow-[0_0_18px_rgba(249,115,22,.5)]">
                        @icon('layers', 'w-5 h-5')
                    </span>
                    <h3 class="mt-3.5 text-xl font-extrabold tracking-tight text-white">Espaces</h3>
                    <span class="mt-2 block h-1 w-12 rounded-full bg-orange-500" aria-hidden="true"></span>

                    <ul class="mt-4 space-y-3 text-[12px] leading-snug text-white/85">
                        <li>
                            <a href="{{ route('login') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[2px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Espace organisateur</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[2px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Espace agent</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[2px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Point de vente</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Suivez-nous --}}
            <div class="mt-9 flex items-center gap-4">
                <span class="h-px flex-1 bg-white/15" aria-hidden="true"></span>
                <h3 class="text-base font-bold text-white">Suivez-nous</h3>
                <span class="h-px flex-1 bg-white/15" aria-hidden="true"></span>
            </div>

            <ul class="mt-5 flex flex-wrap items-center justify-center gap-3">
                <li>
                    <span class="grid h-11 w-11 place-items-center rounded-xl border border-white/10 bg-white/[.05]" role="img" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" class="h-[22px] w-[22px]" aria-hidden="true">
                            <circle cx="12" cy="12" r="11" fill="#1877F2"/>
                            <path fill="#fff" d="M13.2 19v-6h2l.3-2.3h-2.3V9.2c0-.7.2-1.1 1.1-1.1h1.2V6c-.2 0-.9-.1-1.7-.1-1.7 0-2.9 1-2.9 2.9v1.9H8.9V13h2v6h2.3z"/>
                        </svg>
                    </span>
                </li>
                <li>
                    <span class="grid h-11 w-11 place-items-center rounded-xl border border-white/10 bg-white/[.05]" role="img" aria-label="X">
                        <svg viewBox="0 0 24 24" class="h-[20px] w-[20px]" aria-hidden="true">
                            <path fill="#fff" d="M17.6 3h3.1l-6.8 7.8L21.8 21h-6.2l-4.9-6.4L5.1 21H2l7.3-8.3L2.4 3h6.4l4.4 5.8L17.6 3zm-1.1 16.1h1.7L7.6 4.8H5.8l10.7 14.3z"/>
                        </svg>
                    </span>
                </li>
                <li>
                    <span class="grid h-11 w-11 place-items-center rounded-xl border border-white/10 bg-white/[.05]" role="img" aria-label="TikTok">
                        <svg viewBox="0 0 24 24" class="h-[21px] w-[21px]" aria-hidden="true">
                            <path fill="#25F4EE" transform="translate(-0.6,-0.6)" d="M16.5 2.5h-2.7v13a2.6 2.6 0 1 1-2.6-2.6c.24 0 .47.04.69.11v-2.8a5.5 5.5 0 1 0 4.6 5.4V8.8a6.6 6.6 0 0 0 3.9 1.25V7.3a3.9 3.9 0 0 1-3.89-4.8z"/>
                            <path fill="#FE2C55" transform="translate(0.6,0.6)" d="M16.5 2.5h-2.7v13a2.6 2.6 0 1 1-2.6-2.6c.24 0 .47.04.69.11v-2.8a5.5 5.5 0 1 0 4.6 5.4V8.8a6.6 6.6 0 0 0 3.9 1.25V7.3a3.9 3.9 0 0 1-3.89-4.8z"/>
                            <path fill="#fff" d="M16.5 2.5h-2.7v13a2.6 2.6 0 1 1-2.6-2.6c.24 0 .47.04.69.11v-2.8a5.5 5.5 0 1 0 4.6 5.4V8.8a6.6 6.6 0 0 0 3.9 1.25V7.3a3.9 3.9 0 0 1-3.89-4.8z"/>
                        </svg>
                    </span>
                </li>
                <li>
                    <span class="grid h-11 w-11 place-items-center rounded-xl border border-white/10 bg-white/[.05]" role="img" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" class="h-[22px] w-[22px]" aria-hidden="true">
                            <defs>
                                <linearGradient id="beIgGrad" x1="0" y1="1" x2="1" y2="0">
                                    <stop offset="0" stop-color="#FEDA75"/>
                                    <stop offset=".3" stop-color="#FA7E1E"/>
                                    <stop offset=".6" stop-color="#D62976"/>
                                    <stop offset=".85" stop-color="#962FBF"/>
                                    <stop offset="1" stop-color="#4F5BD5"/>
                                </linearGradient>
                            </defs>
                            <rect x="1.5" y="1.5" width="21" height="21" rx="6.5" fill="url(#beIgGrad)"/>
                            <rect x="6.5" y="6.5" width="11" height="11" rx="3.6" fill="none" stroke="#fff" stroke-width="1.7"/>
                            <circle cx="12" cy="12" r="2.7" fill="none" stroke="#fff" stroke-width="1.7"/>
                            <circle cx="16.6" cy="7.5" r="1.1" fill="#fff"/>
                        </svg>
                    </span>
                </li>
                <li>
                    <span class="grid h-11 w-11 place-items-center rounded-xl border border-white/10 bg-white/[.05]" role="img" aria-label="YouTube">
                        <svg viewBox="0 0 24 24" class="h-[22px] w-[22px]" aria-hidden="true">
                            <rect x="1.5" y="5" width="21" height="14" rx="4.5" fill="#FF0000"/>
                            <path fill="#fff" d="M10 8.8 15.4 12 10 15.2z"/>
                        </svg>
                    </span>
                </li>
                <li>
                    <span class="grid h-11 w-11 place-items-center rounded-xl border border-white/10 bg-white/[.05]" role="img" aria-label="LinkedIn">
                        <svg viewBox="0 0 24 24" class="h-[22px] w-[22px]" aria-hidden="true">
                            <rect x="1.5" y="1.5" width="21" height="21" rx="4.5" fill="#0A66C2"/>
                            <path fill="#fff" d="M6.9 9.6h2.05v7.8H6.9V9.6zm1.02-3.15a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4zM10.65 9.6h1.96v1.07h.03c.27-.51.95-1.05 1.96-1.05 2.1 0 2.49 1.38 2.49 3.18v4.6h-2.05v-4.08c0-.97-.02-2.23-1.36-2.23-1.36 0-1.57 1.06-1.57 2.16v4.15h-2.05V9.6z"/>
                        </svg>
                    </span>
                </li>
            </ul>

            {{-- Copyright --}}
            <div class="mt-8 border-t border-white/10 pt-6 text-center">
                <p class="text-[13.5px] text-white/75">
                    © {{ now()->year }} <span class="font-bold text-orange-500">Billeterie Express</span>. Tous droits réservés.
                </p>
                <p class="mt-2.5 text-[13px] text-white/60">
                    Mobile money <span class="mx-1 text-orange-500">|</span> Wave <span class="mx-1 text-orange-500">|</span> Orange Money
                </p>
            </div>
        </div>

        {{-- Bandeau foule + ruban orange (prolongé sous la barre de navigation fixe) --}}
        <div class="relative h-40 overflow-hidden {{ ($hasSidebar ?? false) ? '' : '-mb-24' }}" aria-hidden="true">
            <img src="{{ asset('images/hero-fans-v2.jpg') }}" alt="" loading="lazy"
                 class="absolute inset-0 h-full w-full object-cover" style="object-position: center 30%;">
            <div class="absolute inset-0" style="background:
                linear-gradient(180deg, #0A0D15 0%, rgba(10,13,21,.78) 20%, rgba(10,13,21,.25) 55%, rgba(10,13,21,.55) 100%);"></div>
            <svg class="absolute -left-8 bottom-6 h-28 w-64" viewBox="0 0 260 120" fill="none" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="beFooterRibbon" x1="0" y1="0" x2="1" y2="0">
                        <stop offset="0" stop-color="#f97316"/>
                        <stop offset="1" stop-color="#fb923c"/>
                    </linearGradient>
                </defs>
                <path d="M0 62 C 70 14 155 8 260 44 L260 62 C155 30 70 40 0 92 Z" fill="url(#beFooterRibbon)"/>
            </svg>
        </div>
    </div>

    {{-- =================================================================
         DESKTOP — mise en page historique (4 colonnes)
         ================================================================= --}}
    <div class="hidden lg:block">
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
                        <li><a href="{{ route('partners.become') }}" class="transition hover:text-orange-400">Devenir partenaire</a></li>
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
    </div>
</footer>
