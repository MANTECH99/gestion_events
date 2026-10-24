@extends('layouts.app', ['title' => 'Vos billets'])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
    <div class="mx-auto max-w-5xl px-4 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' }} sm:px-6 lg:px-8 pb-28 lg:pb-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Vos billets</h1>
                <p class="mt-1 text-sm text-white/60">
                    Réservation {{ $reservation->reference }} · {{ event_date_fr($reservation->event->event_date, 'd/m/Y à H:i') }}
                </p>
            </div>
        </div>

        @if ($reservation->tickets->isEmpty())
            <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-10 text-center">
                <p class="text-white/60">Aucun billet n'a encore été généré pour cette réservation.</p>
            </div>
        @else
            <div class="mt-8 flex flex-wrap justify-center gap-8">
                @foreach ($tickets as $ticket)
                    <div class="w-full max-w-[24rem]" x-data="ticketImage">
                        <div x-ref="card" class="js-ticket-card">
                            @include('tickets.partials.card', ['ticket' => $ticket, 'qr' => $qr($ticket)])
                        </div>

                        <div class="mt-4 flex gap-2 no-print">
                            <a href="{{ URL::signedRoute('tickets.show', $ticket) }}" class="btn-invert btn-sm !px-3 !py-1.5 text-xs flex-1">
                                @icon('eye', 'w-4 h-4') Agrandir
                            </a>
                            <button type="button" @click="capture($refs.card, '{{ $ticket->code }}')" :disabled="busy"
                                    class="btn-hero-primary btn-sm !px-3 !py-1.5 text-xs flex-1 disabled:opacity-60">
                                @icon('download', 'w-4 h-4')
                                <span x-show="!busy">Enregistrer l'image</span>
                                <span x-show="busy" x-cloak>Génération…</span>
                            </button>
                        </div>

                        @include('tickets.partials.image-modal')
                    </div>
                @endforeach
            </div>

            @if (config('whatsapp.enabled') && $reservation->isPaid() && ! $reservation->hasBeenSentToWhatsApp() && filled(whatsapp_phone($reservation->customer_phone)))
                <div x-data="ticketWhatsApp(@js(['url' => URL::signedRoute('reservations.whatsapp', ['reference' => $reservation->reference]), 'sent' => false]))"></div>
            @endif
        @endif
    </div>
@endsection
