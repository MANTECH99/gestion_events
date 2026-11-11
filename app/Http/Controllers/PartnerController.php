<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * Enregistre une demande « Rejoignez notre réseau de partenaires ».
     * Aucun mail n'est envoyé (MAIL_MAILER=log) : la demande est validée,
     * puis confirmée par un message flash affiché par partials/flash.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom'             => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255'],
            'structure'       => ['required', 'string', 'max:255'],
            'type_evenement'  => ['required', 'string', 'max:255'],
            'message'         => ['nullable', 'string', 'max:2000'],
        ], [], [
            'nom'            => 'nom complet',
            'email'          => 'email professionnel',
            'structure'      => 'nom de votre structure',
            'type_evenement' => "type d'événement",
            'message'        => 'message',
        ]);

        // Pas de persistance : le projet n'a ni table ni mail configuré pour
        // cela. On confirme à l'utilisateur (le portefeuille arrive plus tard).
        unset($validated);

        // Clé dédiée : affichée par la section elle-même (l'ancre #partenaires-form
        // saute directement dessus, le bandeau haut de partials/flash ne se verrait pas).
        return redirect()
            ->route('partners.become')
            ->withFragment('partenaires-form')
            ->with('partner_success', 'Merci ! Votre demande a bien été envoyée, notre équipe vous contactera rapidement.');
    }
}
