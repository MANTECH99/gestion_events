@extends('layouts.app', [
    'description' => 'Découvrez les concerts, festivals et événements à venir au Sénégal. Réservez en ligne et payez avec Wave ou Orange Money.',
    'schema' => [\App\Support\Seo::website()],
])

@section('content')

    {{-- Héro --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 gradient-brand"></div>
        <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle at 20% 20%, rgba(255,255,255,.45) 0, transparent 45%), radial-gradient(circle at 80% 10%, rgba(255,255,255,.35) 0, transparent 40%);"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-white ring-1 ring-white/25 backdrop-blur">
                    @icon('star', 'w-3.5 h-3.5') {{ $totalTickets }} événements à venir
                </span>

                <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Vos billets, <span class="text-accent-300">en un clic</span>
                </h1>

                <p class="mx-auto mt-5 max-w-xl text-base leading-relaxed text-white/85 sm:text-lg">
                    Réservez vos places pour les meilleurs événements du Sénégal et payez en toute
                    sécurité avec <strong class="font-semibold text-white">Wave</strong> ou
                    <strong class="font-semibold text-white">Orange Money</strong>.
                </p>

                <div class="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="#evenements" class="btn-accent w-full sm:w-auto">
                        @icon('ticket', 'w-5 h-5') Voir les événements
                    </a>
                    <a href="{{ route('agent.scan') }}" class="btn-invert w-full sm:w-auto">
                        @icon('scan', 'w-5 h-5') Scanner un billet
                    </a>
                </div>

                <dl class="mx-auto mt-12 grid max-w-2xl grid-cols-3 gap-4 text-center">
                    <div class="rounded-2xl bg-white/10 px-3 py-4 ring-1 ring-white/15 backdrop-blur">
                        <dt class="text-xs text-white/80">Paiement</dt>
                        <dd class="mt-1 text-lg font-bold text-white">Wave · OM</dd>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-3 py-4 ring-1 ring-white/15 backdrop-blur">
                        <dt class="text-xs text-white/80">Ticket</dt>
                        <dd class="mt-1 text-lg font-bold text-white">QR unique</dd>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-3 py-4 ring-1 ring-white/15 backdrop-blur">
                        <dt class="text-xs text-white/80">Validation</dt>
                        <dd class="mt-1 text-lg font-bold text-white">Immédiate</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    {{-- Filtres --}}
    <section id="evenements" class="mx-auto max-w-7xl scroll-mt-20 px-4 py-12 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">Événements à venir</h2>
                <p class="mt-1.5 text-sm text-ink-600">
                    <span class="font-semibold text-ink-900">{{ $events->total() }}</span>
                    événement{{ $events->total() > 1 ? 's' : '' }} disponible{{ $events->total() > 1 ? 's' : '' }}
                </p>
            </div>

            <form method="GET" class="flex w-full flex-col gap-2.5 sm:w-auto sm:flex-row">
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-400">
                        @icon('search', 'w-4 h-4')
                    </span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher un événement…"
                           class="input sm:w-64 sm:pl-9">
                </div>

                <select name="type" class="input sm:w-48" onchange="this.form.submit()">
                    <option value="">Tous les types</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn-primary">Filtrer</button>
            </form>
        </div>

        @if ($events->isEmpty())
            <div class="card mt-8 flex flex-col items-center px-6 py-16 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-2xl bg-ink-100 text-3xl">🎭</span>
                <h3 class="mt-5 text-lg font-bold text-ink-900">Aucun événement trouvé</h3>
                <p class="mt-1.5 max-w-sm text-sm text-ink-600">
                    Modifiez vos filtres ou revenez plus tard : de nouveaux événements sont ajoutés régulièrement.
                </p>
                <a href="{{ route('home') }}" class="btn-outline btn-sm mt-5">Réinitialiser les filtres</a>
            </div>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    @include('partials.event-card', ['event' => $event])
                @endforeach
            </div>

            <div class="mt-10">{{ $events->links() }}</div>
        @endif
    </section>

    {{-- Réassurance --}}
    <section class="border-y border-ink-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-8 sm:grid-cols-3">
                @foreach ([
                    ['icon' => 'shield', 'title' => 'Paiement 100 % sécurisé', 'text' => 'Vos paiements passent par DexPay (DEXCHANGE PAY). Aucune donnée bancaire ne transite par nos serveurs.'],
                    ['icon' => 'ticket', 'title' => 'Ticket à QR code unique', 'text' => 'Chaque billet possède son propre QR code, à présenter à l\'entrée. Valable une seule fois.'],
                    ['icon' => 'scan', 'title' => 'Contrôle instantané', 'text' => 'Les agents valident les billets en quelques secondes. Un billet déjà utilisé est automatiquement refusé.'],
                ] as $item)
                    <div>
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-brand-600">
                            @icon($item['icon'], 'w-6 h-6')
                        </span>
                        <h3 class="mt-4 font-bold text-ink-900">{{ $item['title'] }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-ink-600">{{ $item['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

@endsection