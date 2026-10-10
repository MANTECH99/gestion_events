@extends('layouts.app', ['title' => 'Réservation '.$reservation->reference])

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('reservations.index') }}" class="text-xs font-medium text-brand-600 hover:underline">← Mes réservations</a>
                <h1 class="mt-1.5 text-2xl font-extrabold tracking-tight text-ink-950">{{ $reservation->event->title }}</h1>
                <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-ink-600">
                    <span class="inline-flex items-center gap-1.5">@icon('calendar', 'w-4 h-4') {{ event_date_fr($reservation->event->event_date) }}</span>
                    <span class="inline-flex items-center gap-1.5">@icon('map-pin', 'w-4 h-4') {{ $reservation->event->location }}</span>
                </p>
            </div>

            <div class="text-right">
                <span class="badge bg-{{ $reservation->status_color }}-100 text-{{ $reservation->status_color }}-700">
                    {{ $reservation->status_label }}
                </span>
                <p class="mt-1.5 font-mono text-xs text-ink-500">{{ $reservation->reference }}</p>
            </div>
        </div>

        <div class="card mb-6 p-5">
            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-ink-500">Titulaire</dt>
                    <dd class="mt-1 font-semibold text-ink-900">{{ $reservation->customer_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-ink-500">Type de billet</dt>
                    <dd class="mt-1 font-semibold text-ink-900">{{ $reservation->ticketType?->name }} × {{ $reservation->quantity }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-ink-500">Prix unitaire</dt>
                    <dd class="mt-1 font-semibold text-ink-900">{{ money($reservation->unit_price) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-ink-500">Total</dt>
                    <dd class="mt-1 text-lg font-extrabold text-ink-950">{{ money($reservation->total_amount) }}</dd>
                </div>
            </dl>

            @if ($reservation->isPaid())
                <div class="mt-5 flex flex-wrap gap-2 border-t border-ink-100 pt-4">
                    <a href="{{ route('reservations.download', $reservation->reference) }}" class="btn-primary btn-sm">
                        @icon('download', 'w-4 h-4') Tout télécharger en PDF
                    </a>
                    <button onclick="window.print()" class="btn-outline btn-sm no-print">
                        @icon('print', 'w-4 h-4') Imprimer
                    </button>
                </div>
            @endif
        </div>

        @if ($reservation->isPaid() && $reservation->tickets->isNotEmpty())
            <h2 class="mb-4 text-lg font-bold text-ink-950">
                Vos billets ({{ $reservation->tickets->count() }})
            </h2>

            <div class="flex flex-wrap justify-center gap-8 lg:justify-start">
                @foreach ($reservation->tickets as $ticket)
                    <div class="w-full max-w-[24rem]">
                        @include('tickets.partials.card', ['ticket' => $ticket, 'qr' => $qr($ticket)])

                        <div class="mt-4 flex gap-2 no-print">
                            <a href="{{ route('tickets.show', $ticket) }}" class="btn-outline btn-sm flex-1 justify-center">
                                @icon('eye', 'w-4 h-4') Agrandir
                            </a>
                            <a href="{{ route('tickets.download', $ticket) }}" class="btn-primary btn-sm flex-1 justify-center">
                                @icon('download', 'w-4 h-4') PDF
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif (! $reservation->isPaid())
            <div class="card p-10 text-center">
                <p class="font-medium text-ink-800">
                    Les billets seront générés uniquement après confirmation du paiement.
                </p>
                @if ($reservation->isPending())
                    <a href="{{ route('payment.show', $reservation->reference) }}" class="btn-primary mt-5">
                        @icon('credit-card', 'w-4 h-4') Finaliser le paiement
                    </a>
                @endif
            </div>
        @endif
    </div>
@endsection