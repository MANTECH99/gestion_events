<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    | "sandbox" = aucun paiement réel (pk_test / sk_test)
    | "live"    = paiements réels Wave / Orange Money (pk_live / sk_live)
    */

    'mode' => env('DEXPAY_MODE', 'sandbox'),

    'api_key' => env('DEXPAY_API_KEY'),

    'api_secret' => env('DEXPAY_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Secret sandbox (facultatif)
    |--------------------------------------------------------------------------
    | Chaque webhook est signé avec le secret de la clé (sandbox ou production)
    | qui a créé la transaction. En passant en production, conservez ici le
    | secret sandbox : les webhooks des sessions sandbox encore ouvertes sont
    | signés avec lui et resteront vérifiables.
    */

    'api_secret_sandbox' => env('DEXPAY_API_SECRET_SANDBOX'),

    'base_url' => env('DEXPAY_BASE_URL', 'https://api-sandbox.dexpay.africa/api/v1'),

    'currency' => env('DEXPAY_CURRENCY', 'XOF'),

    'country_iso' => env('DEXPAY_COUNTRY_ISO', 'SN'),

    'client_support_fee' => filter_var(env('DEXPAY_CLIENT_SUPPORT_FEE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Frais
    |--------------------------------------------------------------------------
    | client_support_fee = true  → DexPay débite le client du montant + les
    |                              frais d'encaissement DexPay (le marchand
    |                              reçoit le montant complet demandé).
    |                              ⚠ le paramètre API s'écrit « client_support_fee »
    |                              (singulier) : toute autre orthographe est
    |                              ignorée et DexPay fait alors payer le marchand.
    | service_fee_rate            → Surcharge (en %) ajoutée au prix du ticket,
    |                              répercutée au client pour couvrir le frais de
    |                              retrait du marchand (payout). 0 = désactivée.
    |                              À régler au même taux que les frais DexPay
    |                              négociés (ex. 2 pour 2 %).
    */

    'service_fee_rate' => (float) env('DEXPAY_SERVICE_FEE_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | SDK Checkout embarqué
    |--------------------------------------------------------------------------
    */

    'sdk_url' => env('DEXPAY_SDK_URL', 'https://checkout.dexpay.africa/dexpay.js'),

    /*
    |--------------------------------------------------------------------------
    | Cache des payment providers
    |--------------------------------------------------------------------------
    */

    'providers_cache_ttl' => (int) env('DEXPAY_PROVIDERS_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Délai de validité d'une réservation en attente de paiement
    |--------------------------------------------------------------------------
    */

    'reservation_ttl_minutes' => (int) env('DEXPAY_RESERVATION_TTL', 30),

];
