@extends('layouts.app', ['title' => 'Billet '.$ticket->code])

@section('content')
    <div class="mx-auto max-w-md px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-5 flex items-center justify-between gap-3 no-print">
            <a href="{{ URL::signedRoute('tickets.reservation', $ticket->reservation->reference) }}" class="btn-ghost btn-sm">
                @icon('arrow-left', 'w-4 h-4') Retour
            </a>
            <div class="flex gap-2">
                <button onclick="window.print()" class="btn-outline btn-sm">
                    @icon('print', 'w-4 h-4') Imprimer
                </button>
                <a href="{{ URL::signedRoute('tickets.download', $ticket) }}" class="btn-primary btn-sm">
                    @icon('download', 'w-4 h-4') PDF
                </a>
            </div>
        </div>

        @include('tickets.partials.card', ['ticket' => $ticket, 'qr' => $qr])

        <p class="mt-5 text-center text-xs text-ink-500 no-print">
            Présentez ce QR code à l'entrée. Le billet ne peut être utilisé qu'une seule fois.
        </p>
    </div>
@endsection