<footer class="{{ request()->routeIs('home', 'events.show', 'booking.create', 'payment.show', 'login', 'register', 'reservations.index', 'reservations.show', 'tickets.show', 'tickets.reservation', 'pos.dashboard', 'agent.index', 'agent.scan', 'favoris.*', 'profil.*', 'partners.*') || (request()->routeIs('organizer.*', 'admin.*') && request()->cookie('theme') !== 'light') ? '' : 'mt-20' }} border-t border-white/10 bg-ink-950 no-print {{ ($hasSidebar ?? false) ? '' : 'pb-24 lg:pb-0' }}">

    {{-- =================================================================
         MOBILE — maquette dédiée (bloquée sous lg)
         ================================================================= --}}
    <div class="relative overflow-hidden bg-[#12141c] lg:hidden">
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
                linear-gradient(180deg, #12141c 0%, rgba(18,20,28,.78) 20%, rgba(18,20,28,.25) 55%, rgba(18,20,28,.55) 100%);"></div>
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
         DESKTOP — maquette footer_desktop.jpeg
         ================================================================= --}}
    <div class="relative hidden overflow-hidden bg-[#12141c] lg:block">
        {{-- Décor : halos orange dans les coins supérieurs --}}
        <div class="pointer-events-none absolute -left-24 -top-24 h-60 w-60 rounded-full bg-orange-600/30 blur-[90px]" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -right-16 -top-28 h-72 w-[26rem] rounded-full bg-orange-500/35 blur-[100px]" aria-hidden="true"></div>

        <div class="relative {{ ($hasSidebar ?? false) ? 'w-full' : 'mx-auto max-w-7xl' }} px-6 pt-14 pb-8">
            {{-- ================= colonnes : marque · Navigation · Espaces · Suivez-nous --}}
            <div class="grid gap-6 px-4 lg:grid-cols-[1.5fr_0.7fr_0.7fr_1.08fr] xl:gap-8">

                {{-- Marque + paiement --}}
                <div>
                    <div class="flex items-center gap-2.5">
                        <img src="{{ asset('images/icon.svg') }}?v={{ config('app.asset_version') }}" alt="Billeterie Express"
                             width="36" height="36" class="h-9 w-9 shrink-0 rounded-xl">
                        <span class="text-lg font-extrabold tracking-tight text-white">
                            Billeterie <span class="text-orange-500">Express</span>
                        </span>
                    </div>

                    <p class="mt-5 max-w-[430px] text-[15px] leading-relaxed text-white/70">
                        Votre billetterie en ligne au Sénégal. Achetez vos billets en toute sécurité et payez avec
                        <strong class="font-semibold text-orange-500">Wave</strong> ou
                        <strong class="font-semibold text-orange-500">Orange Money</strong>.
                    </p>

                    {{-- Pastille « paiement sécurisé » --}}
                    <div class="mt-6 flex w-full max-w-[440px] items-center gap-2.5 rounded-2xl border border-white/15 bg-white/[.03] px-4 py-[19px]">
                        <span class="flex min-w-0 items-center gap-2">
                            <span class="shrink-0 text-orange-500">@icon('shield', 'w-5 h-5')</span>
                            <span class="text-[12.5px] leading-tight text-white/75">Paiement sécurisé via DexPay</span>
                        </span>
                        <span class="h-6 w-px shrink-0 bg-white/15" aria-hidden="true"></span>
                        <img src="{{ asset('images/logos/wave.png') }}" alt="Wave" class="h-6 w-6 shrink-0 rounded-md">
                        <span class="h-6 w-px shrink-0 bg-white/15" aria-hidden="true"></span>
                        <img src="{{ asset('images/logos/orange.png') }}" alt="Orange Money" class="h-6 w-6 shrink-0 rounded-md">
                        <span class="h-6 w-px shrink-0 bg-white/15" aria-hidden="true"></span>
                        <img src="{{ asset('images/logos/mixx.png') }}" alt="Mixx by Yas" class="h-6 w-6 shrink-0 rounded-md">
                    </div>
                </div>

                {{-- Carte Navigation --}}
                <div class="relative overflow-hidden rounded-2xl border border-[#1D2433] bg-[#101523] p-5">
                    <span class="absolute left-0 top-[14%] h-[42%] w-[3px] rounded-full bg-gradient-to-b from-transparent via-orange-500 to-transparent drop-shadow-[0_0_10px_rgba(249,115,22,.8)]" aria-hidden="true"></span>

                    <span class="grid h-12 w-12 place-items-center rounded-full bg-gradient-to-br from-orange-400 to-orange-600 text-white shadow-[0_0_18px_rgba(249,115,22,.5)]">
                        @icon('send', 'w-5 h-5')
                    </span>
                    <h3 class="mt-4 text-[22px] font-extrabold tracking-tight text-white">Navigation</h3>
                    <span class="mt-2 block h-1 w-12 rounded-full bg-orange-500" aria-hidden="true"></span>

                    <ul class="mt-4 space-y-4 text-sm text-white/85">
                        <li>
                            <a href="{{ route('home') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[3px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Tous les événements</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('agent.scan') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[3px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Scanner un ticket</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[3px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Connexion</span>
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Carte Espaces --}}
                <div class="relative overflow-hidden rounded-2xl border border-[#1D2433] bg-[#101523] p-5">
                    <span class="absolute left-0 top-[14%] h-[42%] w-[3px] rounded-full bg-gradient-to-b from-transparent via-orange-500 to-transparent drop-shadow-[0_0_10px_rgba(249,115,22,.8)]" aria-hidden="true"></span>

                    <span class="grid h-12 w-12 place-items-center rounded-full bg-gradient-to-br from-orange-400 to-orange-600 text-white shadow-[0_0_18px_rgba(249,115,22,.5)]">
                        @icon('layers', 'w-5 h-5')
                    </span>
                    <h3 class="mt-4 text-[22px] font-extrabold tracking-tight text-white">Espaces</h3>
                    <span class="mt-2 block h-1 w-12 rounded-full bg-orange-500" aria-hidden="true"></span>

                    <ul class="mt-4 space-y-4 text-sm text-white/85">
                        <li>
                            <a href="{{ route('login') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[3px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Espace organisateur</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[3px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Espace agent</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('login') }}" class="flex items-start gap-1.5 transition hover:text-orange-400">
                                <span class="mt-[3px] shrink-0 text-orange-500">@icon('chevron-right', 'w-3.5 h-3.5')</span>
                                <span>Point de vente</span>
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Suivez-nous --}}
                <div class="lg:pt-8">
                    <h3 class="text-[22px] font-extrabold tracking-tight text-white">Suivez-nous</h3>

                    <ul class="mt-5 flex flex-wrap items-center gap-2.5">
                        <li>
                            <span class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/[.04]" role="img" aria-label="Facebook">
                                <svg viewBox="0 0 24 24" class="h-[21px] w-[21px]" aria-hidden="true">
                                    <circle cx="12" cy="12" r="11" fill="#1877F2"/>
                                    <path fill="#fff" d="M13.2 19v-6h2l.3-2.3h-2.3V9.2c0-.7.2-1.1 1.1-1.1h1.2V6c-.2 0-.9-.1-1.7-.1-1.7 0-2.9 1-2.9 2.9v1.9H8.9V13h2v6h2.3z"/>
                                </svg>
                            </span>
                        </li>
                        <li>
                            <span class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/[.04]" role="img" aria-label="X">
                                <svg viewBox="0 0 24 24" class="h-[19px] w-[19px]" aria-hidden="true">
                                    <path fill="#fff" d="M17.6 3h3.1l-6.8 7.8L21.8 21h-6.2l-4.9-6.4L5.1 21H2l7.3-8.3L2.4 3h6.4l4.4 5.8L17.6 3zm-1.1 16.1h1.7L7.6 4.8H5.8l10.7 14.3z"/>
                                </svg>
                            </span>
                        </li>
                        <li>
                            <span class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/[.04]" role="img" aria-label="TikTok">
                                <svg viewBox="0 0 24 24" class="h-[20px] w-[20px]" aria-hidden="true">
                                    <path fill="#25F4EE" transform="translate(-0.6,-0.6)" d="M16.5 2.5h-2.7v13a2.6 2.6 0 1 1-2.6-2.6c.24 0 .47.04.69.11v-2.8a5.5 5.5 0 1 0 4.6 5.4V8.8a6.6 6.6 0 0 0 3.9 1.25V7.3a3.9 3.9 0 0 1-3.89-4.8z"/>
                                    <path fill="#FE2C55" transform="translate(0.6,0.6)" d="M16.5 2.5h-2.7v13a2.6 2.6 0 1 1-2.6-2.6c.24 0 .47.04.69.11v-2.8a5.5 5.5 0 1 0 4.6 5.4V8.8a6.6 6.6 0 0 0 3.9 1.25V7.3a3.9 3.9 0 0 1-3.89-4.8z"/>
                                    <path fill="#fff" d="M16.5 2.5h-2.7v13a2.6 2.6 0 1 1-2.6-2.6c.24 0 .47.04.69.11v-2.8a5.5 5.5 0 1 0 4.6 5.4V8.8a6.6 6.6 0 0 0 3.9 1.25V7.3a3.9 3.9 0 0 1-3.89-4.8z"/>
                                </svg>
                            </span>
                        </li>
                        <li>
                            <span class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/[.04]" role="img" aria-label="Instagram">
                                <svg viewBox="0 0 24 24" class="h-[21px] w-[21px]" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="beFooterIg" x1="0" y1="1" x2="1" y2="0">
                                            <stop offset="0" stop-color="#FEDA75"/>
                                            <stop offset=".3" stop-color="#FA7E1E"/>
                                            <stop offset=".6" stop-color="#D62976"/>
                                            <stop offset=".85" stop-color="#962FBF"/>
                                            <stop offset="1" stop-color="#4F5BD5"/>
                                        </linearGradient>
                                    </defs>
                                    <rect x="1.5" y="1.5" width="21" height="21" rx="6.5" fill="url(#beFooterIg)"/>
                                    <rect x="6.5" y="6.5" width="11" height="11" rx="3.6" fill="none" stroke="#fff" stroke-width="1.7"/>
                                    <circle cx="12" cy="12" r="2.7" fill="none" stroke="#fff" stroke-width="1.7"/>
                                    <circle cx="16.6" cy="7.5" r="1.1" fill="#fff"/>
                                </svg>
                            </span>
                        </li>
                        <li>
                            <span class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/[.04]" role="img" aria-label="YouTube">
                                <svg viewBox="0 0 24 24" class="h-[21px] w-[21px]" aria-hidden="true">
                                    <rect x="1.5" y="5" width="21" height="14" rx="4.5" fill="#FF0000"/>
                                    <path fill="#fff" d="M10 8.8 15.4 12 10 15.2z"/>
                                </svg>
                            </span>
                        </li>
                        <li>
                            <span class="grid h-10 w-10 place-items-center rounded-xl border border-white/10 bg-white/[.04]" role="img" aria-label="LinkedIn">
                                <svg viewBox="0 0 24 24" class="h-[21px] w-[21px]" aria-hidden="true">
                                    <rect x="1.5" y="1.5" width="21" height="21" rx="4.5" fill="#0A66C2"/>
                                    <path fill="#fff" d="M6.9 9.6h2.05v7.8H6.9V9.6zm1.02-3.15a1.2 1.2 0 1 1 0 2.4 1.2 1.2 0 0 1 0-2.4zM10.65 9.6h1.96v1.07h.03c.27-.51.95-1.05 1.96-1.05 2.1 0 2.49 1.38 2.49 3.18v4.6h-2.05v-4.08c0-.97-.02-2.23-1.36-2.23-1.36 0-1.57 1.06-1.57 2.16v4.15h-2.05V9.6z"/>
                                </svg>
                            </span>
                        </li>
                    </ul>

                    <div class="mt-6 h-px bg-white/10" aria-hidden="true"></div>

                    <p class="mt-6 text-center">
                        <span class="block text-[17px] font-bold text-white">Billeterie Express,</span>
                        <span class="block text-[17px] italic text-white/85">au cœur de vos événements</span>
                        <span class="mt-1 flex justify-center" aria-hidden="true">
                            <svg viewBox="0 0 200 24" class="h-5 w-44 translate-x-4" fill="#F97316">
                                <path d="M6 16C60 2 140 2 194 8 140 18 60 20 6 16Z"/>
                            </svg>
                        </span>
                    </p>
                </div>
            </div>

            {{-- ================= bandeau newsletter — maquette WhatsApp ================= --}}
            <div class="relative mt-7 min-h-[180px] overflow-hidden rounded-[26px] border border-white/[.07] bg-[#010510]">
                {{-- Photo foule (extrait de la maquette, zone formulaire peinte en fond) --}}
                <div class="pointer-events-none absolute right-0 top-0 h-[180px] w-[720px] max-w-none overflow-hidden" aria-hidden="true">
                    <img src="{{ asset('images/newsletter-crowd.png') }}" alt="" class="absolute right-0 top-0 h-[180px] w-[720px] max-w-none">
                    <div class="absolute inset-y-0 left-0 w-[130px] bg-gradient-to-r from-[#010510] via-[#010510]/80 to-transparent"></div>
                </div>

                {{-- Halo + arc orange (coin bas-gauche) --}}
                <div class="pointer-events-none absolute -bottom-24 -left-20 h-64 w-80 rounded-full bg-orange-600/20 blur-3xl" aria-hidden="true"></div>
                <svg class="pointer-events-none absolute bottom-0 left-0 h-[180px] w-[420px]" viewBox="0 0 420 180" fill="none"
                     preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="beNlArc" x1="0" y1="0.2" x2="1" y2="0.9">
                            <stop offset="0" stop-color="#FF9A3D"/>
                            <stop offset="0.5" stop-color="#FE6200"/>
                            <stop offset="1" stop-color="#FE6200" stop-opacity="0.75"/>
                        </linearGradient>
                    </defs>
                    <path d="M0 106 C 90 154 205 175 410 177 C 215 194 70 196 0 152 Z"
                          fill="url(#beNlArc)" class="drop-shadow-[0_0_16px_rgba(254,98,0,.45)]"/>
                </svg>

                {{-- Contenu : mégaphone + textes --}}
                <div class="relative flex min-h-[180px] items-center gap-3 pl-9 pr-[580px] pt-5">
                    <svg viewBox="0 0 200 190" class="hidden h-[138px] w-[152px] shrink-0 xl:block" fill="none" aria-hidden="true">
                        <defs>
                            <linearGradient id="beNlCone" x1="0" y1="0" x2="0.3" y2="1">
                                <stop offset="0" stop-color="#FFFFFF"/>
                                <stop offset="0.55" stop-color="#F4F3F7"/>
                                <stop offset="1" stop-color="#DAD9E2"/>
                            </linearGradient>
                            <linearGradient id="beNlOrange" x1="0" y1="0" x2="0.4" y2="1">
                                <stop offset="0" stop-color="#FF9A45"/>
                                <stop offset="0.5" stop-color="#FE6200"/>
                                <stop offset="1" stop-color="#E84800"/>
                            </linearGradient>
                            <linearGradient id="beNlInner" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#E9E8F0"/>
                                <stop offset="1" stop-color="#FFFFFF"/>
                            </linearGradient>
                        </defs>
                        <g transform="rotate(-38 100 95)">
                            <line x1="42" y1="102" x2="8" y2="140" stroke="url(#beNlOrange)" stroke-width="26" stroke-linecap="round"/>
                            <rect x="28" y="80" width="30" height="30" rx="10" fill="url(#beNlOrange)"/>
                            <path d="M52 82 L146 53 A14 42 0 0 1 146 137 L52 108 Z" fill="url(#beNlCone)"/>
                            <ellipse cx="146" cy="95" rx="14" ry="42" fill="#FF7A12"/>
                            <ellipse cx="147.5" cy="95" rx="9.5" ry="35" fill="url(#beNlInner)"/>
                            <path d="M168 72 L192 62 M172 95 L198 93 M168 118 L190 128"
                                  stroke="#FF7A00" stroke-width="7" stroke-linecap="round"/>
                        </g>
                    </svg>

                    <div class="min-w-0 flex-1">
                        <h3 class="text-[21px] font-extrabold leading-tight text-white">
                            Restez informé de tous nos <span class="text-[#FF8A1F]">événements</span> !
                        </h3>
                        <p class="mt-1.5 text-[13px] leading-[17px] text-[#BFC1CD]">
                            Recevez en avant-première les prochaines sorties, les offres exclusives<br>
                            et les actualités de Billeterie Express directement dans votre boîte mail.
                        </p>
                    </div>
                </div>

                {{-- Formulaire (recouvre la zone peinte de la photo) --}}
                <form class="absolute right-[214px] top-[80px] z-10 flex h-[52px] w-[366px] items-center gap-2.5 rounded-full border border-white/20 bg-[#0D080F] pl-[18px] pr-2"
                      x-data="{ email: '' }"
                      @submit.prevent="$store.toast.show('Merci ! Vous êtes abonné à notre newsletter.', 'success'); email = ''">
                    <span class="shrink-0 text-white/55" aria-hidden="true">@icon('mail', 'w-[22px] h-[22px]')</span>
                    <input type="email" name="email" required x-model="email" autocomplete="email"
                           placeholder="Votre adresse email"
                           aria-label="Votre adresse email"
                           class="min-w-0 flex-1 bg-transparent text-[14px] text-white placeholder:text-white/45 focus:outline-none">
                    <button type="submit"
                            class="flex h-[44px] w-[126px] shrink-0 items-center justify-center gap-1.5 rounded-full bg-[#FD6500] text-[13.5px] font-semibold text-white transition hover:bg-[#FF7A1A]">
                        S'abonner @icon('arrow-right', 'w-4 h-4')
                    </button>
                </form>
            </div>

            {{-- ================= barre basse ================= --}}
            <div class="mt-9 border-t border-white/10 pt-6">
                <div class="flex flex-col items-center justify-between gap-4 px-4 text-sm sm:flex-row">
                    <p class="text-white/60">
                        © {{ now()->year }} <span class="font-bold text-orange-500">Billeterie Express</span>. Tous droits réservés.
                    </p>

                    <nav class="flex items-center gap-3 text-white/70">
                        <a href="#" class="transition hover:text-orange-400">Conditions d'utilisation</a>
                        <span class="h-4 w-px bg-white/20" aria-hidden="true"></span>
                        <a href="#" class="transition hover:text-orange-400">Politique de confidentialité</a>
                        <span class="h-4 w-px bg-white/20" aria-hidden="true"></span>
                        <a href="#" class="transition hover:text-orange-400">Aide</a>
                    </nav>

                    <p class="flex items-center gap-2.5 text-white/60">
                        <span>Mobile money :</span>
                        <img src="{{ asset('images/logos/wave.png') }}" alt="Wave" class="h-5 w-5 shrink-0 rounded">
                        <img src="{{ asset('images/logos/orange.png') }}" alt="Orange Money" class="h-5 w-5 shrink-0 rounded">
                        <img src="{{ asset('images/logos/mixx.png') }}" alt="Mixx by Yas" class="h-5 w-5 shrink-0 rounded">
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
