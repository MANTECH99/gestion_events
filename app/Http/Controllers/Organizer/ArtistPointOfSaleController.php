<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\ArtistPointOfSale;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Points de vente en ligne des artistes : un lien partageable vers la page
 * d'un événement, avec un quota de billets. Les ventes restent le système
 * public existant — seul le traçage (artist_pos_id) est ajouté.
 */
class ArtistPointOfSaleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $pointsOfSale = ArtistPointOfSale::query()
            ->with(['artist', 'event'])
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas(
                'event',
                fn ($e) => $e->where('organizer_id', $user->organizerScopeId())
            ))
            // Agrégats limités aux réservations payées (quota consommé = vendu).
            ->withSum(['reservations as tickets_sold_sum' => fn ($q) => $q->paid()], 'quantity')
            ->withSum(['reservations as revenue_sum' => fn ($q) => $q->paid()], 'total_amount')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('organizer.artist-pos.index', ['pointsOfSale' => $pointsOfSale]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        return view('organizer.artist-pos.create', [
            'artists' => Artist::orderBy('name')->get(),
            'events' => Event::when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->organizerScopeId()))
                ->orderByDesc('event_date')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $eventRule = Rule::exists('events', 'id');

        if (! $user->isAdmin()) {
            $eventRule->where('organizer_id', $user->organizerScopeId());
        }

        $data = $request->validate([
            'artist_id' => ['required', 'integer', Rule::exists('artists', 'id')],
            'event_id' => ['required', 'integer', $eventRule],
            'ticket_limit' => ['required', 'integer', 'min:1'],
        ], [
            'artist_id.required' => 'Choisissez un artiste.',
            'event_id.required' => 'Choisissez un événement.',
            'event_id.exists' => 'Cet événement n\'est pas disponible.',
            'ticket_limit.required' => 'Indiquez le nombre de billets assignés.',
            'ticket_limit.min' => 'Assignez au moins 1 billet.',
        ], [
            'artist_id' => 'artiste',
            'event_id' => 'événement',
            'ticket_limit' => 'nombre de billets',
        ]);

        $artistPos = ArtistPointOfSale::create([
            'artist_id' => $data['artist_id'],
            'event_id' => $data['event_id'],
            'ticket_limit' => $data['ticket_limit'],
            'token' => ArtistPointOfSale::makeToken(),
        ]);

        return redirect()->route('organizer.artist-pos.show', $artistPos)
            ->with('success', 'Point de vente créé — partagez son lien à l\'artiste.');
    }

    public function show(Request $request, ArtistPointOfSale $artistPos): View
    {
        $this->authorizePos($request, $artistPos);

        $artistPos->load(['artist', 'event']);
        $sold = $artistPos->soldQuantity();

        return view('organizer.artist-pos.show', [
            'artistPos' => $artistPos,
            // Toutes les ventes du lien (statut de paiement compris).
            'sales' => $artistPos->reservations()
                ->with(['ticketType', 'tickets'])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'sold' => $sold,
            'revenue' => $artistPos->revenue(),
            'remaining' => max(0, (int) $artistPos->ticket_limit - $sold),
        ]);
    }

    public function update(Request $request, ArtistPointOfSale $artistPos): RedirectResponse
    {
        $this->authorizePos($request, $artistPos);

        $data = $request->validate([
            'ticket_limit' => ['required', 'integer', 'min:1'],
        ], [
            'ticket_limit.required' => 'Indiquez le nombre de billets assignés.',
            'ticket_limit.min' => 'Assignez au moins 1 billet.',
        ], [
            'ticket_limit' => 'nombre de billets',
        ]);

        $sold = $artistPos->soldQuantity();

        if ($data['ticket_limit'] < $sold) {
            return back()->with('error', "Impossible : {$sold} billet(s) déjà vendu(s) via ce lien.");
        }

        $artistPos->update(['ticket_limit' => $data['ticket_limit']]);

        return back()->with('success', 'Nombre de billets mis à jour.');
    }

    public function destroy(Request $request, ArtistPointOfSale $artistPos): RedirectResponse
    {
        $this->authorizePos($request, $artistPos);

        $artistPos->delete();

        return redirect()->route('organizer.artist-pos.index')
            ->with('success', 'Point de vente supprimé.');
    }

    /** Le point de vente doit appartenir à l'organisateur (ou admin). */
    private function authorizePos(Request $request, ArtistPointOfSale $artistPos): void
    {
        $user = $request->user();

        abort_unless(
            $user->isAdmin() || (int) $artistPos->event?->organizer_id === $user->organizerScopeId(),
            404
        );
    }
}
