<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\Reservation;
use App\Models\WebhookLog;
use App\Services\DexPay\DexPayWebhookVerifier;
use App\Services\ReservationPaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Point d'entrée des webhooks DEXCHANGE PAY.
 *
 * La source de vérité de la confirmation de paiement est cet endpoint :
 * la signature HMAC-SHA256 du body brut est vérifiée avant tout traitement,
 * puis le traitement est idempotent.
 *
 * @see https://docs.dexpay.africa/guides/webhooks
 */
class DexPayWebhookController extends Controller
{
    public function __construct(
        private readonly DexPayWebhookVerifier $verifier,
        private readonly ReservationPaymentManager $payments,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $raw = $request->getContent();
        $event = $this->verifier->eventFromRequest($request) ?: (json_decode($raw, true)['event'] ?? null);
        $signatureValid = $this->verifier->verify($request);

        $log = WebhookLog::create([
            'source' => 'dexpay',
            'event' => $event,
            'reference' => json_decode($raw, true)['reference'] ?? null,
            'event_id_header' => $this->verifier->eventFromRequest($request),
            'signature_valid' => $signatureValid,
            'payload' => mb_substr($raw, 0, 60000),
        ]);

        if (! $signatureValid) {
            $log->update(['error' => 'Signature HMAC invalide']);

            Log::warning('DexPay webhook rejected: bad signature', ['event' => $event]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            $log->update(['error' => 'Payload JSON invalide']);

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Certaines versions de la documentation imbriquent le contenu sous « data ».
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
        $eventName = (string) ($event ?: $payload['event'] ?? '');

        try {
            $this->dispatch($eventName, $data, $log);
        } catch (\Throwable $e) {
            $log->update(['error' => $e->getMessage()]);

            Log::error('DexPay webhook failed', ['event' => $eventName, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Processing failed'], 500);
        }

        return response()->json(['received' => true]);
    }

    private function dispatch(string $event, array $data, WebhookLog $log): void
    {
        switch ($event) {
            case 'checkout.completed':
            case 'checkout.paid':
            case 'checkout.success':
                $this->onCompleted($data, $log);
                break;

            case 'checkout.failed':
            case 'checkout.cancelled':
                $this->onFailed($event, $data, $log);
                break;

            case 'checkout.refunded':
                $this->onRefunded($data, $log);
                break;

            case 'checkout.initiated':
            case 'checkout.pending':
                $log->update(['processed' => true]);
                break;

            case 'payout.completed':
            case 'payout.failed':
                $this->onPayout($event, $data, $log);
                break;

            default:
                $log->update(['processed' => true, 'error' => 'Événement ignoré : '.$event]);
                break;
        }
    }

    private function onCompleted(array $data, WebhookLog $log): void
    {
        $reservation = $this->findReservation($data);

        if (! $reservation) {
            $log->update(['processed' => true, 'error' => 'Réservation introuvable']);

            return;
        }

        // Idempotent : markPaid ne fait rien si déjà payée. La session de
        // paiement est créée/mise à jour ici (statut, transaction, frais) pour
        // que la page « Sessions de paiement » reflète le statut réel.
        $this->payments->applyRemoteStatus($reservation, $data);

        $log->update(['processed' => true, 'reference' => $reservation->reference]);
    }

    private function onFailed(string $event, array $data, WebhookLog $log): void
    {
        $reservation = $this->findReservation($data);

        if ($reservation) {
            $status = $event === 'checkout.cancelled' ? 'cancelled' : 'failed';
            $this->payments->markUnpaid($reservation, $status, $data);
            $log->update(['processed' => true, 'reference' => $reservation->reference]);

            return;
        }

        $log->update(['processed' => true]);
    }

    private function onRefunded(array $data, WebhookLog $log): void
    {
        $reservation = $this->findReservation($data);

        if (! $reservation) {
            $log->update(['processed' => true, 'error' => 'Réservation introuvable']);

            return;
        }

        $reservation->update(['payment_status' => Reservation::STATUS_REFUNDED]);
        $reservation->tickets()->where('status', 'valid')->update(['status' => 'refunded']);

        $log->update(['processed' => true, 'reference' => $reservation->reference]);
    }

    private function onPayout(string $event, array $data, WebhookLog $log): void
    {
        $dexpayId = $data['payout_id'] ?? $data['id'] ?? null;
        $merchantReference = $data['metadata']['merchant_reference'] ?? null;
        $remoteReference = $data['reference'] ?? null;

        // Le payout peut être identifié par son id DexPay, par notre référence
        // métier (metadata.merchant_reference) ou, à défaut, par la référence
        // DexPay. On teste les trois pour ne jamais rater la mise à jour.
        $payout = Payout::query()
            ->where(function ($query) use ($dexpayId, $merchantReference, $remoteReference) {
                if ($dexpayId) {
                    $query->orWhere('dexpay_id', $dexpayId);
                }
                if ($merchantReference) {
                    $query->orWhere('reference', $merchantReference);
                }
                if ($remoteReference) {
                    $query->orWhere('reference', $remoteReference);
                }
            })
            ->first();

        if ($payout) {
            $payout->update([
                'dexpay_id' => $payout->dexpay_id ?: $dexpayId,
                'status' => $event === 'payout.completed' ? 'COMPLETED' : 'FAILED',
                'provider_tx_id' => $data['provider_tx_id'] ?? $payout->provider_tx_id,
                'fees' => (int) ($data['fee_amount'] ?? $data['fees'] ?? $payout->fees),
                'failure_reason' => $data['failure_reason'] ?? null,
                'completed_at' => $event === 'payout.completed' ? now() : null,
            ]);
        }

        $log->update(['processed' => true]);
    }

    /** Retrouve la réservation via la référence métier ou les métadonnées. */
    private function findReservation(array $data): ?Reservation
    {
        $reference = $data['reference'] ?? null;

        if (is_string($reference) && $reference !== '') {
            $reservation = Reservation::where('reference', $reference)->first();

            if ($reservation) {
                return $reservation;
            }
        }

        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $reference = $metadata['reference'] ?? null;

        if (is_string($reference) && $reference !== '') {
            return Reservation::where('reference', $reference)->first();
        }

        if (isset($metadata['reservation_id'])) {
            return Reservation::find((int) $metadata['reservation_id']);
        }

        return null;
    }
}
