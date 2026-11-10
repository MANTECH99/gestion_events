@extends('layouts.app', [
    'title' => 'Devenir partenaire',
    'description' => "Organisateur d'événements, point de vente ou entreprise : devenez partenaire de Billeterie Express et vendez vos billets en toute sécurité au Sénégal (Wave, Orange Money).",
])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp

    {{-- Héro --}}
    <section class="relative overflow-hidden bg-ink-950">
        <div class="absolute inset-0" aria-hidden="true">
            <div class="absolute inset-0" style="background:
                linear-gradient(180deg, rgba(6,18,44,.85) 0%, rgba(14,48,96,.62) 40%, rgba(10,28,64,.35) 60%, rgba(8,12,28,0) 78%);"></div>
            <div class="absolute inset-0" style="background:
                radial-gradient(46% 40% at 52% 18%, rgba(249,115,22,.30), rgba(249,115,22,0) 72%);"></div>
            <div class="absolute inset-0" style="background:
                radial-gradient(40% 34% at 84% 74%, rgba(40,110,200,.28), rgba(40,110,200,0) 72%);"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-ink-950/80 via-transparent to-ink-950/40"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 pb-14 {{ $hasFlash ? 'pt-6 sm:pt-8' : 'pt-28 sm:pt-32' }} sm:px-6 lg:px-8 lg:pb-20 {{ $hasFlash ? 'lg:pt-10' : 'lg:pt-40' }}">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center gap-2.5 rounded-full bg-white/10 py-2 pl-3 pr-4 text-sm font-medium text-white ring-1 ring-white/20 backdrop-blur">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-orange-500 text-ink-950">
                        @icon('users', 'w-3.5 h-3.5')
                    </span>
                    Programme partenaire · Sénégal
                </span>

                <h1 class="mt-7 text-4xl font-extrabold leading-[1.06] tracking-tight text-white sm:text-5xl lg:text-[3.4rem]">
                    Devenez
                    <span class="relative inline-block text-orange-400">partenaire
                        <svg class="absolute -bottom-2.5 left-0 h-3.5 w-full" viewBox="0 0 220 14" fill="none"
                             preserveAspectRatio="none" aria-hidden="true">
                            <path d="M4 9 C 70 3, 160 3, 216 8" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <br class="hidden sm:block">de Billeterie Express
                </h1>

                <p class="mx-auto mt-7 max-w-2xl leading-relaxed text-white/70 sm:text-lg">
                    Vous organisez un événement, tenez un point de vente ou représentez une marque ?
                    Rejoignez la billetterie en ligne qui fait vibrer le Sénégal et touchez des milliers
                    de spectateurs, en toute sécurité.
                </p>

                <div class="mt-9 flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                    <a href="#formules" class="btn-hero-primary">
                        Découvrir les formules @icon('arrow-right', 'w-5 h-5')
                    </a>
                    <a href="mailto:{{ config('mail.from.address') }}?subject={{ rawurlencode('Partenariat — Billeterie Express') }}"
                       class="btn-hero-ghost">
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-white/15">
                            @icon('mail', 'w-3.5 h-3.5')
                        </span>
                        Nous contacter
                    </a>
                </div>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-x-7 gap-y-3 text-sm text-white/65">
                    @foreach ([
                        ['icon' => 'check-circle', 'label' => 'Inscription sans engagement'],
                        ['icon' => 'shield', 'label' => 'Paiement sécurisé DexPay'],
                        ['icon' => 'headset', 'label' => 'Accompagnement dédié'],
                    ] as $trust)
                        <span class="inline-flex items-center gap-2">
                            <span class="text-orange-400">@icon($trust['icon'], 'w-4 h-4')</span>
                            {{ $trust['label'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Chiffres clés --}}
    <section class="border-y border-white/10 bg-ink-950">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-x-4 gap-y-8 px-4 py-10 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div class="text-center lg:text-left">
                <p class="text-2xl font-extrabold text-white sm:text-3xl">+50K</p>
                <p class="mt-1 text-sm leading-snug text-white/55">Billets vendus<br>au Sénégal</p>
            </div>
            <div class="text-center lg:border-l lg:border-white/10 lg:pl-8 lg:text-left">
                <p class="text-2xl font-extrabold text-white sm:text-3xl">+20</p>
                <p class="mt-1 text-sm leading-snug text-white/55">Événements<br>par an</p>
            </div>
            <div class="text-center lg:border-l lg:border-white/10 lg:pl-8 lg:text-left">
                <p class="text-2xl font-extrabold text-white sm:text-3xl">QR</p>
                <p class="mt-1 text-sm leading-snug text-white/55">Billets sécurisés,<br>scannés en 1 seconde</p>
            </div>
            <div class="text-center lg:border-l lg:border-white/10 lg:pl-8 lg:text-left">
                <p class="text-base font-bold leading-snug text-white sm:text-lg">Wave · Orange Money</p>
                <div class="mt-2 flex justify-center lg:justify-start">@include('partials.payment-methods')</div>
            </div>
        </div>
    </section>

    <div class="bg-ink-950 pb-16 pt-14 sm:pt-16">
        <div class="mx-auto max-w-7xl space-y-16 px-4 sm:px-6 lg:px-8">

            {{-- Pourquoi nous rejoindre --}}
            <section id="avantages" class="scroll-mt-24">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-400">Pourquoi nous rejoindre ?</p>
                    <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                        Tout ce qu'il faut pour <span class="text-orange-400">réussir</span> vos événements
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/60 sm:text-base">
                        De la publication à l'encaissement, nous gérons la billetterie pendant que vous
                        vous concentrez sur l'essentiel : l'expérience de vos spectateurs.
                    </p>
                </div>

                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['icon' => 'globe', 'title' => 'Une visibilité nationale', 'text' => "Vos événements exposés à des milliers d'utilisateurs déjà prêts à réserver."],
                        ['icon' => 'qr-code', 'title' => 'Billets QR anti-fraude', 'text' => 'Chaque billet est unique et contrôlé par scan à l\'entrée.'],
                        ['icon' => 'wallet', 'title' => 'Encaissements rapides', 'text' => 'Vos clients paient avec Wave, Orange Money ou carte, en toute sécurité.'],
                        ['icon' => 'chart-bar', 'title' => 'Tableau de bord en direct', 'text' => 'Ventes, validations, revenus : suivez tout en temps réel, où que vous soyez.'],
                        ['icon' => 'store', 'title' => 'Réseau de points de vente', 'text' => 'Vendez aussi hors ligne grâce à vos points de vente agréés.'],
                        ['icon' => 'headset', 'title' => 'Une équipe à vos côtés', 'text' => 'Support réactif avant, pendant et après votre événement.'],
                    ] as $benefit)
                        <div class="group rounded-2xl border border-white/10 bg-white/5 p-6 transition hover:border-orange-400/40 hover:bg-white/[.07]">
                            <span class="grid h-12 w-12 place-items-center rounded-xl border-2 border-orange-400/40 bg-orange-400/10 text-orange-400 transition group-hover:border-orange-400">
                                @icon($benefit['icon'], 'w-5 h-5')
                            </span>
                            <h3 class="mt-4 text-sm font-bold text-white">{{ $benefit['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-white/60">{{ $benefit['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Formules de partenariat --}}
            <section id="formules" class="scroll-mt-24">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-400">Nos formules</p>
                    <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                        Un partenariat <span class="text-orange-400">adapté</span> à votre activité
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/60 sm:text-base">
                        Choisissez le profil qui vous ressemble : nous nous occupons du reste.
                    </p>
                </div>

                <div class="mt-10 grid items-start gap-5 lg:grid-cols-3">
                    @foreach ([
                        [
                            'icon' => 'megaphone',
                            'label' => 'Organisateur',
                            'title' => "Organisateur d'événements",
                            'text' => 'Concerts, festivals, matchs, conférences, soirées… publiez et vendez en ligne.',
                            'features' => [
                                'Billets QR uniques et sécurisés',
                                'Vente en ligne Wave, Orange Money, carte',
                                'Tableau de bord et rapports détaillés',
                                'Agents de contrôle et points de vente',
                            ],
                            'cta' => 'Créer un compte',
                            'href' => route('register'),
                            'featured' => true,
                        ],
                        [
                            'icon' => 'store',
                            'label' => 'Point de vente',
                            'title' => 'Point de vente agréé',
                            'text' => 'Revendez des billets physiquement pour les événements de nos organisateurs.',
                            'features' => [
                                'Espace dédié aux ventes sur place',
                                'Stock alloué par événement',
                                'Suivi des ventes et des commissions',
                                'Impression des QR codes sur place',
                            ],
                            'cta' => 'Devenir point de vente',
                            'href' => 'mailto:'.config('mail.from.address').'?subject='.rawurlencode('Partenariat point de vente — Billeterie Express'),
                            'featured' => false,
                        ],
                        [
                            'icon' => 'briefcase',
                            'label' => 'Entreprise',
                            'title' => 'Partenaire entreprise',
                            'text' => 'Marques, sponsors, médias : associez votre image aux grands événements du Sénégal.',
                            'features' => [
                                'Visibilité sur nos événements et pages',
                                'Billets offerts à vos équipes et clients',
                                'Opérations de communication conjointes',
                                'Facturation simple et transparente',
                            ],
                            'cta' => 'Parler à notre équipe',
                            'href' => 'mailto:'.config('mail.from.address').'?subject='.rawurlencode('Partenariat entreprise — Billeterie Express'),
                            'featured' => false,
                        ],
                    ] as $plan)
                        <div class="relative flex flex-col rounded-2xl border p-6 sm:p-7 {{ $plan['featured'] ? 'border-orange-400/60 bg-orange-500/[.07] shadow-lg shadow-orange-500/10' : 'border-white/10 bg-white/5' }}">
                            @if ($plan['featured'])
                                <span class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-orange-500 px-3.5 py-1 text-xs font-bold text-ink-950 shadow-lg shadow-orange-500/30">
                                    Le plus choisi
                                </span>
                            @endif

                            <span class="grid h-12 w-12 place-items-center rounded-xl {{ $plan['featured'] ? 'bg-orange-500 text-ink-950' : 'bg-white/10 text-orange-400' }}">
                                @icon($plan['icon'], 'w-6 h-6')
                            </span>

                            <p class="mt-4 text-xs font-bold uppercase tracking-[0.16em] {{ $plan['featured'] ? 'text-orange-400' : 'text-white/45' }}">
                                {{ $plan['label'] }}
                            </p>
                            <h3 class="mt-1.5 text-lg font-extrabold tracking-tight text-white">{{ $plan['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-white/60">{{ $plan['text'] }}</p>

                            <ul class="mt-5 flex-1 space-y-2.5">
                                @foreach ($plan['features'] as $feature)
                                    <li class="flex items-start gap-2.5 text-sm text-white/75">
                                        <span class="mt-0.5 shrink-0 text-orange-400">@icon('check', 'w-4 h-4')</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ $plan['href'] }}"
                               class="mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-bold transition {{ $plan['featured'] ? 'bg-orange-400 text-ink-950 hover:bg-orange-300' : 'border border-white/25 text-white hover:bg-white/10' }}">
                                {{ $plan['cta'] }} @icon('arrow-right', 'w-4 h-4')
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Comment ça marche --}}
            <section id="etapes" class="scroll-mt-24">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-400">Comment ça marche ?</p>
                    <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                        Opérationnel en <span class="text-orange-400">4 étapes</span>
                    </h2>
                </div>

                <ol class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['icon' => 'user-plus', 'title' => 'Créez votre compte', 'text' => 'Inscription gratuite en 2 minutes, sans engagement.'],
                        ['icon' => 'calendar', 'title' => 'Ajoutez votre événement', 'text' => 'Titre, date, lieu, tarifs, photos : tout en quelques clics.'],
                        ['icon' => 'share', 'title' => 'Partagez le lien', 'text' => 'Réseaux sociaux, WhatsApp, affiches avec QR code.'],
                        ['icon' => 'coins', 'title' => 'Encaissez', 'text' => 'Vos ventes sont suivies en direct et vos fonds retirables à tout moment.'],
                    ] as $index => $step)
                        <li class="relative rounded-2xl border border-white/10 bg-white/5 p-6">
                            <span class="absolute right-5 top-4 text-5xl font-extrabold leading-none text-white/[.07]">
                                {{ $index + 1 }}
                            </span>
                            <span class="grid h-11 w-11 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                                @icon($step['icon'], 'w-5 h-5')
                            </span>
                            <h3 class="mt-4 text-sm font-bold text-white">{{ $step['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-white/60">{{ $step['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- FAQ --}}
            <section id="faq" class="scroll-mt-24">
                <div class="mx-auto max-w-3xl">
                    <div class="text-center">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-orange-400">Questions fréquentes</p>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                            Tout ce qu'il faut <span class="text-orange-400">savoir</span>
                        </h2>
                    </div>

                    <div class="mt-8 space-y-3" x-data="{ open: null }">
                        @foreach ([
                            [
                                'q' => "L'inscription est-elle payante ?",
                                'a' => "Non. Créer un compte partenaire et publier vos événements est entièrement gratuit. Une commission est appliquée uniquement sur les billets effectivement vendus — la tarification vous est communiquée clairement lors de l'ouverture de votre compte.",
                            ],
                            [
                                'q' => 'Comment suis-je payé ?',
                                'a' => "Chaque vente est créditée dans votre espace, visible en temps réel. Vous demandez un retrait vers votre numéro Wave ou Orange Money, et les fonds vous sont versés rapidement, avec un historique complet de vos opérations.",
                            ],
                            [
                                'q' => "Faut-il un compte pour acheter un billet ?",
                                'a' => "Non. Vos clients réservent et paient en quelques secondes, sans création de compte. Ils reçoivent leur billet QR par e-mail et peuvent le télécharger à tout moment.",
                            ],
                            [
                                'q' => 'Comment les billets sont-ils sécurisés ?',
                                'a' => "Chaque billet possède un code QR unique. À l'entrée, vos agents scannent le billet avec l'application : il est marqué « utilisé » dès le premier scan et ne peut plus être réutilisé.",
                            ],
                            [
                                'q' => 'Proposez-vous un accompagnement ?',
                                'a' => "Oui. Notre équipe vous aide à la mise en ligne de votre événement, forme vos points de vente et reste disponible avant, pendant et après l'événement.",
                            ],
                        ] as $index => $faq)
                            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5 transition"
                                 :class="open === {{ $index }} ? 'border-orange-400/50' : ''">
                                <button type="button" @click="open = open === {{ $index }} ? null : {{ $index }}"
                                        :aria-expanded="open === {{ $index }} ? 'true' : 'false'"
                                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left">
                                    <span class="text-sm font-semibold text-white sm:text-base">{{ $faq['q'] }}</span>
                                    <span class="shrink-0 text-orange-400 transition"
                                          :class="open === {{ $index }} ? 'rotate-180' : ''">
                                        @icon('chevron-down', 'w-5 h-5')
                                    </span>
                                </button>
                                <div x-show="open === {{ $index }}" x-transition.opacity.duration.150ms
                                     x-cloak class="px-5 pb-5">
                                    <p class="text-sm leading-relaxed text-white/65">{{ $faq['a'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- CTA final --}}
            <section class="relative overflow-hidden rounded-3xl border border-orange-500/25" style="background: #160d08;">
                <div class="absolute inset-y-0 right-0 hidden w-[55%] sm:block" aria-hidden="true">
                    <img src="{{ asset('images/hero-fans-v2.jpg') }}" alt="" loading="lazy"
                         class="h-full w-full object-cover"
                         style="object-position: center 35%;
                                -webkit-mask-image: linear-gradient(to right, transparent 0%, #000 35%);
                                mask-image: linear-gradient(to right, transparent 0%, #000 35%);">
                    <div class="absolute inset-0" style="background: linear-gradient(90deg, #160d08 0%, rgba(22,13,8,.85) 30%, rgba(234,88,12,.45) 70%, rgba(249,115,22,.55) 100%);"></div>
                    <div class="absolute inset-0" style="background: radial-gradient(50% 80% at 70% 50%, rgba(249,115,22,.5), transparent 70%);"></div>
                </div>

                <div class="relative z-10 flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between sm:p-8 lg:p-10">
                    <div class="flex items-start gap-4">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-orange-500/20 text-orange-400">
                            @icon('megaphone', 'w-6 h-6')
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-lg font-extrabold text-white sm:text-xl">Prêt à lancer votre événement ?</h3>
                            <p class="mt-1 text-sm text-white/70">
                                Créez votre compte partenaire gratuitement ou écrivez-nous :
                                nous répondons sous 24 h ouvrées.
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col gap-3 sm:flex-row">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-orange-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-orange-400">
                            Créer un compte @icon('arrow-right', 'w-4 h-4')
                        </a>
                        <a href="mailto:{{ config('mail.from.address') }}?subject={{ rawurlencode('Partenariat — Billeterie Express') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-full border border-white/25 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                            @icon('mail', 'w-4 h-4') {{ config('mail.from.address') }}
                        </a>
                    </div>
                </div>
            </section>

        </div>
    </div>
@endsection
