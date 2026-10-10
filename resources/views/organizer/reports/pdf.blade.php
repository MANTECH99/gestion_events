<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport — {{ $event->title }}</title>
    <style>
        @page { margin: 24px 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2430; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .muted { color: #6b7280; }
        .head { border-bottom: 2px solid #1f41f0; padding-bottom: 8px; margin-bottom: 12px; }
        .brand { color: #1f41f0; font-weight: bold; }
        .totals { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .totals td { border: 1px solid #e2e5ea; padding: 6px 8px; }
        .totals td.label { background: #f5f6f8; color: #4b5563; }
        .totals td.value { text-align: right; font-weight: bold; }
        table.rows { width: 100%; border-collapse: collapse; }
        table.rows th { background: #1f41f0; color: #fff; text-align: left; padding: 5px 6px; font-size: 9px; }
        table.rows td { border-bottom: 1px solid #e5e7eb; padding: 4px 6px; }
        table.rows tr:nth-child(even) td { background: #fafafa; }
        .right { text-align: right; }
        footer { position: fixed; bottom: -14px; left: 0; right: 0; text-align: center; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="head">
        <span class="brand">Billeterie Express</span> — Rapport des réservations
        <h1>{{ $event->title }}</h1>
        <p class="muted">
            {{ $event->event_date?->translatedFormat('l j F Y à H:i') }} · {{ $event->location }}<br>
            Organisateur : {{ $event->organizer?->full_name ?? '—' }} ·
            Généré le {{ now()->translatedFormat('j F Y à H:i') }}
        </p>
    </div>

    <table class="totals">
        <tr>
            <td class="label">Réservations</td>
            <td class="value">{{ $totals['reservations'] }}</td>
            <td class="label">Payées</td>
            <td class="value">{{ $totals['paid'] }}</td>
        </tr>
        <tr>
            <td class="label">Tickets réservés</td>
            <td class="value">{{ $totals['quantity'] }}</td>
            <td class="label">Revenus encaissés</td>
            <td class="value">{{ money($totals['revenue']) }}</td>
        </tr>
    </table>

    <table class="rows">
        <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>E-mail</th>
                <th>Type</th>
                <th>Canal</th>
                <th class="right">Qté</th>
                <th class="right">Montant</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['date']?->translatedFormat('d/m/Y H:i') }}</td>
                    <td>{{ $row['user'] }}</td>
                    <td>{{ $row['email'] }}</td>
                    <td>{{ $row['type'] }}</td>
                    <td>{{ $row['channel'] === 'physical' ? 'Physique' : 'En ligne' }}</td>
                    <td class="right">{{ $row['quantity'] }}</td>
                    <td class="right">{{ money($row['price']) }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8">Aucune réservation pour cet événement.</td></tr>
            @endforelse
        </tbody>
    </table>

    <footer>Document généré par Billeterie Express — {{ now()->translatedFormat('j F Y') }}</footer>
</body>
</html>