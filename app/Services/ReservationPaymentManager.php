<?php

namespace App\Services;

use App\Exceptions\DexPayException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Services\DexPay\DexPayAccount;
use App\Services\DexPay\DexPayCheckout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Cycle de vie d'une réservation : création, confirmation, remboursement.
 *
 * Règle métier centrale : les tickets ne sont générés qu'après confirmation
 * du paiement (webhook signé `checkout.completed` ou réconciliation serveur).
 */
class ReservationPaymentManager
{
    public function __construct(
        private readonly DexPayCheckout $dexpay,
        private readonly DexPayAccount $account,
    ) {}

    /**
     * Démarre une réservation en ligne et crée la checkout session DexPay.
     *
     * @param  string|null  $operatorCode  `provider_short_name` choisi par l'utilisateur
     *                                     (ex: `wave_sn`). Il est résolu contre la liste
     *                                     des opérateurs du pays : une valeur inconnue est
     *                                     rejetée, jamais transmise telle quelle à l'API.
     */
    public function startOnlinePayment(
        Reservation $reservation,
        string $customerPhone = '',
        ?string $operatorCode = null,
    ): Reservation {
        if ($this->dexpay->client()->isConfigured() === false) {
            throw new DexPayException(
                'Le service de paiement n\'est pas configuré. Renseignez DEXPAY_API_KEY dans le fichier .env.',
                503
            );
        }

        $operator = null;

        if (filled($operatorCode)) {
            $operator = $this->account->findOperator($operatorCode);

            if (! $operator) {
                throw new DexPayException('Ce moyen de paiement n\'est pas disponible.', 422);
            }
        }

        $payment = $reservation->latestPayment()->first();

        // Une session déjà créée et encore ouverte est réutilisée.
        if ($payment && $payment->status !== 'completed' && $payment->payment_url) {
            // Changer d'opérateur sur une session ouverte relance une tentative.
            if ($operator && $payment->operator !== $operator['code']) {
                $this->attachAttempt($reservation, $payment, $operator, $customerPhone);
            }

            $reservation->refresh();

            return $reservation;
        }

        // Les métadonnées DexPay n'acceptent pas les valeurs nulles.
        $metadata = array_filter([
            'reservation_id' => (string) $reservation->id,
            'reference' => $reservation->reference,
            'event_id' => (string) $reservation->event_id,
            'customer_email' => $reservation->customer_email,
            'customer_name' => $reservation->customer_name,
            'customer_phone' => $customerPhone,
            'operator' => $operator['code'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        // Montant réglé par le client : prix des billets + surcharge de service
        // (les frais d'encaissement DexPay sont ajoutés par DexPay lui-même quand
        // client_support_fee=true ; la surcharge couvre le frais de retrait).
        $fee = dexpay_service_fee($reservation->total_amount, $operator);
        $amountToPay = $reservation->total_amount + $fee;

        $session = $this->dexpay->create(
            reference: $reservation->reference,
            itemName: $this->itemNameFor($reservation),
            amount: $amountToPay,
            // URLs signées : le retour de DexPay/Wave peut arriver dans un
            // navigateur sans le cookie de session (appli mobile / navigateur
            // intégré). La signature autorise l'accès sans session.
            successUrl: URL::signedRoute('payment.success', ['reference' => $reservation->reference]),
            failureUrl: URL::signedRoute('payment.failed', ['reference' => $reservation->reference]),
            webhookUrl: route('webhooks.dexpay'),
            metadata: $metadata,
        );

        $payment = Payment::updateOrCreate(
            ['reference' => $reservation->reference],
            [
                'reservation_id' => $reservation->id,
                'provider' => 'dexpay',
                'session_id' => $session['id'] ?? null,
                'payment_url' => $session['payment_url'] ?? null,
                'status' => $session['status'] ?? 'initiated',
                'currency' => $session['currency'] ?? config('dexpay.currency', 'XOF'),
                'amount' => (int) ($session['amount'] ?? $amountToPay),
                'operator' => $operator['code'] ?? null,
                'payload' => array_merge($session, ['service_fee' => $fee]),
            ]
        );

        $reservation->update([
            'payment_operator' => $operator['code'] ?? null,
        ]);

        // Le choix de l'opérateur est un confort : en cas d'échec on garde la page
        // hébergée DexPay comme repli plutôt que de bloquer le paiement.
        if ($operator) {
            $this->attachAttempt($reservation, $payment, $operator, $customerPhone);
        }

        $reservation->refresh();

        return $reservation;
    }

    /**
     * Lance la tentative de paiement chez l'opérateur choisi et conserve son URL.
     *
     * @param  array<string, mixed>  $operator
     */
    private function attachAttempt(
        Reservation $reservation,
        Payment $payment,
        array $operator,
        string $customerPhone,
    ): void {
        if (blank($customerPhone)) {
            return;
        }

        try {
            $attempt = $this->dexpay->attempt(
                reference: $reservation->reference,
                operator: $operator,
                customerName: $reservation->customer_name,
                customerPhone: $customerPhone,
                customerEmail: $reservation->customer_email,
            );
        } catch (DexPayException $e) {
            Log::warning('Reservation payment attempt fallback', [
                'reference' => $reservation->reference,
                'operator' => $operator['code'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $cashoutUrl = $attempt['cashout_url'] ?? null;
        $sandboxUrl = $payment->payload['sandbox_payment_url'] ?? null;

        $payment->fill([
            'operator' => $attempt['operator'] ?? $operator['code'],
            'transaction_id' => $attempt['transaction_id'] ?? $payment->transaction_id,
            'payment_url' => $cashoutUrl ?: $payment->payment_url,
            'payload' => array_merge($payment->payload ?? [], [
                'payment_attempt' => $attempt,
                'sandbox_payment_url' => $sandboxUrl,
            ]),
        ])->save();

        $reservation->update([
            'payment_operator' => $attempt['operator'] ?? $operator['code'],
        ]);
    }

    /**
     * Applique le statut renvoyé par DexPay (webhook ou GET /checkout-sessions).
     *
     * @param  array<string, mixed>  $payload
     */
    public function applyRemoteStatus(Reservation $reservation, array $payload): Reservation
    {
        $status = strtolower((string) ($payload['status'] ?? ''));
        $reference = (string) ($payload['reference'] ?? $reservation->reference);

        $payment = Payment::firstOrNew(['reference' => $reference]);

        if (! $payment->exists) {
            $payment->fill([
                'reservation_id' => $reservation->id,
                'provider' => 'dexpay',
            ]);
        }

        // Les frais ne sont fournis que par le webhook checkout.completed
        // (fee_amount, merchant_net) : la lecture GET /checkout-sessions n'en
        // renvoie pas. On préserve donc les frais déjà capturés pour ne pas les
        // écraser lors d'une relecture manuelle.
        $prior = is_array($payment->payload) ? $payment->payload : [];
        $payload = array_merge(
            array_intersect_key($prior, array_flip(['fee', 'fee_amount', 'merchant_net', 'service_fee'])),
            $payload
        );

        $payment->fill([
            'session_id' => $payload['id'] ?? $payload['checkout_session_id'] ?? $payment->session_id,
            'payment_url' => $payload['payment_url'] ?? $payment->payment_url,
            'status' => $status ?: $payment->status,
            'currency' => $payload['currency'] ?? ($payment->currency ?: config('dexpay.currency')),
            'amount' => (int) ($payload['amount'] ?? ($payment->amount ?: $reservation->total_amount)),
            'operator' => $payload['operator'] ?? $payment->operator,
            'transaction_id' => $payload['transaction_id'] ?? $payload['payment_attempt_id'] ?? $payment->transaction_id,
            'completed_at' => $status === 'completed'
                ? ($payload['completed_at'] ?? now()->toIso8601String())
                : $payment->completed_at,
            'payload' => $payload,
        ])->save();

        return match ($status) {
            'completed', 'success' => $this->markPaid($reservation, $payment, $payload),
            'failed', 'cancelled' => $this->markUnpaid($reservation, $status, $payload),
            default => $reservation->refresh(),
        };
    }

    /**
     * Marque la réservation payée et génère les tickets (idempotent).
     *
     * @param  array<string, mixed>  $payload
     */
    public function markPaid(Reservation $reservation, ?Payment $payment = null, array $payload = []): Reservation
    {
        return DB::transaction(function () use ($reservation, $payment, $payload) {
            $reservation = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if ($reservation->isPaid()) {
                return $reservation;
            }

            $operator = $payload['operator'] ?? $payment?->operator;

            $reservation->update([
                'payment_status' => Reservation::STATUS_PAID,
                'paid_at' => $reservation->paid_at ?? now(),
                'payment_operator' => is_string($operator) ? $operator : $reservation->payment_operator,
            ]);

            $this->generateTickets($reservation);
            $this->incrementOnlineSales($reservation);

            if ($payment) {
                $payment->fill([
                    'status' => 'completed',
                    'completed_at' => $payment->completed_at ?? now(),
                ])->save();
            }

            Log::info('Reservation paid', ['reference' => $reservation->reference]);

            return $reservation->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function markUnpaid(Reservation $reservation, string $status, array $payload = []): Reservation
    {
        if ($reservation->isPaid()) {
            return $reservation;
        }

        $payment = Payment::where('reference', $payload['reference'] ?? $reservation->reference)->first();

        $reservation->update([
            'payment_status' => $status === 'cancelled'
                ? Reservation::STATUS_CANCELLED
                : Reservation::STATUS_FAILED,
        ]);

        if ($payment) {
            $payment->fill([
                'status' => $status,
                'payload' => $payload,
            ])->save();
        }

        return $reservation->refresh();
    }

    /** Remboursement complet via DexPay. */
    public function refund(Reservation $reservation): Reservation
    {
        if (! $reservation->isPaid()) {
            throw new DexPayException('Seule une réservation payée peut être remboursée.', 422);
        }

        $result = $this->dexpay->refund($reservation->reference);

        if (($result['status'] ?? null) !== 'success') {
            throw new DexPayException(
                (string) ($result['message'] ?? 'Le remboursement a été refusé par DexPay.'),
                422
            );
        }

        return DB::transaction(function () use ($reservation, $result) {
            $reservation->update([
                'payment_status' => Reservation::STATUS_REFUNDED,
            ]);

            $reservation->tickets()
                ->where('status', Ticket::STATUS_VALID)
                ->update(['status' => Ticket::STATUS_REFUNDED]);

            $payment = Payment::where('reference', $reservation->reference)->first();

            $payment?->fill([
                'status' => 'refunded',
                'payload' => array_merge($payment->payload ?? [], $result),
            ])->save();

            return $reservation->refresh();
        });
    }

    /** Un ticket par unité réservée, avec QR code unique. */
    public function generateTickets(Reservation $reservation): void
    {
        if ($reservation->tickets()->exists()) {
            return;
        }

        $created = now();

        $rows = [];

        for ($i = 1; $i <= $reservation->quantity; $i++) {
            $rows[] = [
                'code' => Ticket::makeCode(),
                'reservation_id' => $reservation->id,
                'event_id' => $reservation->event_id,
                'ticket_type_id' => $reservation->ticket_type_id,
                'holder_name' => $reservation->customer_name,
                'holder_email' => $reservation->customer_email,
                'price' => $reservation->unit_price,
                'status' => Ticket::STATUS_VALID,
                'created_at' => $created,
                'updated_at' => $created,
            ];
        }

        Ticket::insert($rows);
    }

    private function incrementOnlineSales(Reservation $reservation): void
    {
        $reservation->ticketType?->increment('sold_online', $reservation->quantity);
    }

    public function itemNameFor(Reservation $reservation): string
    {
        $event = $reservation->event;
        $type = $reservation->ticketType;

        return sprintf(
            '%s — %s x%d',
            $event?->title ?? 'Événement',
            $type?->name ?? 'Ticket',
            $reservation->quantity
        );
    }
}
