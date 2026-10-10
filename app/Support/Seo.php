<?php

namespace App\Support;

use App\Models\Event;
use Illuminate\Support\Str;

class Seo
{
    /**
     * Image absolue à partir d'un chemin relatif (ou d'une URL déjà absolue).
     */
    public static function absoluteImage(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : url($path);
    }

    /**
     * Données structurées de l'organisation (schema.org Organization).
     *
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('seo.site_name'),
            'url' => url('/'),
            'logo' => self::absoluteImage(config('seo.organization.logo')),
        ];

        $sameAs = config('seo.organization.same_as', []);

        if (! empty($sameAs)) {
            $data['sameAs'] = array_values($sameAs);
        }

        return $data;
    }

    /**
     * Données structurées du site avec recherche interne (schema.org WebSite).
     *
     * @return array<string, mixed>
     */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('seo.site_name'),
            'url' => url('/'),
            'inLanguage' => config('seo.language'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('home').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Données structurées d'un événement (schema.org Event).
     *
     * @return array<string, mixed>
     */
    public static function event(Event $event): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $event->title,
            'description' => self::plainDescription($event),
            'url' => route('events.show', $event),
            'startDate' => $event->event_date?->toIso8601String(),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => [
                '@type' => 'Place',
                'name' => $event->location,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $event->location,
                    'addressCountry' => config('seo.organization.country'),
                ],
            ],
            'organizer' => [
                '@type' => 'Organization',
                'name' => $event->organizer_display_name,
                'url' => url('/'),
            ],
            'image' => self::absoluteImage($event->image_url)
                ?? self::absoluteImage(config('seo.default_image')),
        ];

        if ($event->min_price !== null) {
            $data['offers'] = [
                '@type' => 'Offer',
                'price' => $event->min_price,
                'priceCurrency' => 'XOF',
                'availability' => $event->isSoldOut()
                    ? 'https://schema.org/SoldOut'
                    : 'https://schema.org/InStock',
                'url' => route('events.show', $event),
            ];
        }

        return $data;
    }

    private static function plainDescription(Event $event): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $event->description)) ?? '');

        return $text !== ''
            ? Str::limit($text, 300)
            : (string) config('seo.default_description');
    }
}
