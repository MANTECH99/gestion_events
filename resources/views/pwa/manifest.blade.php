@php
    /** @var string|null $title */
    $v = config('app.asset_version', '1');
@endphp
{
    "name": "Billeterie Express",
    "short_name": "Billeterie",
    "description": "Réservez vos billets d'événements au Sénégal. Paiement Wave et Orange Money via DexPay.",
    "start_url": "/",
    "scope": "/",
    "display": "standalone",
    "orientation": "portrait",
    "background_color": "#0a0d14",
    "theme_color": "#f97316",
    "lang": "fr",
    "dir": "ltr",
    "categories": ["entertainment", "shopping", "business"],
    "icons": [
        { "src": "{{ asset('images/icon-192.png') }}?v={{ $v }}", "sizes": "192x192", "type": "image/png", "purpose": "any" },
        { "src": "{{ asset('images/icon-512.png') }}?v={{ $v }}", "sizes": "512x512", "type": "image/png", "purpose": "any" },
        { "src": "{{ asset('images/icon-maskable-512.png') }}?v={{ $v }}", "sizes": "512x512", "type": "image/png", "purpose": "maskable" },
        { "src": "{{ asset('images/icon.svg') }}?v={{ $v }}", "sizes": "any", "type": "image/svg+xml", "purpose": "any" }
    ],
    "shortcuts": [
        {
            "name": "Mes réservations",
            "short_name": "Réservations",
            "url": "{{ route('reservations.index') }}",
            "icons": [{ "src": "{{ asset('images/icon-192.png') }}?v={{ $v }}", "sizes": "192x192" }]
        },
        {
            "name": "Scanner un billet",
            "short_name": "Scanner",
            "url": "{{ route('agent.scan') }}",
            "icons": [{ "src": "{{ asset('images/icon-192.png') }}?v={{ $v }}", "sizes": "192x192" }]
        }
    ]
}