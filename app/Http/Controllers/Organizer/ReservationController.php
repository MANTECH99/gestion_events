<?php

namespace App\Http\Controllers\Organizer;

use App\Exceptions\DexPayException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Services\QrCodeService;
use App\Services\ReservationPaymentManager;
use App\Services\StatsService;
use App\Services\TicketPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationPaymentManager $payments,
        private readonly QrCodeService $qr,
        private readonly StatsService $stats,
        private readonly TicketPdfService $ticketPdf,
    ) {}

    /** Liste des réservations. */
    public function index(Request $request): View
    {
        $user = $request->user();

        $eventOptions = Event::when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->orderByDesc('event_date')
            ->get();

        // Les cartes de synthèse ne se limitent qu'à l'événement choisi (et
        // restent cloisonnées aux événements dont l'utilisateur est propriétaire).
        $ownedIds = $eventOptions->pluck('id')->all();
        $eventIds = $request->filled('event_id')
            ? array_values(array_intersect($ownedIds, [$request->integer('event_id')]))
            : $ownedIds;

        $query = Reservation::query()
            ->with(['event', 'ticketType', 'user', 'soldBy'])
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('event', fn ($e) => $e->where('organizer_id', $user->id)))
            ->when($request->filled('event_id'), fn ($q) => $q->where('event_id', $request->integer('event_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status', $request->string('status')->value()))
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->string('channel')->value()))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->trim()->value();
                $q->where(function ($sub) use ($term) {
                    $sub->where('reference', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%")
                        ->orWhere('customer_email', 'like', "%{$term}%");
                });
            })
            // Tri déterministe : sans départage par id, des réservations créées
            // au même instant peuvent changer de page (lignes omises/dupliquées).
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return view('organizer.reservations.index', [
            'reservations' => $query->paginate(20)->withQueryString(),
            'events' => $eventOptions,
            'summary' => $this->stats->summary(null, null, $eventIds),
            'filters' => $request->only(['event_id', 'status', 'channel', 'q']),
        ]);
    }

    /** Génère le PDF d'une réservation. */
    public function download(Request $request, Reservation $reservation): Response
    {
        $this->authorizeReservation($request, $reservation);

        $reservation->load(['event.organizer', 'ticketType', 'tickets']);

        return Pdf::loadView('organizer.reservations.pdf', [
            'reservation' => $reservation,
            'tickets' => $reservation->tickets,
            // dompdf n'accepte pas le SVG inline : on passe des data-URI.
            'qr' => fn (Ticket $ticket) => $this->qr->dataUri($ticket->qrPayload(), 320),
        ])->download('reservation-'.$reservation->reference.'.pdf');
    }

    /** Génère le PDF des billets seuls (même gabarit que la carte affichée). */
    public function downloadTickets(Request $request, Reservation $reservation): Response
    {
        $this->authorizeReservation($request, $reservation);

        $reservation->load(['tickets.event.organizer', 'tickets.ticketType', 'tickets.reservation', 'tickets.validator']);

        return response($this->ticketPdf->render($reservation->tickets))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="billets-'.$reservation->reference.'.pdf"');
    }

    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeReservation($request, $reservation);

        if ($reservation->isPaid()) {
            return back()->with('error', 'Une réservation payée ne peut pas être supprimée : remboursez-la d\'abord.');
        }

        $reservation->delete();

        return back()->with('success', 'Réservation supprimée.');
    }

    public function refund(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeReservation($request, $reservation);

        try {
            $this->payments->refund($reservation);
        } catch (DexPayException $e) {
            return back()->with('error', 'Remboursement impossible : '.$e->getMessage());
        }

        return back()->with('success', "Réservation {$reservation->reference} remboursée.");
    }

    private function authorizeReservation(Request $request, Reservation $reservation): void
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin() || $reservation->event?->organizer_id === $user->id,
            403
        );
    }
}
