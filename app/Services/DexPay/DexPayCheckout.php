<?php

namespace App\Services\DexPay;

use App\Exceptions\DexPayException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\ReservationPaymentManager;
use Illuminate\Support\Facades\Log;

/**
 * Checkout sessions DexPay : création, lecture, listage, remboursement.
 *
 * @see https://docs.dexpay.africa/api-reference/checkout
 */
class DexPayCheckout
{
    public function __construct(private readonly DexPayClient $client) {}

    /**
     * POST /checkout-sessions
     *
     * @param  array<string, mixed>  $metadata
     */
    public function create(
        string $reference,
        string $itemName,
        int $amount,
        string $successUrl,
        string $failureUrl,
        string $webhookUrl,
        array $metadata = [],
    ): array {
        $payload = [
            'reference' => $reference,
            'item_name' => $itemName,
            'amount' => $amount,
            'currency' => config('dexpay.currency', 'XOF'),
            'success_url' => $successUrl,
            'failure_url' => $failureUrl,
            'webhook_url' => $webhookUrl,
            'is_one_shot_payment' => true,
            'client_support_fee' => (bool) config('dexpay.client_support_fee', false),
            'metadata' => $metadata,
        ];

        $response = $this->client->send($this->client->request(), 'post', '/checkout-sessions', $payload);

        if (! $response->successful()) {
            Log::warning('DexPay create checkout session failed', [
                'reference' => $reference,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new DexPayException($this->client->errorMessage($response), $response->status());
        }

        return $this->client->payload($response);
    }

    /** GET /checkout-sessions/{reference} */
    public function get(string $reference): ?array
    {
        $response = $this->client->send(
            $this->client->request(), 'get', '/checkout-sessions/'.rawurlencode($reference)
        );

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new DexPayException($this->client->errorMessage($response), $response->status());
        }

        return $this->client->payload($response);
    }

    /**
     * GET /checkout-sessions
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: array<int, array<string, mixed>>, hasNextPage: bool, totalCount: int}
     */
    public function list(array $filters = [], int $page = 1, int $limit = 50): array
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

        $response = $this->client->send($this->client->request(), 'get', '/checkout-sessions?'.http_build_query($query));

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

    /**
     * POST /checkout-sessions/refund/{reference}
     *
     * L'API répond toujours en 200 : `status` porte le résultat métier.
     */
    public function refund(string $reference): array
    {
        $response = $this->client->send(
            $this->client->privilegedRequest(), 'post', '/checkout-sessions/refund/'.rawurlencode($reference)
        );

        if (! $response->successful()) {
            throw new DexPayException($this->client->errorMessage($response), $response->status());
        }

        $body = $response->json();

        return is_array($body) ? $body : [];
    }

    /**
     * POST /checkout-sessions/{reference}/transaction-attempt
     *
     * Lance le paiement chez l'opérateur choisi. `operator` attend le
     * `provider_short_name` du provider (ex: `wave_sn`), pas le nom commercial.
     * Un `cashout_url` n'est renvoyé que par les providers à redirection (Wave) ;
     * les providers USSD (Orange Money, MTN) renvoient un `cashout_url` nul.
     *
     * @see https://docs.dexpay.africa/api-reference/checkout/create-payment-attempt
     */
    public function attempt(
        string $reference,
        array $operator,
        string $customerName,
        string $customerPhone,
        string $customerEmail,
    ): array {
        $payload = [
            'payment_method' => $operator['payment_method'] ?? 'mobile_money',
            'operator' => $operator['code'],
            'customer' => [
                'name' => $customerName,
                'phone' => $customerPhone,
                'email' => $customerEmail,
            ],
            'countryISO' => config('dexpay.country_iso', 'SN'),
        ];

        $session = '/checkout-sessions/'.rawurlencode($reference);
        $response = $this->client->send($this->client->request(), 'post', $session.'/transaction-attempt', $payload);

        // L'API a aussi exposé la route sous /attempts : on tente l'alias.
        if (in_array($response->status(), [404, 405], true)) {
            $response = $this->client->send($this->client->request(), 'post', $session.'/attempts', $payload);
        }

        if (! $response->successful()) {
            Log::warning('DexPay payment attempt failed', [
                'reference' => $reference,
                'operator' => $operator['code'] ?? null,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new DexPayException($this->client->errorMessage($response), $response->status());
        }

        return $this->client->payload($response);
    }

    /** Synchronise la réservation + son paiement avec l'état côté DexPay. */
    public function syncReservation(Reservation $reservation): Reservation
    {
        $payment = $reservation->latestPayment;

        if (! $payment) {
            return $reservation;
        }

        $remote = $this->get($payment->reference);

        if (! $remote) {
            return $reservation;
        }

        return app(ReservationPaymentManager::class)->applyRemoteStatus($reservation, $remote);
    }

    /** Historique brut des sessions DexPay (utile en support / admin). */
    public function sessionsFor(array $references): array
    {
        if ($references === []) {
            return [];
        }

        $result = [];

        foreach ($references as $reference) {
            try {
                $result[$reference] = $this->get($reference);
            } catch (DexPayException $e) {
                $result[$reference] = null;
            }
        }

        return $result;
    }

    public function client(): DexPayClient
    {
        return $this->client;
    }
}
