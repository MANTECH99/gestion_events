<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Réservation {{ $reservation->reference }}</title>
    <style>
        @page { margin: 20px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2430; }
        .head { border-bottom: 2px solid #1f41f0; padding-bottom: 8px; margin-bottom: 14px; }
        .brand { color: #1f41f0; font-weight: bold; }
        h1 { font-size: 17px; margin: 4px 0 2px; }
        .muted { color: #6b7280; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .grid td { border: 1px solid #e2e5ea; padding: 6px 8px; vertical-align: top; }
        .grid td.label { background: #f5f6f8; color: #4b5563; width: 34%; }
        table.rows { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.rows th { background: #1f41f0; color: #fff; text-align: left; padding: 5px 6px; font-size: 10px; }
        table.rows td { border-bottom: 1px solid #e5e7eb; padding: 4px 6px; }
        .page-break { page-break-after: always; }
        footer { position: fixed; bottom: -12px; left: 0; right: 0; text-align: center; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="head">
        <span class="brand">Billeterie Express</span> — Récapitulatif de réservation
        <h1>{{ $reservation->event->title }}</h1>
        <p class="muted">
            {{ $reservation->event->event_date?->translatedFormat('l j F Y à H:i') }} · {{ $reservation->event->location }}
        </p>
    </div>

    <table class="grid">
        <tr>
            <td class="label">Référence</td>
            <td><strong>{{ $reservation->reference }}</strong></td>
            <td class="label">Statut</td>
            <td>{{ $reservation->status_label }}</td>
        </tr>
        <tr>
            <td class="label">Client</td>
            <td>{{ $reservation->customer_name }}</td>
            <td class="label">E-mail</td>
            <td>{{ $reservation->customer_email }}</td>
        </tr>
        <tr>
            <td class="label">Téléphone</td>
            <td>{{ $reservation->customer_phone ?? '—' }}</td>
            <td class="label">Canal</td>
            <td>{{ $reservation->channel_label }}</td>
        </tr>
        <tr>
            <td class="label">Type de ticket</td>
            <td>{{ $reservation->ticketType?->name }}</td>
            <td class="label">Quantité</td>
            <td>{{ $reservation->quantity }}</td>
        </tr>
        <tr>
            <td class="label">Prix unitaire</td>
            <td>{{ money($reservation->unit_price) }}</td>
            <td class="label">Total</td>
            <td><strong>{{ money($reservation->total_amount) }}</strong></td>
        </tr>
        @if ($reservation->paid_at)
            <tr>
                <td class="label">Payé le</td>
                <td colspan="3">{{ $reservation->paid_at->translatedFormat('j F Y à H:i') }}</td>
            </tr>
        @endif
    </table>

    <p class="muted">
        {{ count($tickets) }} billet(s) à présenter à l'entrée. Chaque billet est à usage unique.
    </p>

    @foreach ($tickets as $ticket)
        <div class="page-break">
            <table class="rows">
                <tr>
                    <td style="width:32mm;border:1px solid #e2e5ea;padding:4mm;text-align:center;">
                        <img src="{{ $qr($ticket) }}" width="105" alt="QR">
                        <div style="font-size:10px;font-weight:bold;letter-spacing:.5px;">{{ $ticket->code }}</div>
                    </td>
                    <td style="border:1px solid #e2e5ea;padding:4mm;">
                        <strong style="font-size:13px;">{{ $ticket->event->title }}</strong>
                        <div style="margin-top:2mm;font-size:11px;">
                            {{ $ticket->event->event_date?->translatedFormat('l j F Y à H:i') }}<br>
                            {{ $ticket->event->location }}<br>
                            {{ $ticket->ticketType?->name }}
                        </div>
                        <div style="margin-top:2mm;font-size:10px;color:#6b7280;">
                            Titulaire : <strong style="color:#1f2430;">{{ $ticket->holder_name }}</strong><br>
                            Prix : {{ money($ticket->price) }} · Statut : {{ $ticket->status_label }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach

    <footer>Billeterie Express — {{ $reservation->reference }} — {{ now()->translatedFormat('j F Y') }}</footer>
</body>
</html>