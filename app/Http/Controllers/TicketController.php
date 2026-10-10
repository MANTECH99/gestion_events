<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Ticket;
use App\Services\QrCodeService;
use App\Services\TicketPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TicketController extends Controller
{
    public function __construct(
        private readonly QrCodeService $qr,
        private readonly TicketPdfService $ticketPdf,
    ) {}

    /** Joli ticket format portrait avec QR code. */
    public function show(Request $request, Ticket $ticket): View
    {
        $this->authorizeTicket($request, $ticket);

        $ticket->load(['event.organizer', 'ticketType', 'reservation']);

        return view('tickets.show', [
            'ticket' => $ticket,
            'qr' => $this->qr->svg($ticket->qrPayload(), 360),
        ]);
    }

    /** Tous les tickets d'une réservation (affichage groupé après paiement). */
    public function reservationTickets(Request $request, string $reference): View
    {
        $reservation = Reservation::where('reference', $reference)
            ->with(['event.organizer', 'ticketType', 'tickets'])
            ->firstOrFail();

        $this->authorizeReservation($request, $reservation);

        return view('tickets.index', [
            'reservation' => $reservation,
            'tickets' => $reservation->tickets,
            'qr' => fn (Ticket $ticket) => $this->qr->svg($ticket->qrPayload(), 360),
        ]);
    }

    public function download(Request $request, Ticket $ticket): Response
    {
        $this->authorizeTicket($request, $ticket);

        $ticket->load(['event.organizer', 'ticketType', 'reservation', 'validator']);

        return response($this->ticketPdf->render(collect([$ticket])))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$ticket->code.'.pdf"');
    }

    public function downloadReservation(Request $request, string $reference): Response
    {
        $reservation = Reservation::where('reference', $reference)
            ->with(['event.organizer', 'ticketType', 'tickets'])
            ->firstOrFail();

        $this->authorizeReservation($request, $reservation);

        $pdf = Pdf::loadView('reservations.pdf', [
            'reservation' => $reservation,
            'tickets' => $reservation->tickets,
            // dompdf n'accepte pas le SVG inline : on passe des data-URI PNG/SVG.
            'qr' => fn (Ticket $ticket) => $this->qr->dataUri($ticket->qrPayload(), 320),
        ]);

        return $pdf->download('reservation-'.$reservation->reference.'.pdf');
    }

    private function authorizeTicket(Request $request, Ticket $ticket): void
    {
        $user = $request->user();

        if ($user && ($ticket->reservation?->user_id === $user->id || $user->isAdmin() || $user->isOrganizer() || $user->isAgent())) {
            return;
        }

        if ($request->hasValidSignature()) {
            return;
        }

        abort_unless($request->session()->has('ticket_access.'.$ticket->id), 403);
    }

    private function authorizeReservation(Request $request, Reservation $reservation): void
    {
        $user = $request->user();

        if ($user && ($reservation->user_id === $user->id || $user->isAdmin() || $user->isOrganizer() || $user->isAgent())) {
            return;
        }

        if ($request->hasValidSignature()) {
            return;
        }

        abort_unless($request->session()->has('reservation_access.'.$reservation->id), 403);
    }
}
