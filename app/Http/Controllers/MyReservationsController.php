<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Ticket;
use App\Services\QrCodeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MyReservationsController extends Controller
{
    public function __construct(private readonly QrCodeService $qr) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $reservations = Reservation::query()
            ->with(['event', 'ticketType', 'tickets'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('reservations.index', [
            'reservations' => $reservations,
        ]);
    }

    public function show(Request $request, string $reference): View
    {
        $reservation = Reservation::where('reference', $reference)
            ->with(['event.organizer', 'ticketType', 'tickets'])
            ->firstOrFail();

        $user = $request->user();

        abort_unless(
            $reservation->user_id === $user->id || $user->isAdmin() || $user->isOrganizer(),
            403
        );

        return view('reservations.show', [
            'reservation' => $reservation,
            'qr' => fn (Ticket $ticket) => $this->qr->svg($ticket->qrPayload(), 360),
        ]);
    }
}
