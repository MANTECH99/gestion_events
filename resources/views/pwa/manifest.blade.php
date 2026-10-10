@php
    /** @var string|null $title */
@endphp
{
    "name": "Billeterie Express",
    "short_name": "Billeterie",
    "description": "Réservez vos billets d'événements au Sénégal. Paiement Wave et Orange Money via DexPay.",
    "start_url": "/",
    "scope": "/",
    "display": "standalone",
    "orientation": "portrait",
    "background_color": "#f6f7f9",
    "theme_color": "#1f41f0",
    "lang": "fr",
    "dir": "ltr",
    "categories": ["entertainment", "shopping", "business"],
    "icons": [
        { "src": "{{ asset('images/icon-192.png') }}", "sizes": "192x192", "type": "image/png", "purpose": "any" },
        { "src": "{{ asset('images/icon-512.png') }}", "sizes": "512x512", "type": "image/png", "purpose": "any" },
        { "src": "{{ asset('images/icon-maskable-512.png') }}", "sizes": "512x512", "type": "image/png", "purpose": "maskable" },
        { "src": "{{ asset('images/icon.svg') }}", "sizes": "any", "type": "image/svg+xml", "purpose": "any" }
    ],
    "shortcuts": [
        {
            "name": "Mes réservations",
            "short_name": "Réservations",
            "url": "{{ route('reservations.index') }}",
            "icons": [{ "src": "{{ asset('images/icon-192.png') }}", "sizes": "192x192" }]
        },
        {
            "name": "Scanner un billet",
            "short_name": "Scanner",
            "url": "{{ route('agent.scan') }}",
            "icons": [{ "src": "{{ asset('images/icon-192.png') }}", "sizes": "192x192" }]
        }
    ]
}