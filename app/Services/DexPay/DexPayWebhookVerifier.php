<?php

namespace App\Services\DexPay;

use Illuminate\Http\Request;

/**
 * Vérification de la signature HMAC-SHA256 des webhooks DEXCHANGE PAY.
 *
 * La signature est le HMAC-SHA256 (hex) du body brut, calculé avec l'API Secret
 * de la clé ayant créé la session (`sk_test_…` / `sk_live_…`).
 * Certains payloads de la doc sont signés après ré-encodage JSON : les deux
 * variantes sont acceptées, la comparaison est à temps constant.
 *
 * @see https://docs.dexpay.africa/guides/webhooks
 */
class DexPayWebhookVerifier
{
    public function signatureFromRequest(Request $request): ?string
    {
        return $request->header('X-Webhook-Signature')
            ?? $request->header('x-dexchange-signature')
            ?? $request->header('X-Dexchange-Signature');
    }

    public function eventFromRequest(Request $request): ?string
    {
        return $request->header('X-Webhook-Event')
            ?? $request->header('X-Event');
    }

    public function verify(Request $request): bool
    {
        $signature = $this->signatureFromRequest($request);

        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $rawBody = $request->getContent();

        if ($rawBody === '') {
            return false;
        }

        // Chaque webhook est signé avec l'API Secret de la clé ayant créé la
        // transaction. On accepte le secret courant et, s'il est renseigné,
        // celui de l'autre environnement (sandbox ↔ production).
        $secrets = array_filter([
            (string) config('dexpay.api_secret'),
            (string) config('dexpay.api_secret_sandbox'),
        ], fn (string $secret): bool => $secret !== '' && ! str_contains($secret, 'VOTRE_CLE'));

        if ($secrets === []) {
            return false;
        }

        $candidates = [$rawBody];

        // Variante « signature calculée sur le JSON ré-encodé » (doc events).
        $reencoded = json_encode(json_decode($rawBody, true) ?: []);

        if (is_string($reencoded) && $reencoded !== $rawBody) {
            $candidates[] = $reencoded;
        }

        // Variante avec séparateurs UTF-8 non échappés.
        $unescaped = json_encode(json_decode($rawBody, true) ?: [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (is_string($unescaped) && ! in_array($unescaped, $candidates, true)) {
            $candidates[] = $unescaped;
        }

        foreach ($secrets as $secret) {
            foreach ($candidates as $candidate) {
                $expected = hash_hmac('sha256', $candidate, $secret);

                if (hash_equals($expected, $signature)) {
                    return true;
                }
            }
        }

        return false;
    }
}
