@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('organizer.pos.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm text-ink-500 transition hover:text-brand-600">
                @icon('arrow-left', 'w-4 h-4') Retour aux points de vente
            </a>
            <div class="flex items-center gap-3">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brand-100 text-base font-bold text-brand-700">
                    {{ $pointOfSale->initials }}
                </span>
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">{{ $pointOfSale->full_name }}</h1>
                    <p class="mt-0.5 text-sm text-ink-600">
                        {{ $pointOfSale->point_of_sale ?: 'Point de vente' }}
                        @if ($pointOfSale->phone)
                            · {{ $pointOfSale->phone }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        @include('partials.stat', [
            'label' => 'Billets vendus',
            'value' => number_format($stats['tickets'], 0, ',', ' '),
            'icon' => 'ticket',
            'tone' => 'violet',
            'hint' => 'tickets physiques écoulés',
        ])
        @include('partials.stat', [
            'label' => 'Recette encaissée',
            'value' => money($stats['revenue']),
            'icon' => 'currency',
            'tone' => 'emerald',
            'hint' => 'encaissements au comptoir',
        ])
        @include('partials.stat', [
            'label' => 'Opérations',
            'value' => number_format($stats['operations'], 0, ',', ' '),
            'icon' => 'receipt',
            'tone' => 'orange',
            'hint' => 'ventes déclarées',
        ])
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-ink-100 px-5 py-4">
            <h2 class="font-semibold text-ink-900">Historique des ventes</h2>
        </div>

        @if ($sales->isEmpty())
            @include('partials.empty', [
                'icon' => 'receipt',
                'title' => 'Aucune vente',
                'message' => 'Les ventes déclarées par ce point de vente apparaîtront ici.',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Événement</th>
                            <th>Type</th>
                            <th>Client</th>
                            <th class="text-right">Qté</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            <tr>
                                <td class="whitespace-nowrap text-xs text-ink-600">{{ event_date_fr($sale->created_at, 'd M Y') }}</td>
                                <td>
                                    <p class="max-w-[14rem] truncate text-sm text-ink-800">{{ $sale->ticketType?->event?->title ?? '—' }}</p>
                                </td>
                                <td>
                                    <p class="text-sm text-ink-700">{{ $sale->ticketType?->name ?? '—' }}</p>
                                </td>
                                <td>
                                    <p class="text-sm font-medium text-ink-900">{{ $sale->buyer_name }}</p>
                                    @if ($sale->buyer_phone)
                                        <p class="text-xs text-ink-500">{{ $sale->buyer_phone }}</p>
                                    @endif
                                </td>
                                <td class="text-right font-semibold text-ink-900">{{ $sale->quantity }}</td>
                                <td class="text-right font-semibold text-ink-900">{{ money($sale->total_amount, false) }} F</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-3">{{ $sales->links() }}</div>
        @endif
    </div>
@endsection