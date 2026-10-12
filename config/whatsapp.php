<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activation
    |--------------------------------------------------------------------------
    | Désactivé par défaut : sans identifiants Meta valides, aucun appel réseau
    | n'est tenté. Passez à true une fois le compte WhatsApp Cloud configuré.
    */

    'enabled' => filter_var(env('WHATSAPP_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Identifiants Meta (WhatsApp Cloud API)
    |--------------------------------------------------------------------------
    | Le jeton doit être permanent (System User token). `phone_number_id` est
    | l'identifiant du numéro expéditeur (et non le numéro lui-même).
    */

    'token' => env('WHATSAPP_TOKEN'),

    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),

    'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),

    'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),

    /*
    |--------------------------------------------------------------------------
    | Indicatif par défaut
    |--------------------------------------------------------------------------
    | Appliqué aux numéros locaux saisis sans indicatif (ex. « 77 123 45 67 »
    | devient « 221771234567 »). Sans le « + ».
    */

    'default_country_code' => env('WHATSAPP_DEFAULT_COUNTRY_CODE', '221'),

    /*
    |--------------------------------------------------------------------------
    | Modèle de message
    |--------------------------------------------------------------------------
    | `header` = type d'en-tête média attendu par le modèle ("image" ou null).
    | `has_button` = le modèle comporte un bouton CTA dynamique ("Voir mon
    | billet"). S'il est absent du modèle, laisser false sous peine de rejet.
    */

    'template' => [
        'name' => env('WHATSAPP_TEMPLATE', 'receipt_billeterie_express'),
        'lang' => env('WHATSAPP_TEMPLATE_LANG', 'fr'),
        'header' => env('WHATSAPP_TEMPLATE_HEADER', 'image'),
        'has_button' => filter_var(env('WHATSAPP_TEMPLATE_BUTTON', true), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout des requêtes (secondes)
    |--------------------------------------------------------------------------
    */

    'timeout' => (int) env('WHATSAPP_TIMEOUT', 20),

];
