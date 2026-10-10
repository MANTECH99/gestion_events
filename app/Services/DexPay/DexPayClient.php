<?php

namespace App\Services\DexPay;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP bas niveau pour l'API DEXCHANGE PAY.
 *
 * @see https://docs.dexpay.africa
 */
class DexPayClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly ?string $apiSecret,
    ) {}

    public static function make(): self
    {
        return new self(
            (string) config('dexpay.base_url'),
            config('dexpay.api_key'),
            config('dexpay.api_secret'),
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey) && ! str_contains((string) $this->apiKey, 'VOTRE_CLE');
    }

    public function isSandbox(): bool
    {
        return config('dexpay.mode') === 'sandbox';
    }

    /** En-têtes publics (x-api-key) — suffisant pour les endpoints de checkout. */
    public function request(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->timeout(30)
            ->connectTimeout(10)
            ->withHeaders(array_filter([
                'x-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ]))
            ->acceptJson();
    }

    /** En-têtes privilégiés (x-api-key + x-api-secret) — soldes, remboursements, payouts. */
    public function privilegedRequest(): PendingRequest
    {
        return $this->request()->withHeaders([
            'x-api-secret' => $this->apiSecret,
        ]);
    }

    public function send(PendingRequest $request, string $method, string $uri, array $payload = []): Response
    {
        try {
            return $request->{$method}(ltrim($uri, '/'), $payload);
        } catch (\Throwable $e) {
            Log::error('DexPay transport error', ['uri' => $uri, 'error' => $e->getMessage()]);

            return Http::response(['message' => 'Service de paiement injoignable : '.$e->getMessage()], 503);
        }
    }

    /** Corps JSON normalisé : accepte {data:...} ou un objet racine. */
    public function payload(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body)) {
            return [];
        }

        return $body['data'] ?? $body['payout'] ?? $body;
    }

    public function errorMessage(Response $response): string
    {
        $body = $response->json();

        if (! is_array($body)) {
            return "Erreur DexPay (HTTP {$response->status()})";
        }

        $message = $body['message'] ?? $body['error'] ?? null;

        if (is_array($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }

        return $message ?: "Erreur DexPay (HTTP {$response->status()})";
    }

    public function baseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    public function apiKey(): ?string
    {
        return $this->apiKey;
    }
}
