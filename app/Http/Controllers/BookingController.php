<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\TicketType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function create(Request $request, Event $event): View|RedirectResponse
    {
        if (! $event->isOnSale()) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Les réservations en ligne ne sont pas ouvertes pour cet événement.');
        }

        return view('booking.create', [
            'event' => $event->load('ticketTypes'),
            'selectedType' => $request->integer('type') ?: null,
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->isOnSale(), 404);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:3', 'max:120'],
            'customer_email' => ['required', 'email', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'ticket_type_id' => ['required', Rule::exists('ticket_types', 'id')->where('event_id', $event->id)],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ], [], [
            'customer_name' => 'nom',
            'customer_email' => 'adresse email',
            'ticket_type_id' => 'type de ticket',
            'quantity' => 'quantité',
        ]);

        $ticketType = TicketType::query()
            ->where('event_id', $event->id)
            ->where('is_active', true)
            ->findOrFail($data['ticket_type_id']);

        $quantity = (int) $data['quantity'];
        $available = $ticketType->available_online;

        if ($available !== null && $quantity > $available) {
            return back()->withInput()->with('error', 'Il ne reste que '.($available ?: 0).' ticket(s) disponible(s) pour ce type.');
        }

        $reservation = DB::transaction(function () use ($data, $event, $ticketType, $quantity) {
            return Reservation::create([
                'reference' => Reservation::makeReference(),
                'event_id' => $event->id,
                'ticket_type_id' => $ticketType->id,
                'user_id' => auth()->id(),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $ticketType->price,
                'total_amount' => $ticketType->price * $quantity,
                'payment_status' => Reservation::STATUS_PENDING,
                'channel' => Reservation::CHANNEL_ONLINE,
            ]);
        });

        // Autorise cette session (invité ou client) à suivre le paiement.
        $request->session()->put('reservation_access.'.$reservation->id, true);

        return redirect()->route('payment.show', $reservation->reference);
    }
}
