@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Réservations</h1>
            <p class="mt-1 text-sm text-ink-600">Réservations en ligne et ventes physiques déclarées.</p>
        </div>
    </div>

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Référence, nom, e-mail, téléphone, ville…" class="input lg:col-span-2">

        <select name="event_id" class="input">
            <option value="">Tous les événements</option>
            @foreach ($events as $e)
                <option value="{{ $e->id }}" @selected((int) ($filters['event_id'] ?? 0) === $e->id)>{{ $e->title }}</option>
            @endforeach
        </select>

        <select name="status" class="input">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\Reservation::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="channel" class="input">
            <option value="">En ligne & physique</option>
            <option value="online" @selected(($filters['channel'] ?? '') === 'online')>En ligne</option>
            <option value="physical" @selected(($filters['channel'] ?? '') === 'physical')>Physique</option>
        </select>

        <div class="flex flex-wrap items-center gap-2">
            <button class="btn-primary btn-sm">@icon('filter', 'w-4 h-4') Filtrer</button>
            @if (array_filter($filters))
                <a href="{{ route('organizer.reservations.index') }}" class="btn-ghost btn-sm">Réinitialiser</a>
            @endif
        </div>
    </form>

    {{-- Synthèse du périmètre sélectionné : limitée à l'événement choisi. --}}
    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('partials.stat', [
            'label' => 'Revenus encaissés',
            'value' => money($summary['total_revenue']),
            'icon' => 'currency',
            'tone' => 'brand',
            'hint' => $summary['paid_reservations'].' ticket(s) payé(s)',
            'breakdown' => money($summary['revenue_online'], false).' F en ligne · '.money($summary['revenue_physical'], false).' F en physique',
        ])
        @include('partials.stat', [
            'label' => 'Tickets vendus',
            'value' => number_format($summary['total_tickets'], 0, ',', ' '),
            'icon' => 'ticket',
            'tone' => 'violet',
            'hint' => $summary['reservations'].' réservation(s) au total',
            'breakdown' => $summary['tickets_online'].' en ligne / '.$summary['tickets_physical'].' en physique',
        ])
        @include('partials.stat', [
            'label' => 'Billets valides',
            'value' => number_format($summary['tickets_valid'], 0, ',', ' '),
            'icon' => 'check-circle',
            'tone' => 'emerald',
            'hint' => $summary['tickets_used'].' déjà utilisé(s)',
        ])
        @include('partials.stat', [
            'label' => 'Validations à l\'entrée',
            'value' => number_format($summary['validations'], 0, ',', ' '),
            'icon' => 'scan',
            'tone' => 'orange',
            'hint' => $summary['validations_invalid'].' refus(s) / '.$summary['validations_valid'].' accepté(s)',
        ])
    </div>

    @if ($reservations->isEmpty())
        @include('partials.empty', [
            'icon' => 'list',
            'title' => 'Aucune réservation',
            'message' => 'Les réservations apparaîtront ici dès la première vente confirmée.',
        ])
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Client</th>
                            <th>Événement / Type</th>
                                    <th>Canal</th>
                                    <th>Paiement</th>
                            <th class="text-right">Quantité</th>
                            <th class="text-right">Montant</th>
                            <th>Statut</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reservations as $reservation)
                            <tr x-data="{ confirmRefund: false }">
                                <td>
                                    <p class="font-mono text-xs font-semibold text-ink-900">{{ $reservation->reference }}</p>
                                    <p class="text-xs text-ink-500">{{ $reservation->created_at->translatedFormat('d/m/Y H:i') }}</p>
                                </td>
                                <td>
                                    <p class="font-medium text-ink-900">{{ $reservation->customer_name }}</p>
                                    <p class="text-xs text-ink-500">{{ $reservation->customer_email }}</p>
                                    @if ($reservation->customer_phone)
                                        <p class="text-xs text-ink-500">{{ $reservation->customer_phone }}</p>
                                    @endif
                                    @if ($reservation->customer_city || $reservation->customer_address)
                                        <p class="text-xs text-ink-500">
                                            {{ collect([$reservation->customer_city, $reservation->customer_address])->filter()->implode(' · ') }}
                                        </p>
                                    @endif
                                </td>
                                <td>
                                    <p class="max-w-[14rem] truncate text-sm text-ink-800">{{ $reservation->event->title }}</p>
                                    <p class="text-xs text-ink-500">{{ $reservation->ticketType?->name }}</p>
                                </td>
                                <td>
                                    <span class="badge {{ $reservation->isPhysical() ? 'bg-orange-100 text-orange-700' : 'bg-brand-100 text-brand-700' }}">
                                        {{ $reservation->isPhysical() ? 'Physique' : 'En ligne' }}
                                    </span>
                                    @if ($reservation->soldBy)
                                        <p class="mt-0.5 text-xs text-ink-500">{{ $reservation->soldBy->full_name }}</p>
                                    @endif
                                </td>
                                <td>
                                    <p class="text-sm text-ink-800">{{ $reservation->payment_operator_label }}</p>
                                    @if ($reservation->paid_at)
                                        <p class="text-xs text-ink-500">{{ $reservation->paid_at->translatedFormat('d/m H:i') }}</p>
                                    @endif
                                </td>
                                <td class="text-right font-semibold text-ink-900">{{ $reservation->quantity }}</td>
                                <td class="text-right font-semibold text-ink-900">{{ money($reservation->total_amount) }}</td>
                                <td>
                                    <span class="badge bg-{{ $reservation->status_color }}-100 text-{{ $reservation->status_color }}-700">
                                        {{ $reservation->status_label }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="{{ route('organizer.reservations.pdf', $reservation) }}" class="btn-ghost btn-sm" title="Récapitulatif de la réservation (PDF)">
                                            @icon('download', 'w-4 h-4')
                                        </a>

                                        @if ($reservation->isPaid())
                                            <div x-data="ticketImage" class="inline-flex">
                                                <button type="button" :disabled="busy"
                                                        @click="captureFromUrl('{{ route('organizer.reservations.cards', $reservation) }}', '{{ $reservation->reference }}')"
                                                        class="btn-ghost btn-sm disabled:opacity-60" title="Enregistrer les billets (image)">
                                                    @icon('ticket', 'w-4 h-4')
                                                </button>
                                            </div>
                                        @endif

                                        @if ($reservation->isPaid())
                                            <button type="button" @click="confirmRefund = true" class="btn-ghost btn-sm text-orange-600" title="Rembourser">
                                                @icon('refresh', 'w-4 h-4')
                                            </button>
                                        @endif

                                        @unless ($reservation->isPaid())
                                            <form method="POST" action="{{ route('organizer.reservations.destroy', $reservation) }}"
                                                  onsubmit="return confirm('Supprimer cette réservation ?');">
                                                @csrf @method('DELETE')
                                                <button class="btn-ghost btn-sm text-rose-600" title="Supprimer">
                                                    @icon('trash', 'w-4 h-4')
                                                </button>
                                            </form>
                                        @endunless
                                    </div>

                                    <div x-cloak x-show="confirmRefund" x-transition
                                         class="absolute inset-0 z-40 grid place-items-center rounded-2xl bg-ink-950/70 p-4"
                                         style="position: fixed;">
                                        <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl" @click.outside="confirmRefund = false">
                                            <h3 class="font-bold text-ink-950">Confirmer le remboursement</h3>
                                            <p class="mt-2 text-sm text-ink-600">
                                                Rembourser <strong>{{ $reservation->reference }}</strong> ({{ money($reservation->total_amount) }})
                                                via DexPay ? Les tickets seront invalidés.
                                            </p>
                                            <div class="mt-5 flex gap-2">
                                                <form method="POST" action="{{ route('organizer.reservations.refund', $reservation) }}" class="flex-1">
                                                    @csrf
                                                    <button class="btn-primary w-full justify-center btn-sm">Rembourser</button>
                                                </form>
                                                <button type="button" @click="confirmRefund = false" class="btn-ghost btn-sm">Annuler</button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-3">{{ $reservations->links() }}</div>
        </div>
    @endif
@endsection
