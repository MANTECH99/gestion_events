@extends('layouts.app', ['title' => 'Réservation '.$reservation->reference])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
    <div class="mx-auto max-w-5xl px-4 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' }} sm:px-6 lg:px-8 pb-28 lg:pb-8">

        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('reservations.index') }}" class="text-xs font-medium text-orange-400 hover:underline">← Mes réservations</a>
                <h1 class="mt-1.5 text-2xl font-extrabold tracking-tight text-white">{{ $reservation->event->title }}</h1>
                <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-white/60">
                    <span class="inline-flex items-center gap-1.5">@icon('calendar', 'w-4 h-4') {{ event_date_fr($reservation->event->event_date) }}</span>
                    <span class="inline-flex items-center gap-1.5">@icon('map-pin', 'w-4 h-4') {{ $reservation->event->location }}</span>
                </p>
            </div>

            <div class="text-right">
                <span class="badge bg-{{ $reservation->status_color }}-500/20 text-{{ $reservation->status_color }}-300">
                    {{ $reservation->status_label }}
                </span>
                <p class="mt-1.5 font-mono text-xs text-white/50">{{ $reservation->reference }}</p>
            </div>
        </div>

        <div class="card mb-6 border border-white/10 bg-white/5 p-5">
            <dl class="grid gap-4 text-sm grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-white/50">Titulaire</dt>
                    <dd class="mt-1 font-semibold text-white">{{ $reservation->customer_name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-white/50">Type de billet</dt>
                    <dd class="mt-1 font-semibold text-white">{{ $reservation->ticketType?->name }} × {{ $reservation->quantity }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-white/50">Prix unitaire</dt>
                    <dd class="mt-1 font-semibold text-white">{{ money($reservation->unit_price) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-white/50">Total</dt>
                    <dd class="mt-1 text-lg font-extrabold text-orange-400">{{ money($reservation->total_amount) }}</dd>
                </div>
            </dl>

            @if ($reservation->isPaid())
                <div class="mt-5 flex justify-center border-t border-white/10 pt-4">
                    <button onclick="window.print()" class="btn-hero-ghost btn-sm no-print">
                        @icon('print', 'w-4 h-4') Imprimer
                    </button>
                </div>
            @endif
        </div>

        @if ($reservation->isPaid() && $reservation->tickets->isNotEmpty())
            <h2 class="mb-4 text-lg font-bold text-white">
                Vos billets ({{ $reservation->tickets->count() }})
            </h2>

            <div class="flex flex-wrap justify-center gap-8 lg:justify-start">
                @foreach ($reservation->tickets as $ticket)
                    <div class="w-full max-w-[24rem]" x-data="ticketImage">
                        <div x-ref="card">
                            @include('tickets.partials.card', ['ticket' => $ticket, 'qr' => $qr($ticket)])
                        </div>

                        <div class="mt-3 flex justify-center gap-2 no-print">
                            <a href="{{ route('tickets.show', $ticket) }}" class="btn-hero-ghost btn-sm !px-3 !py-1.5 text-xs">
                                @icon('eye', 'w-3.5 h-3.5') Agrandir
                            </a>
                            <button type="button" @click="capture($refs.card, '{{ $ticket->code }}')" :disabled="busy"
                                    class="btn-hero-primary btn-sm !px-3 !py-1.5 text-xs disabled:opacity-60">
                                @icon('download', 'w-3.5 h-3.5')
                                <span x-show="!busy">Enregistrer l'image</span>
                                <span x-show="busy" x-cloak>Génération…</span>
                            </button>
                        </div>

                        @include('tickets.partials.image-modal')
                    </div>
                @endforeach
            </div>
        @elseif (! $reservation->isPaid())
            <div class="card border border-white/10 bg-white/5 p-10 text-center">
                <p class="font-medium text-white/80">
                    Les billets seront générés uniquement après confirmation du paiement.
                </p>
                @if ($reservation->isPending())
                    <a href="{{ route('payment.show', $reservation->reference) }}" class="btn-hero-primary mt-5">
                        @icon('credit-card', 'w-4 h-4') Finaliser le paiement
                    </a>
                @endif
            </div>
        @endif
    </div>
@endsection
