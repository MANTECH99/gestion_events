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
    /**
     * Page de réservation : le récapitulatif part des quantités choisies sur la
     * page événement (?qty[{typeId}]={quantité}). Sans sélection, la page laisse
     * ajouter les types de billets directement dans le formulaire.
     */
    public function create(Request $request, Event $event): View|RedirectResponse
    {
        if (! $event->isOnSale()) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Les réservations en ligne ne sont pas ouvertes pour cet événement.');
        }

        $event->load('ticketTypes');

        $types = $event->ticketTypes->where('is_active', true)->values();
        $qtys = [];

        foreach ((array) $request->input('qty', []) as $typeId => $qty) {
            $typeId = (int) $typeId;
            $type = $types->firstWhere('id', $typeId);

            if (! $type || ! is_numeric($qty)) {
                continue;
            }

            $available = $type->available_online;
            $limit = $available === null ? 20 : min(20, $available);
            $qty = (int) $qty;

            if ($qty >= 1 && $limit >= 1) {
                $qtys[$typeId] = min($qty, $limit);
            }
        }

        return view('booking.create', [
            'event' => $event,
            'types' => $types,
            'qtys' => $qtys,
        ]);
    }

    public function store(Request $request, Event $event): RedirectResponse
    {
        abort_unless($event->isOnSale(), 404);

        $useRecap = $request->has('qty') || ! $request->filled('ticket_type_id');

        if ($useRecap) {
            $data = $request->validate([
                'customer_name' => ['required', 'string', 'min:3', 'max:120'],
                'customer_email' => ['required', 'email', 'max:150'],
                'customer_phone' => ['nullable', 'string', 'max:30'],
                'customer_city' => ['nullable', 'string', 'max:80'],
                'customer_address' => ['nullable', 'string', 'max:190'],
                'qty' => ['required', 'array', 'min:1'],
                'qty.*' => ['required', 'integer', 'min:1', 'max:20'],
            ], [
                'qty.required' => 'Sélectionnez au moins un billet.',
                'qty.min' => 'Sélectionnez au moins un billet.',
                'qty.*.max' => '20 tickets maximum par type de billet.',
            ], [
                'customer_name' => 'nom',
                'customer_email' => 'adresse email',
                'customer_phone' => 'téléphone',
                'customer_city' => 'ville',
                'customer_address' => 'adresse',
                'qty' => 'sélection de billets',
                'qty.*' => 'quantité',
            ]);

            $types = TicketType::query()
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->whereIn('id', array_keys($data['qty']))
                ->get()
                ->keyBy('id');

            if ($types->count() !== count($data['qty'])) {
                return back()->withInput()
                    ->withErrors(['qty' => 'Un des types de billets sélectionnés n\'est plus disponible.']);
            }

            $lines = [];

            foreach ($data['qty'] as $typeId => $qty) {
                $lines[] = ['type' => $types->get((int) $typeId), 'qty' => (int) $qty];
            }
        } else {
            // Format historique : un seul type de billet.
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

            $lines = [['type' => $ticketType, 'qty' => (int) $data['quantity']]];
        }

        $single = count($lines) === 1;

        foreach ($lines as $line) {
            $available = $line['type']->available_online;

            if ($available !== null && $line['qty'] > $available) {
                $message = 'Il ne reste que '.($available ?: 0).' ticket(s) disponible(s) pour ce type.';

                if (! $single) {
                    $message = 'Il ne reste que '.($available ?: 0).' ticket(s) disponible(s) pour le type '.$line['type']->name.'.';
                }

                return back()->withInput()->with('error', $message);
            }
        }

        $reservations = DB::transaction(function () use ($lines, $data, $event) {
            $created = [];

            foreach ($lines as $line) {
                $ticketType = TicketType::query()
                    ->whereKey($line['type']->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $created[] = Reservation::create([
                    'reference' => Reservation::makeReference(),
                    'event_id' => $event->id,
                    'ticket_type_id' => $ticketType->id,
                    'user_id' => auth()->id(),
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'customer_city' => $data['customer_city'] ?? null,
                    'customer_address' => $data['customer_address'] ?? null,
                    'quantity' => $line['qty'],
                    'unit_price' => $ticketType->price,
                    'total_amount' => $ticketType->price * $line['qty'],
                    'payment_status' => Reservation::STATUS_PENDING,
                    'channel' => Reservation::CHANNEL_ONLINE,
                ]);
            }

            return $created;
        });

        // Autorise cette session (invité ou client) à suivre le paiement.
        foreach ($reservations as $reservation) {
            $request->session()->put('reservation_access.'.$reservation->id, true);
        }

        // Toutes les réservations de la commande, affichées sur la page de succès.
        $request->session()->put(
            'payment_refs',
            array_map(fn (Reservation $r) => $r->reference, $reservations),
        );

        return redirect()->route('payment.show', $reservations[0]->reference);
    }
}
