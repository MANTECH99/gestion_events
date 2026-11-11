<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#12141c">
    <title>Maintenance en cours — Billeterie Express</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon.svg') }}?v={{ config('app.asset_version') }}">
    @vite(['resources/css/app.css'])
    @if ($endsAt)
        <meta http-equiv="refresh" content="{{ (int) max(1, now()->diffInSeconds($endsAt, false)) }}">
    @endif
    <style>
        .me-shimmer {
            background: linear-gradient(90deg, transparent, rgba(251, 146, 60, .9), transparent);
            background-size: 40% 100%;
            background-repeat: no-repeat;
            animation: me-slide 1.6s ease-in-out infinite;
        }
        @keyframes me-slide {
            0%   { background-position: -40% 0; }
            100% { background-position: 140% 0; }
        }
        @media (prefers-reduced-motion: reduce) {
            .me-shimmer { animation: none; background: rgba(251, 146, 60, .7); }
        }
    </style>
</head>
<body class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-ink-950 px-4 py-10 text-white">

    {{-- Halo orange discret, comme les halos des halos des pages sombres du site --}}
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 left-1/2 h-80 w-[36rem] max-w-full -translate-x-1/2 rounded-full bg-orange-500/15 blur-3xl"></div>
        <div class="absolute -bottom-40 right-0 h-72 w-72 rounded-full bg-orange-400/10 blur-3xl"></div>
    </div>

    <main class="relative w-full max-w-lg">
        <div class="rounded-3xl border border-white/10 bg-white/[0.04] p-7 text-center shadow-2xl shadow-black/40 backdrop-blur-sm sm:p-9">

            {{-- Identité --}}
            <div class="flex items-center justify-center gap-2.5">
                <img src="{{ asset('images/icon.svg') }}?v={{ config('app.asset_version') }}" alt="" width="40" height="40"
                     class="h-10 w-10 rounded-xl shadow-lg shadow-orange-500/30">
                <span class="text-xl font-extrabold tracking-tight text-white">
                    Billeterie <span class="text-orange-400">Express</span>
                </span>
            </div>

            {{-- Badge --}}
            <span class="mt-7 inline-flex items-center gap-2 rounded-full bg-orange-400/15 px-4 py-1.5 text-sm font-medium text-orange-400 ring-1 ring-orange-400/30">
                @icon('settings', 'h-4 w-4 animate-spin')
                Maintenance en cours
            </span>

            <h1 class="mt-5 text-[1.75rem] font-extrabold leading-tight tracking-tight text-white sm:text-4xl">
                Le site est en <span class="text-orange-400">maintenance</span>
            </h1>

            <p class="mx-auto mt-4 max-w-md text-[15px] leading-relaxed text-white/75">
                Nos développeurs travaillent actuellement sur Billeterie Express.
                Nous apportons des améliorations à la plateforme pour vous offrir
                une meilleure expérience de billetterie. Merci de votre patience,
                le service sera de retour très prochainement.
            </p>

            {{-- Fenêtre de maintenance + décompte --}}
            <div class="mt-6 rounded-2xl border border-white/10 bg-ink-950/60 p-4">
                @if ($startsAt && $endsAt)
                    <p class="flex items-start justify-center gap-2 text-[13px] leading-5 text-white/60">
                        @icon('clock', 'mt-0.5 h-4 w-4 shrink-0 text-orange-400')
                        <span>
                            Intervention planifiée du
                            <span class="text-white/85">{{ $startsAt->locale('fr')->isoFormat('dddd D MMMM [à] H:mm') }}</span>
                            au
                            <span class="text-white/85">{{ $endsAt->locale('fr')->isoFormat('dddd D MMMM [à] H:mm') }}</span>
                        </span>
                    </p>
                @elseif ($endsAt)
                    <p class="flex items-start justify-center gap-2 text-[13px] leading-5 text-white/60">
                        @icon('clock', 'mt-0.5 h-4 w-4 shrink-0 text-orange-400')
                        <span>
                            Fin des travaux prévue le
                            <span class="text-white/85">{{ $endsAt->locale('fr')->isoFormat('dddd D MMMM [à] H:mm') }}</span>
                        </span>
                    </p>
                @else
                    <p class="flex items-center justify-center gap-2 text-[13px] text-white/60">
                        @icon('clock', 'h-4 w-4 shrink-0 text-orange-400')
                        Retour prévu d'ici peu — jusqu'à nouvel ordre.
                    </p>
                @endif

                @if ($endsAt)
                    <p class="mt-3 text-xs uppercase tracking-[0.15em] text-white/45">Retour estimé dans</p>
                    <p id="me-countdown"
                       data-target="{{ $endsAt->getTimestamp() }}"
                       class="mt-1 text-2xl font-bold tabular-nums text-orange-400">
                        —
                    </p>
                @endif
            </div>

            {{-- Barre de progression --}}
            <div class="mt-6 h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                <div class="me-shimmer h-full w-full rounded-full"></div>
            </div>

            {{-- Contact --}}
            <p class="mt-6 text-[13px] text-white/55">
                Une question pendant l'intervention ?
                <a href="mailto:{{ config('mail.from.address') }}?subject={{ rawurlencode('Maintenance — Billeterie Express') }}"
                   class="font-medium text-orange-400 underline-offset-4 transition hover:text-orange-300 hover:underline">
                    Écrivez-nous
                </a>
            </p>
        </div>

        <p class="mt-6 text-center text-xs text-white/35">
            © {{ date('Y') }} Billeterie Express — Tous droits réservés
        </p>
    </main>

    @if ($endsAt)
        <script>
            (function () {
                var el = document.getElementById('me-countdown');
                if (!el) return;
                var target = parseInt(el.getAttribute('data-target'), 10);

                function pad(n) { return n < 10 ? '0' + n : String(n); }

                function tick() {
                    var s = target - Math.floor(Date.now() / 1000);
                    if (s <= 0) {
                        // L'heure est passée côté client : le meta refresh sert de
                        // filet (rechargement à l'heure serveur) — pas de boucle.
                        el.textContent = 'Le site revient dans un instant…';
                        return;
                    }
                    var h = Math.floor(s / 3600);
                    var m = Math.floor((s % 3600) / 60);
                    var sec = s % 60;
                    el.textContent = pad(h) + ':' + pad(m) + ':' + pad(sec);
                    setTimeout(tick, 1000);
                }

                tick();
            })();
        </script>
    @endif
</body>
</html>
