<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identité du site
    |--------------------------------------------------------------------------
    |
    | Ces valeurs alimentent les balises <title>, la meta description, les
    | balises Open Graph / Twitter et les données structurées (schema.org).
    |
    */

    'site_name' => env('SEO_SITE_NAME', 'Billeterie Express'),

    'tagline' => env('SEO_TAGLINE', 'Réservez vos billets en quelques secondes'),

    'default_title' => env('SEO_DEFAULT_TITLE', 'Billeterie Express — Réservez vos billets en quelques secondes'),

    'default_description' => env(
        'SEO_DEFAULT_DESCRIPTION',
        'Billeterie Express : concerts, festivals et événements au Sénégal. Payez en toute sécurité avec Wave ou Orange Money.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Image de partage par défaut
    |--------------------------------------------------------------------------
    |
    | Chemin relatif (préfixé par APP_URL) ou URL absolue utilisée comme image
    | Open Graph / Twitter quand une page n'en fournit pas.
    |
    */

    'default_image' => env('SEO_DEFAULT_IMAGE', '/images/icon-512.png'),

    /*
    |--------------------------------------------------------------------------
    | Langue, locale et indexation
    |--------------------------------------------------------------------------
    */

    'locale' => env('SEO_LOCALE', 'fr_FR'),

    'language' => env('SEO_LANGUAGE', 'fr'),

    'default_robots' => env('SEO_ROBOTS', 'index, follow'),

    'twitter_handle' => env('SEO_TWITTER_HANDLE'),

    /*
    |--------------------------------------------------------------------------
    | Organisation (données structurées)
    |--------------------------------------------------------------------------
    */

    'organization' => [
        'legal_name' => env('SEO_ORG_LEGAL_NAME', 'Billeterie Express'),
        'logo' => env('SEO_ORG_LOGO', '/images/icon-512.png'),
        'country' => env('SEO_ORG_COUNTRY', 'SN'),
        'same_as' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SEO_ORG_SAME_AS', ''))
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vérification des moteurs de recherche
    |--------------------------------------------------------------------------
    */

    'verification' => [
        'google' => env('SEO_GOOGLE_VERIFICATION'),
        'bing' => env('SEO_BING_VERIFICATION'),
    ],

];
