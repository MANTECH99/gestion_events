<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /organisateur',
            'Disallow: /admin',
            'Disallow: /agent',
            'Disallow: /point-de-vente',
            'Disallow: /mes-reservations',
            'Disallow: /paiement',
            'Disallow: /reservation',
            'Disallow: /connexion',
            'Disallow: /inscription',
            'Disallow: /tickets',
            'Disallow: /webhooks',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function sitemap(): Response
    {
        $urls = [[
            'loc' => route('home'),
            'lastmod' => now()->toDateString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ]];

        Event::query()
            ->published()
            ->orderByDesc('event_date')
            ->get(['slug', 'updated_at'])
            ->each(function (Event $event) use (&$urls) {
                $urls[] = [
                    'loc' => route('events.show', $event),
                    'lastmod' => $event->updated_at?->toDateString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
