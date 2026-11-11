<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page de maintenance
    |--------------------------------------------------------------------------
    |
    | MAINTENANCE_ENABLED : true → page de maintenance active, false → site
    |                       normal. Utiliser filter_var pour que la chaîne
    |                       "false" soit bien fausse (un (bool) sur une chaîne
    |                       non vide renverrait true).
    |
    | MAINTENANCE_START   : début des travaux, format AAAA-MM-JJ HH:MM
    |                       (heure de Dakar, UTC+0 — même fuseau que le Sénégal).
    |
    | MAINTENANCE_END     : fin des travaux, même format. La page se
    |                       désactive toute seule à cette heure-là.
    |
    | Sans heure renseignée, la maintenance reste active tant que
    | MAINTENANCE_ENABLED vaut true.
    |
    */

    'enabled' => filter_var(env('MAINTENANCE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'start' => env('MAINTENANCE_START'),

    'end' => env('MAINTENANCE_END'),

];
