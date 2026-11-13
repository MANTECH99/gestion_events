@extends(request()->routeIs('treasurer.*') ? 'layouts.treasurer' : 'layouts.organizer')

@php
    // Espace trésorière : consultation seule (pas de mise à jour du quota
    // ni de suppression, réservées à l'organisateur).
    $isTreasurer = request()->routeIs('treasurer.*');
    $pfx = $isTreasurer ? 'treasurer' : 'organizer';
@endphp

@section('content')
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route($pfx.'.artist-pos.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-ink-500 transition hover:text-brand-600">
                @icon('arrow-left', 'w-4 h-4') Retour aux points de vente artistes
            </a>
            <div class="flex items-center gap-3">
                @if ($artistPos->artist?->image_url)
                    <img src="{{ $artistPos->artist->image_url }}" alt=""
                         class="h-12 w-12 shrink-0 rounded-xl object-cover">
                @else
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-700">
                        @icon('star', 'w-6 h-6')
                    </span>
                @endif
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">
                        {{ $artistPos->artist?->name ?? 'Artiste supprimé' }}
                        @if ($artistPos->artist?->phone)
                            <span class="ml-2 align-middle text-sm font-medium text-ink-500">{{ $artistPos->artist->phone }}</span>
                        @endif
                    </h1>
                    <p class="mt-0.5 text-sm text-ink-600">
                        {{ $artistPos->event?->title ?? 'Événement supprimé' }}
                        @if ($artistPos->event?->event_date)
                            · {{ event_date_fr($artistPos->event->event_date, 'd M Y H:i') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Les 4 cartes de synthèse du point de vente --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('partials.stat', [
            'label' => 'Montant total',
            'value' => money($revenue),
            'icon' => 'currency',
            'tone' => 'emerald',
            'hint' => 'encaissés via le lien',
        ])
        @include('partials.stat', [
            'label' => 'Solde de billet total',
            'value' => number_format((int) $artistPos->ticket_limit, 0, ',', ' '),
            'icon' => 'ticket',
            'tone' => 'brand',
            'hint' => 'billets assignés au lien',
        ])
        @include('partials.stat', [
            'label' => 'Billets vendus',
            'value' => number_format($sold, 0, ',', ' '),
            'icon' => 'check-circle',
            'tone' => 'violet',
            'hint' => 'réservations payées via le lien',
        ])
        @include('partials.stat', [
            'label' => 'Solde restant',
            'value' => number_format($remaining, 0, ',', ' '),
            'icon' => 'wallet',
            'tone' => $remaining > 0 ? 'orange' : 'rose',
            'hint' => $remaining > 0 ? 'encore disponibles via le lien' : 'quota épuisé',
        ])
    </div>

    {{-- Lien de partage de l'artiste --}}
    <div class="card mt-6 p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="flex items-center gap-2 font-semibold text-ink-900">
                @icon('share', 'w-4 h-4 text-brand-600') Lien de partage
                @if ($remaining > 0)
                    <span class="badge bg-emerald-100 text-emerald-700">{{ $remaining }} billet(s) restant(s)</span>
                @else
                    <span class="badge bg-rose-100 text-rose-700">Stock épuisé</span>
                @endif
            </h2>
            <a href="{{ $artistPos->url() }}" target="_blank" rel="noopener" class="btn-ghost btn-sm">
                @icon('arrow-right', 'w-4 h-4') Ouvrir le lien
            </a>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <input type="text" readonly value="{{ $artistPos->url() }}" aria-label="Lien de partage"
                   class="input min-w-0 flex-1 truncate font-mono text-xs"
                   onclick="this.select()">
            <button type="button" class="btn-ghost btn-sm shrink-0"
                    @click="navigator.clipboard.writeText('{{ $artistPos->url() }}').then(() => $store.toast.show('Lien copié dans le presse-papiers.', 'success')).catch(() => $store.toast.show('Impossible de copier le lien.', 'error'))">
                @icon('copy', 'w-4 h-4') Copier
            </button>
        </div>

        <p class="mt-2 text-xs text-ink-500">
            L'artiste partage ce lien : chaque réservation en ligne créée par ici lui est créditée
            (le nom de l'artiste apparaît sous « En ligne » dans la liste des réservations).
        </p>
    </div>

    {{-- Ajustement du quota (organisateur) --}}
    @unless ($isTreasurer)
        <form method="POST" action="{{ route('organizer.artist-pos.update', $artistPos) }}" class="card mt-6 p-5">
            @csrf @method('PUT')
            <label for="ticket_limit" class="label">Nombre de billets assignés</label>

            {{-- Champ et bouton alignés sur la même ligne (desktop comme mobile) --}}
            <div class="flex items-center gap-3">
                <input id="ticket_limit" name="ticket_limit" type="number" min="1" step="1" required
                       value="{{ old('ticket_limit', $artistPos->ticket_limit) }}"
                       class="input min-w-0 flex-1 @error('ticket_limit') input-error @enderror">
                <button type="submit" class="btn-primary shrink-0 whitespace-nowrap">
                    @icon('check', 'w-4 h-4') Mettre à jour
                </button>
            </div>

            <p class="mt-1.5 text-xs text-ink-500">
                Vous pouvez l'augmenter à tout moment — jamais en dessous de {{ $sold }} (déjà vendu(s)).
            </p>
            @error('ticket_limit')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </form>
    @endunless

    {{-- Ventes réalisées via le lien --}}
    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-ink-100 px-5 py-4">
            <h2 class="font-semibold text-ink-900">Ventes via ce lien</h2>
        </div>

        @if ($sales->isEmpty())
            @include('partials.empty', [
                'icon' => 'receipt',
                'title' => 'Aucune vente',
                'message' => 'Les réservations créées via le lien de l\'artiste apparaîtront ici.',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Événement / Type</th>
                            <th>Acheteur</th>
                            <th>Paiement</th>
                            <th>Statut paiement</th>
                            <th class="text-right">Quantité</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr>
                                <td class="whitespace-nowrap text-xs text-ink-600">{{ event_date_fr($sale->created_at, 'd M Y H:i') }}</td>
                                <td>
                                    <p class="max-w-[14rem] truncate text-sm text-ink-800">{{ $sale->event?->title ?? $artistPos->event?->title ?? '—' }}</p>
                                    <p class="text-xs text-ink-500">{{ $sale->ticketType?->name ?? '—' }}</p>
                                </td>
                                <td>
                                    <p class="text-sm font-medium text-ink-900">{{ $sale->customer_name }}</p>
                                    @if ($sale->customer_phone)
                                        <p class="text-xs text-ink-500">{{ $sale->customer_phone }}</p>
                                    @endif
                                </td>
                                <td class="text-sm text-ink-800">{{ $sale->payment_operator_label }}</td>
                                <td>
                                    <span class="badge bg-{{ $sale->status_color }}-100 text-{{ $sale->status_color }}-700">
                                        {{ $sale->status_label }}
                                    </span>
                                </td>
                                <td class="text-right font-semibold text-ink-900">{{ $sale->quantity }}</td>
                                <td class="text-right font-semibold text-ink-900">{{ money($sale->total_amount) }}</td>
                                <td class="text-right">
                                    @if ($sale->isPaid() && $sale->tickets->isNotEmpty())
                                        <div class="flex justify-end gap-1.5">
                                            {{-- Enregistrer le billet (image), comme dans Réservations --}}
                                            <div x-data="ticketImage" class="inline-flex">
                                                <button type="button" :disabled="busy"
                                                        @click="captureFromUrl('{{ URL::signedRoute($pfx.'.reservations.cards', $sale) }}', '{{ $sale->reference }}')"
                                                        class="btn-ghost btn-sm disabled:opacity-60" title="Enregistrer le billet (image)">
                                                    @icon('ticket', 'w-4 h-4')
                                                </button>
                                            </div>

                                            {{-- Voir le billet (page du billet, lien signé) --}}
                                            <a href="{{ URL::signedRoute('tickets.reservation', ['reference' => $sale->reference]) }}"
                                               target="_blank" rel="noopener"
                                               class="btn-ghost btn-sm" title="Voir le billet">
                                                @icon('eye', 'w-4 h-4')
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-xs text-ink-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-3">{{ $sales->links() }}</div>
        @endif
    </div>
@endsection
