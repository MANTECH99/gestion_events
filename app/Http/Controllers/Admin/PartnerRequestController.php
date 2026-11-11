<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Demandes du formulaire « Rejoignez notre réseau de partenaires ».
 * L'admin les consulte et les traite depuis son sidebar : aucun mail.
 */
class PartnerRequestController extends Controller
{
    /** Liste des demandes (recherche + filtre de statut). */
    public function index(Request $request): View
    {
        $statut = $request->string('statut')->value();
        $q = trim($request->string('q')->value());

        return view('admin.partner-requests.index', [
            'requests' => PartnerRequest::query()
                ->when($statut !== '' && in_array($statut, [PartnerRequest::STATUS_NOUVEAU, PartnerRequest::STATUS_TRAITE], true),
                    fn ($query) => $query->where('statut', $statut))
                ->when($q !== '', function ($query) use ($q) {
                    $like = '%'.$q.'%';
                    $query->where(fn ($w) => $w
                        ->where('nom', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('structure', 'like', $like));
                })
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString(),
            'filterStatut' => in_array($statut, [PartnerRequest::STATUS_NOUVEAU, PartnerRequest::STATUS_TRAITE], true) ? $statut : '',
            'q' => $q,
            'counts' => [
                'tout' => PartnerRequest::count(),
                'nouveau' => PartnerRequest::where('statut', PartnerRequest::STATUS_NOUVEAU)->count(),
                'traite' => PartnerRequest::where('statut', PartnerRequest::STATUS_TRAITE)->count(),
            ],
        ]);
    }

    /** Passe une demande de « nouveau » à « traité » (et l'inverse). */
    public function update(PartnerRequest $partnerRequest): RedirectResponse
    {
        $nouveauStatut = $partnerRequest->isTraitee()
            ? PartnerRequest::STATUS_NOUVEAU
            : PartnerRequest::STATUS_TRAITE;

        $partnerRequest->update([
            'statut' => $nouveauStatut,
            'traite_at' => $nouveauStatut === PartnerRequest::STATUS_TRAITE ? now() : null,
        ]);

        return back()->with('success', $nouveauStatut === PartnerRequest::STATUS_TRAITE
            ? 'La demande de '.$partnerRequest->nom.' est marquée comme traitée.'
            : 'La demande de '.$partnerRequest->nom.' est de nouveau à traiter.');
    }
}
