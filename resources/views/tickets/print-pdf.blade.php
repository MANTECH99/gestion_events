<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $tickets->first()?->code ?? 'Billets' }}</title>
    <style>
        @page { size: 105mm 200mm; margin: 0; }

        body { font-family: "Instrument Sans", "DejaVu Sans", sans-serif; color: #343946; margin: 0; }

        .page { width: 105mm; padding: 6mm; }

        .card { border: 1px solid #d5dae3; border-radius: 14px; overflow: hidden; }

        .head {
            padding: 6mm;
            color: #ffffff;
            background-image: url("{{ $headerImage }}");
            background-repeat: no-repeat;
            background-position: left top;
            background-size: 100% 100%;
        }
        .kicker { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #bdc8ff; }
        .official { font-size: 10px; color: #e2e7ff; margin-top: 2px; }
        .head h1 { font-size: 18px; font-weight: bold; margin: 8px 0 0; color: #ffffff; }
        .badge { display: inline-block; padding: 3px 9px; font-size: 9px; font-weight: bold; border-radius: 10px; }

        .info { background-color: #f6f7f9; padding: 5mm 6mm; border-bottom: 1px dashed #d5dae3; }
        .info table { width: 100%; border-collapse: collapse; }
        .info td { padding: 2px 0; vertical-align: middle; }
        .info .ic { width: 20px; }
        .info .val { font-size: 11px; color: #343946; }
        .info .val.strong { font-weight: bold; color: #12141c; }

        .qrwrap { padding: 6mm; text-align: center; }
        .qrbox { display: inline-block; border: 2px solid #12141c; border-radius: 12px; padding: 3mm; }
        .qrbox.muted { border-color: #d5dae3; }
        .code { margin-top: 3mm; font-family: "DejaVu Sans Mono", monospace; font-size: 13px; font-weight: bold; letter-spacing: 1px; color: #12141c; }

        .note { margin-top: 3mm; padding: 2mm 3mm; font-size: 9px; font-weight: bold; border-radius: 8px; }
        .note.used { background-color: #eceef2; color: #515d77; }
        .note.invalid { background-color: #fff1f2; color: #be123c; }

        .holder { border-top: 1px dashed #d5dae3; padding: 5mm 6mm; }
        .label { color: #667490; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
        .holder .name { font-size: 14px; font-weight: bold; color: #12141c; }
        .holder .mail { color: #667490; font-size: 10px; }
        .holder table { width: 100%; border-collapse: collapse; margin-top: 4mm; }
        .holder .price { font-weight: bold; color: #343946; }
        .holder .ref { font-family: "DejaVu Sans Mono", monospace; font-size: 10px; font-weight: bold; color: #343946; }

        .foot { background-color: #12141c; color: #9aa0ab; padding: 3mm 6mm; font-size: 9px; }
        .foot table { width: 100%; border-collapse: collapse; }
    </style>
</head>
<body>
    @foreach ($tickets as $ticket)
        @php
            $isUsed = $ticket->status === 'used';
            $isInvalid = in_array($ticket->status, ['refunded', 'cancelled'], true);
            $badge = $isUsed ? 'background:#12141c;color:#ffffff;'
                : ($isInvalid ? 'background:#e11d48;color:#ffffff;' : 'background:#ffffff;color:#047857;');
        @endphp
        <div class="page"@if (! $loop->last) style="page-break-after: always;"@endif>
            <div class="card">
                <div class="head">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td valign="top">
                                <div class="kicker">Billeterie Express</div>
                                <div class="official">Billet officiel</div>
                            </td>
                            <td valign="top" align="right">
                                <span class="badge" style="{{ $badge }}">{{ $ticket->status_label }}</span>
                            </td>
                        </tr>
                    </table>
                    <h1>{{ $ticket->event->title }}</h1>
                </div>

                <div class="info">
                    <table>
                        <tr>
                            <td class="ic"><img src="{{ $icon('calendar') }}" width="14" height="14" alt=""></td>
                            <td class="val strong">{{ event_date_fr($ticket->event->event_date, 'l j F Y à H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="ic"><img src="{{ $icon('map-pin') }}" width="14" height="14" alt=""></td>
                            <td class="val">{{ $ticket->event->location }}</td>
                        </tr>
                        <tr>
                            <td class="ic"><img src="{{ $icon('ticket') }}" width="14" height="14" alt=""></td>
                            <td class="val">{{ $ticket->ticketType->name }}</td>
                        </tr>
                    </table>
                </div>

                <div class="qrwrap">
                    <div class="qrbox{{ $isUsed || $isInvalid ? ' muted' : '' }}">
                        <img src="{{ $qr($ticket) }}" width="150" height="150" alt="QR code">
                    </div>
                    <div class="code">{{ $ticket->code }}</div>

                    @if ($isUsed)
                        <div class="note used">
                            Utilisé le {{ $ticket->used_at?->translatedFormat('d/m/Y à H:i') }}@if ($ticket->validator) par {{ $ticket->validator->full_name }}@endif
                        </div>
                    @elseif ($isInvalid)
                        <div class="note invalid">Ce billet n'est plus valide.</div>
                    @endif
                </div>

                <div class="holder">
                    <div class="label">Titulaire</div>
                    <div class="name">{{ $ticket->holder_name }}</div>
                    @if ($ticket->holder_email)
                        <div class="mail">{{ $ticket->holder_email }}</div>
                    @endif

                    <table>
                        <tr>
                            <td width="50%" valign="top">
                                <div class="label">Prix</div>
                                <div class="price">{{ money($ticket->price) }}</div>
                            </td>
                            <td width="50%" valign="top">
                                <div class="label">Réservation</div>
                                <div class="ref">{{ $ticket->reservation->reference }}</div>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="foot">
                    <table>
                        <tr>
                            <td>{{ $ticket->event->organizer_display_name }}</td>
                            <td align="right">{{ now()->year }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
