<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Rapports PDF par événement.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $events = Event::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->withCount('reservations')
            ->orderByDesc('event_date')
            ->get();

        return view('organizer.reports.index', [
            'events' => $events,
        ]);
    }

    /** Rapport Réservations pour l'événement : X. */
    public function show(Request $request, Event $event): View
    {
        $this->authorizeEvent($request, $event);

        return view('organizer.reports.show', [
            'event' => $event,
            'rows' => $this->rows($event),
            'totals' => $this->totals($event),
        ]);
    }

    public function download(Request $request, Event $event): Response
    {
        $this->authorizeEvent($request, $event);

        $pdf = Pdf::loadView('organizer.reports.pdf', [
            'event' => $event,
            'rows' => $this->rows($event),
            'totals' => $this->totals($event),
        ])->setPaper('a4');

        $name = 'rapport-'.str($event->slug)->slug().'.pdf';

        return $pdf->download($name);
    }

    /**
     * Utilisateur | Quantité | Statut | Prix total
     *
     * @return Collection<int, object>
     */
    private function rows(Event $event)
    {
        return Reservation::query()
            ->where('event_id', $event->id)
            ->with(['ticketType', 'user', 'soldBy'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (Reservation $r) => [
                'user' => $r->user?->full_name ?: $r->customer_name,
                'email' => $r->user?->email ?: $r->customer_email,
                'type' => $r->ticketType?->name,
                'quantity' => $r->quantity,
                'status' => $r->status_label,
                'status_color' => $r->status_color,
                'channel' => $r->channel,
                'price' => $r->total_amount,
                'date' => $r->created_at,
            ]);
    }

    /** @return array{quantity:int, revenue:int, reservations:int, paid:int} */
    private function totals(Event $event): array
    {
        $reservations = Reservation::where('event_id', $event->id)->get();

        return [
            'quantity' => (int) $reservations->sum('quantity'),
            'revenue' => (int) $reservations->where('payment_status', Reservation::STATUS_PAID)->sum('total_amount'),
            'reservations' => $reservations->count(),
            'paid' => $reservations->where('payment_status', Reservation::STATUS_PAID)->count(),
        ];
    }

    private function authorizeEvent(Request $request, Event $event): void
    {
        abort_unless($request->user()->isAdmin() || $event->organizer_id === $request->user()->id, 403);
    }
}
