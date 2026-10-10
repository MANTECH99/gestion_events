@extends('layouts.organizer')

@section('content')
    <div class="mb-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Sessions de paiement</h1>
                <p class="mt-1 text-sm text-ink-600">Sessions de paiement DexPay liées à vos événements.</p>
            </div>
            <span class="badge bg-ink-100 text-ink-700">{{ $sessions->total() }} session(s) au total</span>
        </div>

        <form method="GET" class="mt-4 flex flex-wrap items-end gap-3">
            <div class="w-56">
                <label for="status" class="label">Statut</label>
                <select id="status" name="status" class="input">
                    <option value="all" @selected(! $filters['status'])>Tous</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>
                            {{ $statusLabels[$status] ?? ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn-primary btn-sm">
                @icon('filter', 'w-4 h-4') Filtrer
            </button>
        </form>
    </div>

    <div class="card overflow-hidden">
        @if ($sessions->isEmpty())
            @include('partials.empty', [
                'icon' => 'credit-card',
                'title' => 'Aucune session trouvée.',
                'message' => 'Les sessions de paiement DexPay de vos événements apparaîtront ici.',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Réservation liée</th>
                            <th class="text-right">Montant</th>
                            <th class="text-right">Frais</th>
                            <th>Opérateur</th>
                            <th>Statut</th>
                            <th>Créée le</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sessions as $session)
                            @php
                                $colors = match ($session->status) {
                                    'completed', 'success' => 'emerald',
                                    'failed', 'cancelled', 'expired' => 'rose',
                                    'pending', 'in_progress', 'processing' => 'amber',
                                    'refunded' => 'violet',
                                    default => 'ink',
                                };
                                $fee = (int) ($session->payload['fee'] ?? $session->payload['fee_amount'] ?? $session->payload['service_fee'] ?? 0);

                                // DexPay ne renvoie pas les frais de checkout : on
                                // retombe sur la surcharge de service facturée au client.
                                if ($fee <= 0 && $session->reservation) {
                                    $fee = max(0, (int) $session->amount - (int) $session->reservation->total_amount);
                                }
                            @endphp
                            <tr>
                                <td>
                                    <p class="font-mono text-xs font-semibold text-ink-900">{{ $session->reference }}</p>
                                    @if ($session->session_id)
                                        <p class="text-xs text-ink-500">{{ $session->session_id }}</p>
                                    @endif
                                </td>
                                <td>
                                    <p class="font-mono text-xs text-ink-700">
                                        {{ $session->reservation?->reference ?? '—' }}
                                    </p>
                                    <p class="max-w-[14rem] truncate text-xs text-ink-500">
                                        {{ $session->reservation?->event?->title ?? '' }}
                                    </p>
                                </td>
                                <td class="text-right font-semibold text-ink-900">{{ money($session->amount) }}</td>
                                <td class="text-right text-ink-700">{{ $fee > 0 ? money($fee) : '—' }}</td>
                                <td class="text-sm text-ink-800">
                                    {{ \App\Services\DexPay\DexPayAccount::operatorLabel($session->operator) }}
                                </td>
                                <td>
                                    <span class="badge bg-{{ $colors }}-100 text-{{ $colors }}-700">
                                        {{ $statusLabels[$session->status] ?? ucfirst($session->status) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap text-xs text-ink-600">
                                    {{ $session->created_at->translatedFormat('d/m/Y H:i') }}
                                </td>
                                <td class="text-right">
                                    @if (! in_array($session->status, ['completed', 'success', 'failed', 'cancelled', 'expired', 'refunded'], true) && $session->reference)
                                        <form method="POST" action="{{ route('organizer.payment-sessions.sync', $session) }}">
                                            @csrf
                                            <button class="btn-ghost btn-sm" title="Actualiser la session">
                                                @icon('refresh', 'w-4 h-4')
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-3">{{ $sessions->links() }}</div>
        @endif
    </div>
@endsection