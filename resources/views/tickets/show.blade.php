@extends('layouts.app', ['title' => 'Billet '.$ticket->code])

@section('content')
    <div class="mx-auto max-w-md px-4 py-8 sm:px-6 lg:px-8" x-data="ticketImage">
        <div class="mb-5 flex items-center justify-between gap-3 no-print">
            <a href="{{ URL::signedRoute('tickets.reservation', $ticket->reservation->reference) }}" class="btn-ghost btn-sm">
                @icon('arrow-left', 'w-4 h-4') Retour
            </a>
            <div class="flex gap-2">
                <button onclick="window.print()" class="btn-outline btn-sm">
                    @icon('print', 'w-4 h-4') Imprimer
                </button>
                <button type="button" @click="capture($refs.card, '{{ $ticket->code }}')" :disabled="busy"
                        class="btn-primary btn-sm disabled:opacity-60">
                    @icon('download', 'w-4 h-4')
                    <span x-show="!busy">Enregistrer l'image</span>
                    <span x-show="busy" x-cloak>Génération…</span>
                </button>
            </div>
        </div>

        <div x-ref="card" class="js-ticket-card">
            @include('tickets.partials.card', ['ticket' => $ticket, 'qr' => $qr])
        </div>

        <p class="mt-5 text-center text-xs text-ink-500 no-print">
            Présentez ce QR code à l'entrée. Le billet ne peut être utilisé qu'une seule fois.
        </p>

        @include('tickets.partials.image-modal')

        @if (config('whatsapp.enabled') && $ticket->reservation->isPaid() && ! $ticket->reservation->hasBeenSentToWhatsApp() && filled(whatsapp_phone($ticket->reservation->customer_phone)))
            <div x-data="ticketWhatsApp(@js(['url' => URL::signedRoute('reservations.whatsapp', ['reference' => $ticket->reservation->reference]), 'sent' => false]))"></div>
        @endif
    </div>
@endsection