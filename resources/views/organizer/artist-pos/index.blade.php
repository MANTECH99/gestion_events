@extends(request()->routeIs('treasurer.*') ? 'layouts.treasurer' : 'layouts.organizer')

@php
    // Espace trésorière : consultation seule (pas de création ni de suppression).
    $isTreasurer = request()->routeIs('treasurer.*');
    $pfx = $isTreasurer ? 'treasurer' : 'organizer';
@endphp

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Points de vente artistes</h1>
            <p class="mt-1 text-sm text-ink-600">
                Un lien par artiste vers la page de l'événement, avec un nombre de billets assignés.
            </p>
        </div>

        @unless ($isTreasurer)
            <a href="{{ route('organizer.artist-pos.create') }}" class="btn-primary btn-sm">
                @icon('plus', 'w-4 h-4') Nouveau point de vente
            </a>
        @endunless
    </div>

    @if ($pointsOfSale->isEmpty())
        <div class="card">
            @include('partials.empty', [
                'icon' => 'share',
                'title' => 'Aucun point de vente artiste',
                'message' => 'Créez un point de vente pour assigner des billets à un artiste et lui donner un lien de partage traçable.',
                'action' => $isTreasurer ? null : route('organizer.artist-pos.create'),
                'actionLabel' => $isTreasurer ? null : 'Créer un point de vente',
            ])
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($pointsOfSale as $pos)
                @php
                    $sold = (int) ($pos->tickets_sold_sum ?? 0);
                    $remaining = max(0, (int) $pos->ticket_limit - $sold);
                @endphp
                <div class="card flex flex-col p-5">
                    <div class="flex items-start gap-3">
                        @if ($pos->artist?->image_url)
                            <img src="{{ $pos->artist->image_url }}" alt=""
                                 class="h-11 w-11 shrink-0 rounded-xl object-cover">
                        @else
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-700">
                                @icon('star', 'w-5 h-5')
                            </span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold text-ink-950">
                                {{ $pos->artist?->name ?? 'Artiste supprimé' }}
                                @if ($pos->artist?->phone)
                                    <span class="ml-1.5 text-xs font-normal text-ink-500">{{ $pos->artist->phone }}</span>
                                @endif
                            </p>
                            <p class="truncate text-xs text-ink-500">
                                {{ $pos->event?->title ?? 'Événement supprimé' }}
                                @if ($pos->event?->event_date)
                                    · {{ event_date_fr($pos->event->event_date, 'd M Y') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1.5">
                            <a href="{{ route($pfx.'.artist-pos.show', $pos) }}" class="btn-ghost btn-sm">
                                @icon('eye', 'w-4 h-4') Voir
                            </a>

                            @unless ($isTreasurer)
                                <form method="POST" action="{{ route('organizer.artist-pos.destroy', $pos) }}"
                                      onsubmit="return confirm('Supprimer ce point de vente ? Le lien ne fonctionnera plus.');">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost btn-sm !px-2.5 text-rose-600" title="Supprimer le point de vente">
                                        @icon('trash', 'w-4 h-4')
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3 border-t border-ink-100 pt-4 text-sm">
                        <div>
                            <p class="text-xs text-ink-500">Billets assignés</p>
                            <p class="mt-0.5 font-bold text-ink-900">{{ number_format((int) $pos->ticket_limit, 0, ',', ' ') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500">Billets vendus</p>
                            <p class="mt-0.5 font-bold text-ink-900">{{ number_format($sold, 0, ',', ' ') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500">Solde restant</p>
                            <p class="mt-0.5 font-bold {{ $remaining > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ number_format($remaining, 0, ',', ' ') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-center gap-2 border-t border-ink-100 pt-4">
                        <input type="text" readonly value="{{ $pos->url() }}" aria-label="Lien de partage"
                               class="input flex-1 truncate font-mono text-xs"
                               onclick="this.select()">
                        <button type="button" class="btn-ghost btn-sm shrink-0"
                                title="Copier le lien"
                                @click="navigator.clipboard.writeText('{{ $pos->url() }}').then(() => $store.toast.show('Lien copié dans le presse-papiers.', 'success')).catch(() => $store.toast.show('Impossible de copier le lien.', 'error'))">
                            @icon('copy', 'w-4 h-4')
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">{{ $pointsOfSale->links() }}</div>
    @endif
@endsection
