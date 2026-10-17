<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $tickets->first()?->code ?? 'Billets' }}</title>
    <style>
        @page { size: 105mm 200mm; margin: 0; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2430; margin: 0; }

        .page { width: 105mm; padding: 6mm; }

        .card { border: 1px solid #e2e5ea; }

        .head { background-color: #f97316; color: #ffffff; padding: 6mm; }
        .kicker { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #ffffff; }
        .official { font-size: 10px; color: #ffffff; margin-top: 2px; }
        .head h1 { font-size: 18px; margin: 8px 0 0; }
        .badge { display: inline-block; padding: 2px 8px; font-size: 9px; font-weight: bold; background-color: #ffffff; color: #ea580c; }

        .info { background-color: #f5f6f8; padding: 5mm 6mm; border-bottom: 1px dashed #d7dbe3; }
        .info .row { margin-bottom: 4px; }
        .lbl { color: #6b7280; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
        .val { font-weight: bold; }

        .qrwrap { padding: 6mm; text-align: center; }
        .qrbox { display: inline-block; border: 2px solid #0f172a; padding: 3mm; }
        .code { margin-top: 3mm; font-family: DejaVu Sans Mono, monospace; font-size: 13px; font-weight: bold; letter-spacing: 1px; }

        .holder { border-top: 1px dashed #d7dbe3; padding: 5mm 6mm; }
        .holder .name { font-size: 14px; font-weight: bold; }

        .foot { background-color: #ea580c; color: #ffffff; padding: 3mm 6mm; font-size: 9px; }
    </style>
</head>
<body>
    @foreach ($tickets as $ticket)
        <div class="page"@if (! $loop->last) style="page-break-after: always;"@endif>
            <div class="card">
                <div class="head">
                    <table style="width: 100%;">
                        <tr>
                            <td>
                                <div class="kicker">Billeterie Express</div>
                                <div class="official">Billet officiel</div>
                            </td>
                            <td style="text-align: right; vertical-align: top;">
                                <span class="badge">{{ $ticket->status_label }}</span>
                            </td>
                        </tr>
                    </table>
                    <h1>{{ $ticket->event->title }}</h1>
                </div>

                <div class="info">
                    <div class="row">
                        <span class="lbl">Date</span><br>
                        <span class="val">{{ event_date_fr($ticket->event->event_date, 'l j F Y à H:i') }}</span>
                    </div>
                    <div class="row">
                        <span class="lbl">Lieu</span><br>{{ $ticket->event->location }}
                    </div>
                    <div class="row">
                        <span class="lbl">Type de billet</span><br>{{ $ticket->ticketType->name }}
                    </div>
                </div>

                <div class="qrwrap">
                    <div class="qrbox">
                        <img src="{{ $qr($ticket) }}" width="150" height="150" alt="QR code">
                    </div>
                    <div class="code">{{ $ticket->code }}</div>
                </div>

                <div class="holder">
                    <div class="lbl">Titulaire</div>
                    <div class="name">{{ $ticket->holder_name }}</div>
                    @if ($ticket->holder_email)
                        <div style="color: #6b7280;">{{ $ticket->holder_email }}</div>
                    @endif

                    <table style="width: 100%; margin-top: 4mm;">
                        <tr>
                            <td style="width: 50%;">
                                <div class="lbl">Prix</div>
                                <div class="val">{{ money($ticket->price) }}</div>
                            </td>
                            <td style="width: 50%;">
                                <div class="lbl">Réservation</div>
                                <div class="val">{{ $ticket->reservation->reference }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="foot">
                    <table style="width: 100%;">
                        <tr>
                            <td>{{ $ticket->event->organizer_display_name }}</td>
                            <td style="text-align: right;">{{ now()->year }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
