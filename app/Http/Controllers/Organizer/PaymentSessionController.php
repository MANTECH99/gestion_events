<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\DexPay\DexPayCheckout;
use App\Services\ReservationPaymentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Sessions de paiement DexPay des réservations de l'organisateur. */
class PaymentSessionController extends Controller
{
    public function __construct(
        private readonly DexPayCheckout $checkout,
        private readonly ReservationPaymentManager $payments,
    ) {}

    /** Libellés des statuts de paiement DexPay. */
    public static function statusLabels(): array
    {
        return [
            'initiated' => 'Initiee',
            'pending' => 'En attente',
            'in_progress' => 'En cours',
            'processing' => 'En traitement',
            'completed' => 'Payée',
            'success' => 'Payée',
            'failed' => 'Échouée',
            'cancelled' => 'Annulée',
            'expired' => 'Expirée',
            'refunded' => 'Remboursée',
        ];
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $status = $request->string('status')->toString();
        $status = $status !== '' && $status !== 'all' ? $status : null;

        $scoped = fn ($q) => $q->when(
            ! $user->isAdmin(),
            fn ($q) => $q->whereHas('reservation.event', fn ($q) => $q->where('organizer_id', $user->id))
        );

        $sessions = $scoped(Payment::query())
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['reservation.event'])
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('organizer.payment-sessions.index', [
            'sessions' => $sessions,
            'statuses' => $scoped(Payment::query())
                ->distinct()
                ->orderBy('status')
                ->pluck('status')
                ->values()
                ->all(),
            'filters' => ['status' => $status],
            'statusLabels' => self::statusLabels(),
        ]);
    }

    /** Re-synchronise une session avec DexPay (statut et frais). */
    public function sync(Request $request, Payment $payment): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin() || $payment->reservation?->event?->organizer_id === $user->id,
            403
        );

        if (! $payment->reservation) {
            return back()->with('error', 'Cette session n\'est liée à aucune réservation.');
        }

        $remote = $this->checkout->get($payment->reference);

        if (! $remote) {
            return back()->with('error', 'Session introuvable sur DexPay.');
        }

        $this->payments->applyRemoteStatus($payment->reservation, $remote);

        $status = strtolower((string) ($remote['status'] ?? $payment->status));

        return back()->with(
            'success',
            'Session actualisée : '.self::statusLabels()[$status] ?? ucfirst($status).'.'
        );
    }
}
