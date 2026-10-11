<?php

namespace App\Services;

use Illuminate\Support\Facades\Blade;

/**
 * Icônes SVG inline (aucune dépendance externe, rendu net, colorables).
 */
class Icon
{
    /** @var array<string, string> */
    private const PATHS = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9.5 21v-6h5v6"/>',
        'ticket' => '<path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v2a2 2 0 1 0 0 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2a2 2 0 1 0 0-4z"/><path d="M14 7v10" stroke-dasharray="2 2"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'map-pin' => '<path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.5a3.5 3.5 0 0 1 0 6.6"/><path d="M18 14.5a6 6 0 0 1 3.5 5.5"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'scan' => '<path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M4 12h16"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
        'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'x-circle' => '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
        'alert' => '<path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'wallet' => '<path d="M3 8a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M16 12.5h3"/><path d="M3 9h18"/>',
        'download' => '<path d="M12 3v12M8 11l4 4 4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'upload' => '<path d="M12 15V3M8 7l4-4 4 4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'print' => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M6 15h12v6H6z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'pencil' => '<path d="M4 20h4L20 8l-4-4L4 16z"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'filter' => '<path d="M3 5h18l-7 8v6l-4 2v-8z"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'logout' => '<path d="M15 17l5-5-5-5"/><path d="M20 12H9"/><path d="M12 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6"/>',
        'login' => '<path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/><path d="M13 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 9 19.4a1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1A1.6 1.6 0 0 0 4.6 9a1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3H9a1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9a1.6 1.6 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/>',
        'chart-bar' => '<path d="M5 21V10M12 21V3M19 21v-6"/><path d="M2 21h20"/>',
        'megaphone' => '<path d="m3 11 14-6v14L3 13z"/><path d="M3 11H2a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1"/><path d="M7 13v5a2 2 0 0 0 2 2h1"/>',
        'building' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 8h2M13 8h2M9 12h2M13 12h2M10 21v-4h4v4"/>',
        'shield' => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'smartphone' => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'refresh' => '<path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/>',
        'store' => '<path d="M4 9 5 4h14l1 5"/><path d="M4 9h16v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/><path d="M9 20v-6h6v6"/>',
        'coins' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
        'refresh-cw' => '<path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/>',
        'phone' => '<path d="M6 3h4l2 5-2.5 1.5a11 11 0 0 0 5 5L16 12l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 4 5a2 2 0 0 1 2-2z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'star' => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'eye' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v6h6"/><path d="M12 8v4l3 2"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m4 17 5-4 4 3 3-2 4 3"/>',
        'currency' => '<circle cx="12" cy="12" r="9"/><path d="M8 8.5h8M8 12h8M12 8.5V16"/><path d="M9.5 16h5"/>',
        'qr-code' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v3M14 20h7"/>',
        'scan-line' => '<path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2"/><path d="M3 12h18"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
        'inbox' => '<path d="M3 13h5l2 3h4l2-3h5"/><path d="M5.5 5h13l2.5 8v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5z"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
        'document' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'briefcase' => '<rect x="2.5" y="7" width="19" height="13" rx="2"/><path d="M8.5 7V5a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v2M2.5 12h19"/>',
        'receipt' => '<path d="M5 3v18l2.5-1.5L10 21l2-1.5 2 1.5 2.5-1.5L19 21V3z"/><path d="M9 8h6M9 12h6"/>',
        'credit-card' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19M6 15h4"/>',
        'user-check' => '<path d="M15 20a8 8 0 0 0-16 0"/><circle cx="7" cy="8" r="4"/><path d="m16 11 2 2 4-4"/>',
        'user-minus' => '<path d="M15 20a8 8 0 0 0-16 0"/><circle cx="7" cy="8" r="4"/><path d="M17 11h5"/>',
        'user-plus' => '<path d="M15 20a8 8 0 0 0-16 0"/><circle cx="7" cy="8" r="4"/><path d="M19 8v6M22 11h-6"/>',
        'lock' => '<rect x="4.5" y="10" width="15" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'unlock' => '<rect x="4.5" y="10" width="15" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 7.5-2"/>',
        'arrow-up-right' => '<path d="M7 17 17 7M9 7h8v8"/>',
        'arrow-down-right' => '<path d="M7 7l10 10M17 9v8H9"/>',
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.6 6.8-4M8.6 13.4l6.8 4"/>',
        'heart' => '<path d="M12 20s-7-4.5-7-9.5A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7 3.5c0 5-7 9.5-7 9.5z"/>',
        'logout-in' => '<path d="M15 17l5-5-5-5"/><path d="M20 12H9"/>',
        'send' => '<path d="M21 3 10.5 13.5"/><path d="M21 3 14.5 21l-4-8-8-4z"/>',
        'wifi-off' => '<path d="M2 2l20 20"/><path d="M8.5 16.5a5 5 0 0 1 7 0M5 13a10 10 0 0 1 3.5-2.3M19 13a10 10 0 0 0-6-2.8M2 9a15 15 0 0 1 4-2.6M16.5 6.5A15 15 0 0 1 22 9M8.5 20a1 1 0 0 1 .5-.8M12 20h.01"/>',
        'smartphone-check' => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2M9.5 11l1.5 1.5L14 9.5"/>',
    ];

    public static function render(string $name, string $class = 'w-5 h-5'): string
    {
        $path = self::PATHS[$name] ?? self::PATHS['info'];

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" '
            .'stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" '
            .'class="'.$class.'" aria-hidden="true">'.$path.'</svg>';
    }

    public static function register(): void
    {
        Blade::directive('icon', function (string $expression) {
            $parts = array_map('trim', explode(',', $expression, 2));

            // $parts[1] arrive avec ses apostrophes (@icon('x', 'w-4 h-4')) : sans ce
            // trim, render() produisait class="'w-4 h-4'" et aucune classe n'existait.
            $class = isset($parts[1]) && $parts[1] !== ''
                ? trim($parts[1], "'\"")
                : 'w-5 h-5';

            return '<?php echo \\App\\Services\\Icon::render('.$parts[0].', '.var_export($class, true).'); ?>';
        });
    }
}
