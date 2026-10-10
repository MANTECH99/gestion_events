<?php

namespace App\Http\Controllers;

use App\Exceptions\DexPayException;
use App\Models\Reservation;
use App\Services\DexPay\DexPayAccount;
use App\Services\DexPay\DexPayCheckout;
use App\Services\ReservationPaymentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly DexPayCheckout $dexpay,
        private readonly DexPayAccount $account,
        private readonly ReservationPaymentManager $payments,
    ) {}

    /** Page de paiement : résumé de la commande + choix du moyen de paiement. */
    public function show(Request $request, string $reference): View|RedirectResponse
    {
        $reservation = $this->findAccessible($request, $reference);

        if ($reservation->isPaid()) {
            return redirect()->route('payment.success', $reservation->reference);
        }

        $operators = $this->account->collectOperators();
        $serviceFee = dexpay_service_fee($reservation->total_amount, $operators[0] ?? null);

        // Avec client_support_fee=true, DexPay prélève en plus, sur sa page, ses
        // frais d'encaissement au même taux que la surcharge de retrait. On les
        // affiche pour que le client voie le total réellement débité.
        $dexpayFee = dexpay_service_fee($reservation->total_amount, $operators[0] ?? null);

        return view('payment.show', [
            'reservation' => $reservation,
            'operators' => $operators,
            'sandbox' => $this->dexpay->client()->isSandbox(),
            'configured' => $this->dexpay->client()->isConfigured(),
            'service_fee' => $serviceFee,
            'dexpay_fee' => $dexpayFee,
            'to_pay' => $reservation->total_amount + $serviceFee + $dexpayFee,
        ]);
    }

    /** Crée (ou réutilise) la checkout session DexPay et renvoie l'URL de paiement. */
    public function start(Request $request, string $reference): JsonResponse|RedirectResponse
    {
        $reservation = $this->findAccessible($request, $reference);

        if ($reservation->isPaid()) {
            return redirect()->route('payment.success', $reservation->reference);
        }

        $phone = (string) $request->input('customer_phone', $reservation->customer_phone ?? '');
        $operator = $request->input('operator');

        try {
            $this->payments->startOnlinePayment($reservation, $phone, $operator !== null ? (string) $operator : null);
        } catch (DexPayException $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $reservation->refresh();
        $payment = $reservation->latestPayment;

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'payment_url' => $payment?->payment_url,
                'reference' => $reservation->reference,
            ]);
        }

        return redirect()->route('payment.show', $reservation->reference);
    }

    /** Retour de DexPay (success_url) — la source de vérité reste le webhook. */
    public function success(Request $request, string $reference): View
    {
        $reservation = $this->findAccessible($request, $reference, allowSigned: true);

        if (! $reservation->isPaid() && $this->dexpay->client()->isConfigured()) {
            try {
                $this->dexpay->syncReservation($reservation);
            } catch (DexPayException) {
                // Le webhook peut ne pas être encore passé : la page affiche « en attente ».
            }
        }

        $reservation->refresh()->load('tickets');

        // Autorise l'affichage des tickets de cette réservation dans la session.
        $request->session()->put('reservation_access.'.$reservation->id, true);

        foreach ($reservation->tickets as $ticket) {
            $request->session()->put('ticket_access.'.$ticket->id, true);
        }

        return view('payment.success', [
            'reservation' => $reservation,
        ]);
    }

    public function failed(Request $request, string $reference): View
    {
        return view('payment.failed', [
            'reservation' => $this->findAccessible($request, $reference, allowSigned: true),
        ]);
    }

    /** Interrogé par la page de paiement pour rafraîchir le statut (SDK DexPay). */
    public function status(Request $request, string $reference): JsonResponse
    {
        $reservation = $this->findAccessible($request, $reference);

        return response()->json([
            'reference' => $reservation->reference,
            'status' => $reservation->payment_status,
            'status_label' => $reservation->status_label,
            'paid' => $reservation->isPaid(),
            'failed' => in_array($reservation->payment_status, [
                Reservation::STATUS_FAILED,
                Reservation::STATUS_CANCELLED,
                Reservation::STATUS_EXPIRED,
            ], true),
            'redirect' => $reservation->isPaid()
                ? route('payment.success', $reservation->reference)
                : null,
        ]);
    }

    /**
     * Autorise le propriétaire connecté, un admin/organisateur, l'internaute
     * qui vient de créer la réservation dans cette session, ou — pour les
     * retours de paiement — une URL signée valide.
     */
    private function findAccessible(Request $request, string $reference, bool $allowSigned = false): Reservation
    {
        $reservation = Reservation::where('reference', $reference)
            ->with(['event', 'ticketType'])
            ->firstOrFail();

        $user = $request->user();

        if ($user && ($reservation->user_id === $user->id || $user->isAdmin() || $user->isOrganizer())) {
            return $reservation;
        }

        if ($allowSigned && $request->hasValidSignature()) {
            return $reservation;
        }

        abort_unless($request->session()->has('reservation_access.'.$reservation->id), 403);

        return $reservation;
    }
}
