<?php

namespace App\Http\Controllers;

use App\Models\ArtistPointOfSale;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function show(Request $request, Event $event): View
    {
        abort_unless($event->status !== Event::STATUS_DRAFT || auth()->user()?->isAdmin() || auth()->user()?->id === $event->organizer_id, 404);

        $event->load('organizer', 'ticketTypes', 'artist', 'images', 'reviews');

        // Lien du point de vente artiste (?pov={token}) : on le mémorise dans
        // la session pour tracer les réservations en ligne créées ensuite.
        // ?pov=off efface la trace (« réserver sans le lien de l'artiste »).
        $artistPos = null;

        if ($request->query('pov') !== null) {
            $token = trim((string) $request->query('pov'));

            if ($token === '' || $token === 'off') {
                $request->session()->forget('artist_pos');
            } else {
                $pos = ArtistPointOfSale::where('token', $token)->first();

                // Seul un jeton rattaché à CET événement est retenu — et
                // seulement s'il est actif (un lien désactivé ne trace rien :
                // la visite retombe sur la page publique, sans attribution).
                if ($pos && $pos->event_id === $event->id && $pos->is_active) {
                    $request->session()->put('artist_pos', [
                        'token' => $pos->token,
                        'event_id' => $pos->event_id,
                    ]);
                } elseif ($pos && ! $pos->is_active) {
                    $request->session()->forget('artist_pos');
                }
            }
        } else {
            // Arrivée SANS le lien de l'artiste (accueil, recherche,
            // navigation normale sur le site) : on a quitté le lien —
            // la trace est close et le badge « Vous réservez via le lien
            // de … » ne doit pas persister sur les visites suivantes.
            $request->session()->forget('artist_pos');
        }

        $stored = $request->session()->get('artist_pos');

        if (is_array($stored) && (int) ($stored['event_id'] ?? 0) === $event->id) {
            $artistPos = ArtistPointOfSale::where('token', $stored['token'] ?? '')
                ->where('is_active', true)
                ->first();

            // Point de vente désactivé (ou supprimé) depuis la mise en
            // session : on purge la trace pour ne pas créditer un lien mort.
            if (! $artistPos) {
                $request->session()->forget('artist_pos');
            }
        }

        return view('events.show', [
            'event' => $event,
            'artistPos' => $artistPos,
            'artistPosBlocked' => $artistPos?->isExhausted() ?? false,
        ]);
    }
}
