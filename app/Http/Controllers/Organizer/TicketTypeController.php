<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketTypeController extends Controller
{
    public function index(Request $request, Event $event): View
    {
        $this->authorizeEvent($request, $event);

        $event->load(['ticketTypes' => fn ($q) => $q->with('stockAllocations')]);

        return view('organizer.events.ticket-types', [
            'event' => $event,
        ]);
    }

    /** « Ajouter un type de ticket pour {Événement} » */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($request, $event);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ], [], [
            'name' => 'nom du ticket',
            'price' => 'prix',
            'quantity' => 'quantité disponible',
        ]);

        $event->ticketTypes()->create($data + ['is_active' => true]);

        return back()->with('success', "Type de ticket « {$data['name']} » ajouté.");
    }

    public function update(Request $request, TicketType $ticketType): RedirectResponse
    {
        $this->authorizeType($request, $ticketType);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'description' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'nom du ticket',
            'price' => 'prix',
            'quantity' => 'quantité disponible',
        ]);

        $ticketType->update([
            ...$data,
            'quantity' => $data['quantity'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Type de ticket mis à jour.');
    }

    public function destroy(Request $request, TicketType $ticketType): RedirectResponse
    {
        $this->authorizeType($request, $ticketType);

        if ($ticketType->reservations()->exists() || $ticketType->tickets()->exists()) {
            return back()->with('error', 'Ce type de ticket est déjà utilisé : désactivez-le plutôt que le supprimer.');
        }

        $ticketType->delete();

        return back()->with('success', 'Type de ticket supprimé.');
    }

    private function authorizeEvent(Request $request, Event $event): void
    {
        abort_unless($request->user()->isAdmin() || $event->organizer_id === $request->user()->id, 403);
    }

    private function authorizeType(Request $request, TicketType $ticketType): void
    {
        abort_unless(
            $request->user()->isAdmin() || $ticketType->event?->organizer_id === $request->user()->id,
            403
        );
    }
}
