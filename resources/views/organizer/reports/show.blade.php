@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('organizer.reports.index') }}" class="text-xs font-medium text-brand-600 hover:underline">← Rapports</a>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-ink-950">{{ $event->title }}</h1>
            <p class="mt-1 text-sm text-ink-600">{{ event_date_fr($event->event_date) }} · {{ $event->location }}</p>
        </div>

        <a href="{{ route('organizer.reports.pdf', $event) }}" class="btn-primary btn-sm">
            @icon('download', 'w-4 h-4') Télécharger le PDF
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-4">
        @include('partials.stat', [
            'label' => 'Réservations',
            'value' => number_format($totals['reservations'], 0, ',', ' '),
            'icon' => 'list',
            'tone' => 'brand',
            'hint' => $totals['paid'].' payée(s)',
        ])
        @include('partials.stat', [
            'label' => 'Tickets',
            'value' => number_format($totals['quantity'], 0, ',', ' '),
            'icon' => 'ticket',
            'tone' => 'violet',
        ])
        @include('partials.stat', [
            'label' => 'Revenus encaissés',
            'value' => money($totals['revenue']),
            'icon' => 'currency',
            'tone' => 'emerald',
        ])
        @include('partials.stat', [
            'label' => 'Stock restant',
            'value' => number_format(max(0, $event->total_tickets - $totals['quantity']), 0, ',', ' '),
            'icon' => 'coins',
            'tone' => 'orange',
            'hint' => 'sur '.$event->total_tickets,
        ])
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client</th>
                        <th>E-mail</th>
                        <th>Type</th>
                        <th>Canal</th>
                        <th class="text-right">Qté</th>
                        <th class="text-right">Montant</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-ink-600">{{ $row['date']?->translatedFormat('d/m/Y H:i') }}</td>
                            <td class="text-sm font-medium text-ink-900">{{ $row['user'] }}</td>
                            <td class="text-xs text-ink-600">{{ $row['email'] }}</td>
                            <td><span class="badge bg-ink-100 text-ink-700">{{ $row['type'] }}</span></td>
                            <td class="text-xs text-ink-600">{{ $row['channel'] === 'physical' ? 'Physique' : 'En ligne' }}</td>
                            <td class="text-right font-semibold text-ink-900">{{ $row['quantity'] }}</td>
                            <td class="text-right font-semibold text-ink-900">{{ money($row['price']) }}</td>
                            <td>
                                <span class="badge bg-{{ $row['status_color'] }}-100 text-{{ $row['status_color'] }}-700">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-sm text-ink-500">Aucune réservation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
