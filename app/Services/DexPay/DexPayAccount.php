<?php

namespace App\Services\DexPay;

use App\Exceptions\DexPayException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Providers, soldes et payouts DEXCHANGE PAY.
 *
 * @see https://docs.dexpay.africa/api-reference/providers
 * @see https://docs.dexpay.africa/api-reference/balances
 * @see https://docs.dexpay.africa/api-reference/payouts
 */
class DexPayAccount
{
    public function __construct(private readonly DexPayClient $client) {}

    /**
     * GET /payment-providers
     *
     * @return array<int, array<string, mixed>>
     */
    public function paymentProviders(?string $countryIso = null): array
    {
        $countryIso ??= config('dexpay.country_iso', 'SN');
        $cacheKey = "dexpay.providers.{$countryIso}";

        return Cache::remember($cacheKey, (int) config('dexpay.providers_cache_ttl', 3600), function () use ($countryIso) {
            try {
                $response = $this->client->send(
                    $this->client->privilegedRequest(),
                    'get',
                    '/payment-providers?'.http_build_query([
                        'page' => 1,
                        'limit' => 50,
                        'filters[provider_country]' => $countryIso,
                        'filters[provider_status]' => 'active',
                    ])
                );

                if (! $response->successful()) {
                    Log::warning('DexPay payment-providers error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return [];
                }

                return $response->json('data') ?? [];
            } catch (\Throwable $e) {
                Log::warning('DexPay payment-providers unavailable', ['error' => $e->getMessage()]);

                return [];
            }
        });
    }

    /**
     * GET /payout-providers
     *
     * L'API peut ignorer `filters[provider_country]` et renvoyer tous les pays :
     * on re-filtre côté serveur sur le pays configuré.
     *
     * @return array<int, array<string, mixed>>
     */
    public function payoutProviders(?string $countryIso = null): array
    {
        $countryIso ??= config('dexpay.country_iso', 'SN');
        $cacheKey = "dexpay.payout-providers.v2.{$countryIso}";

        return Cache::remember($cacheKey, (int) config('dexpay.providers_cache_ttl', 3600), function () use ($countryIso) {
            foreach (['/payout-providers', '/payouts-providers'] as $uri) {
                try {
                    $response = $this->client->send(
                        $this->client->privilegedRequest(),
                        'get',
                        $uri.'?'.http_build_query([
                            'page' => 1,
                            'limit' => 50,
                            'filters[provider_country]' => $countryIso,
                            'filters[provider_status]' => 'active',
                        ])
                    );

                    if ($response->successful()) {
                        $providers = [];

                        foreach ($response->json('data') ?? [] as $provider) {
                            $shortName = $provider['provider_short_name'] ?? null;

                            if (! $shortName || ($provider['provider_country'] ?? $countryIso) !== $countryIso) {
                                continue;
                            }

                            $providers[$shortName] = $provider;
                        }

                        return array_values($providers);
                    }
                } catch (\Throwable $e) {
                    Log::warning('DexPay payout-providers unavailable', ['uri' => $uri, 'error' => $e->getMessage()]);
                }
            }

            return [];
        });
    }

    /**
     * Opérateurs proposés à l'utilisateur sur la page de paiement (Wave, Orange Money…).
     *
     * L'API peut ignorer `filters[provider_country]` et renvoyer alors tous les pays
     * (Orange Money CI/CM/GN, MTN CI/CM, carte GLOBAL…). On re-filtre donc côté serveur
     * sur le pays configuré, et on dédoublonne par `provider_short_name`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function collectOperators(?string $countryIso = null): array
    {
        $countryIso ??= config('dexpay.country_iso', 'SN');
        $currency = config('dexpay.currency', 'XOF');

        // Indexé par code : dédoublonne sans perdre l'ordre de l'API.
        $operators = [];

        foreach ($this->paymentProviders($countryIso) as $provider) {
            $shortName = $provider['provider_short_name'] ?? null;

            if (! $shortName || ($provider['provider_country'] ?? null) !== $countryIso) {
                continue;
            }

            $operators[$shortName] = [
                'code' => $shortName,
                'name' => $provider['provider_name'] ?? strtoupper($shortName),
                'logo' => $provider['provider_logo'] ?? null,
                'country' => $provider['provider_country'] ?? $countryIso,
                // Valeur attendue par POST /checkout-sessions/{ref}/transaction-attempt.
                'payment_method' => ($provider['provider_type'] ?? null) === 'card' ? 'card' : 'mobile_money',
                'currency' => $provider['provider_currency'] ?? $currency,
                'fee' => $provider['provider_fee'] ?? null,
                'fee_type' => $provider['provider_fee_type'] ?? null,
            ];
        }

        return array_values($operators);
    }

    /** Résout un code opérateur (`wave_sn`) contre la liste des opérateurs du pays. */
    public function findOperator(?string $code, ?string $countryIso = null): ?array
    {
        if (! $code) {
            return null;
        }

        foreach ($this->collectOperators($countryIso) as $operator) {
            if ($operator['code'] === $code) {
                return $operator;
            }
        }

        return null;
    }

    /** GET /balances */
    public function balances(): array
    {
        $response = $this->client->send($this->client->privilegedRequest(), 'get', '/balances');

        if (! $response->successful()) {
            throw new DexPayException($this->client->errorMessage($response), $response->status());
        }

        return $response->json('data') ?? [];
    }

    /** Solde total toutes devises confondues. */
    public function totalBalance(): int
    {
        $total = 0;

        foreach ($this->balances() as $balance) {
            $total += (int) ($balance['balance'] ?? 0);
        }

        return $total;
    }

    /**
     * Solde détaillé par devise.
     *
     * @return array<string, int>
     */
    public function balancesByCurrency(): array
    {
        $currencies = [];

        foreach ($this->balances() as $balance) {
            $currency = $balance['currency'] ?? config('dexpay.currency', 'XOF');
            $currencies[$currency] = ($currencies[$currency] ?? 0) + (int) ($balance['balance'] ?? 0);
        }

        return $currencies;
    }

    /**
     * Solde total et détail par devise.
     *
     * @return array{total: int, currencies: array<string, int>}
     */
    public function balanceSummary(): array
    {
        $currencies = $this->balancesByCurrency();

        return [
            'total' => array_sum($currencies),
            'currencies' => $currencies,
        ];
    }

    /** Libellé lisible d'un opérateur (accepte les codes DexPay comme `wave_sn` ou `wave_sn_payout`). */
    public static function operatorLabel(?string $operator): string
    {
        $code = $operator;
        $code = $code !== null ? preg_replace('/_payout$/i', '', $code) : null;
        $code = $code !== null ? preg_replace('/_[a-z]{2}$/i', '', $code) : null;

        return match ($code) {
            'wave' => 'Wave',
            'orange', 'orange_money', 'orangemoney' => 'Orange Money',
            'free', 'free_money', 'freemoney' => 'Free Money',
            'yass' => 'Yass',
            'mixx', 'mixx_by_yas' => 'Mixx By Yas',
            default => $code !== null && $code !== '' ? ucwords(str_replace('_', ' ', $code)) : '—',
        };
    }

    /**
     * POST /payouts
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function createPayout(
        int $amount,
        string $destinationPhone,
        string $operator,
        ?string $recipientName = null,
        ?string $webhookUrl = null,
        array $metadata = [],
    ): array {
        $payload = [
            'amount' => $amount,
            'currency' => config('dexpay.currency', 'XOF'),
            'destination_phone' => $destinationPhone,
            'destination_details' => [
                'operator' => $operator,
                'countryISO' => config('dexpay.country_iso', 'SN'),
                'recipient_name' => $recipientName,
            ],
            'metadata' => $metadata,
        ];

        if ($webhookUrl) {
            $payload['webhook_url'] = $webhookUrl;
        }

        $response = $this->client->send($this->client->privilegedRequest(), 'post', '/payouts', $payload);

        if (! $response->successful()) {
            throw new DexPayException($this->client->errorMessage($response), $response->status());
        }

        return $this->client->payload($response);
    }

    /** GET /payouts/{id} */
    public function getPayout(string $id): array
    {
        $response = $this->client->send($this->client->privilegedRequest(), 'get', '/payouts/'.rawurlencode($id));

        if (! $response->successful()) {
            throw new DexPayException($this->client->errorMessage($response), $response->status());
        }

        return $this->client->payload($response);
    }

    /**
     * GET /payouts
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: array<int, array<string, mixed>>, hasNextPage: bool, totalCount: int}
     */
    public function listPayouts(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $query = [
            'page' => max(1, $page),
            'limit' => min(50, max(1, $limit)),
        ];

        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $query["filters[{$key}]"] = $value;
            }
        }

        $response = $this->client->send($this->client->privilegedRequest(), 'get', '/payouts?'.http_build_query($query));

        if (! $response->successful()) {
            throw new DexPayException($this->client->errorMessage($response), $response->status());
        }

        $body = $response->json();

        return [
            'data' => $body['data'] ?? [],
            'hasNextPage' => (bool) ($body['hasNextPage'] ?? false),
            'totalCount' => (int) ($body['totalCount'] ?? 0),
        ];
    }

    public function client(): DexPayClient
    {
        return $this->client;
    }
}
