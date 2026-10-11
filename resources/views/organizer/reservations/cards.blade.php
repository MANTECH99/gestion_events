{{-- Cartes de billets servant à l'export image côté organisateur (rendues puis capturées côté client). --}}
<div class="space-y-4" style="width: 24rem;">
    @foreach ($tickets as $ticket)
        <div class="ticket-card-export" data-code="{{ $ticket->code }}">
            @include('tickets.partials.card', ['ticket' => $ticket, 'qr' => $qr($ticket)])
        </div>
    @endforeach
</div>
