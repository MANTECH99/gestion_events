@extends('layouts.app', ['title' => 'Mes réservations'])

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">Mes réservations</h1>
            <p class="mt-1 text-sm text-ink-600">Retrouvez vos billets et leurs QR codes.</p>
        </div>

        @if ($reservations->isEmpty())
            @include('partials.empty', [
                'icon' => 'ticket',
                'title' => 'Aucune réservation',
                'message' => 'Vous n\'avez pas encore réservé de billets. Parcourez les événements disponibles.',
                'action' => route('home'),
                'actionLabel' => 'Voir les événements',
            ])
        @else
            <div class="space-y-4">
                @foreach ($reservations as $reservation)
                    <div class="card overflow-hidden">
                        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-600 to-violet-600 text-3xl">
                                {{ ticket_type_icon($reservation->event->event_type) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge bg-{{ $reservation->status_color }}-100 text-{{ $reservation->status_color }}-700">
                                        {{ $reservation->status_label }}
                                    </span>
                                    <span class="font-mono text-xs text-ink-500">{{ $reservation->reference }}</span>
                                </div>

                                <h2 class="mt-1.5 truncate text-lg font-bold text-ink-950">{{ $reservation->event->title }}</h2>

                                <dl class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-ink-600">
                                    <div class="inline-flex items-center gap-1.5">
                                        @icon('calendar', 'w-3.5 h-3.5') {{ event_date_fr($reservation->event->event_date) }}
                                    </div>
                                    <div class="inline-flex items-center gap-1.5">
                                        @icon('map-pin', 'w-3.5 h-3.5') {{ $reservation->event->location }}
                                    </div>
                                    <div class="inline-flex items-center gap-1.5">
                                        @icon('ticket', 'w-3.5 h-3.5') {{ $reservation->quantity }} × {{ $reservation->ticketType?->name }}
                                    </div>
                                </dl>
                            </div>

                            <div class="shrink-0 text-left sm:text-right">
                                <p class="text-xl font-extrabold text-ink-950">{{ money($reservation->total_amount) }}</p>
                                <p class="text-xs text-ink-500">{{ $reservation->created_at->translatedFormat('d/m/Y') }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 border-t border-ink-100 bg-ink-50/60 px-5 py-3">
                            @if ($reservation->isPaid())
                                <a href="{{ route('reservations.show', $reservation->reference) }}" class="btn-primary btn-sm">
                                    @icon('qr-code', 'w-4 h-4') Voir mes billets
                                </a>
                            @elseif ($reservation->isPending())
                                <a href="{{ route('payment.show', $reservation->reference) }}" class="btn-primary btn-sm">
                                    @icon('credit-card', 'w-4 h-4') Finaliser le paiement
                                </a>
                            @else
                                <span class="text-sm text-ink-500">Cette réservation n'est plus valide.</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $reservations->links() }}</div>
        @endif
    </div>
@endsection