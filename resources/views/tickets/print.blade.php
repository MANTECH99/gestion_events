<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $tickets->first()?->code ?? 'Billets' }}</title>
    <style>
        @page { size: 105mm 200mm; margin: 0; }

        html, body { margin: 0; padding: 0; background: #ffffff; }
        body { font-family: "Instrument Sans", ui-sans-serif, system-ui, sans-serif; }

        * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

        .ticket-page {
            width: 105mm;
            padding: 1.6mm;
            box-sizing: border-box;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .ticket-page:not(:last-child) {
            break-after: page !important;
            page-break-after: always !important;
        }
    </style>
    <style>{!! $css !!}</style>
</head>
<body>
    @foreach ($tickets as $ticket)
        <div class="ticket-page">
            @include('tickets.partials.card', ['ticket' => $ticket, 'qr' => $qr($ticket)])
        </div>
    @endforeach
</body>
</html>
