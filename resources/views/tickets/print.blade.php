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
            break-after: page;
            page-break-after: always;
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

    {{-- Ajuste la hauteur de page à la carte réelle (une page par billet). --}}
    <script>
        window.addEventListener('load', function () {
            var max = 0;
            document.querySelectorAll('.ticket-page').forEach(function (page) {
                max = Math.max(max, Math.ceil(page.getBoundingClientRect().height));
            });
            if (max > 0) {
                var mm = (max / 96 * 25.4) + 1;
                var style = document.createElement('style');
                style.textContent = '@page { size: 105mm ' + mm.toFixed(2) + 'mm; margin: 0; }';
                document.head.appendChild(style);
            }
        });
    </script>
</body>
</html>
