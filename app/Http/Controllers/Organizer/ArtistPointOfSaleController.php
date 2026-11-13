<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\ArtistPointOfSale;
use App\Models\ArtistPosAllocation;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Points de vente en ligne des artistes : un lien partageable vers la page
 * d'un événement, avec un quota de billets RÉSERVÉS par type de billet
 * (l'assignation réduit le stock en ligne public, comme le stock physique).
 * Les ventes restent le système public existant — seul le traçage
 * (artist_pos_id) est ajouté.
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
            // Billets assignés (réservés du stock en ligne), tous types.
            ->withSum('allocations as allocated_sum', 'allocated')
            // Désactivation/suppression : la suppression n'est proposée qu'aux
            // points de vente sans aucune vente rattachée.
            ->withCount('reservations')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('organizer.artist-pos.index', ['pointsOfSale' => $pointsOfSale]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        $events = Event::with(['ticketTypes' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->organizerScopeId()))
            ->orderByDesc('event_date')
            ->get();

        // Capacité d'assignation restante par type (null = stock illimité).
        $capacities = [];
        foreach ($events as $event) {
            foreach ($event->ticketTypes as $type) {
                $capacities[$type->id] = $this->maxAllocatable($type, null);
            }
        }

        return view('organizer.artist-pos.create', [
            'artists' => Artist::orderBy('name')->get(),
            'events' => $events,
            'capacities' => $capacities,
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
            'allocations' => ['required', 'array'],
            'allocations.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], [
            'artist_id.required' => 'Choisissez un artiste.',
            'event_id.required' => 'Choisissez un événement.',
            'event_id.exists' => 'Cet événement n\'est pas disponible.',
            'allocations.required' => 'Indiquez le nombre de billets à assigner.',
            'allocations.*.integer' => 'Nombre de billets invalide.',
        ], [
            'artist_id' => 'artiste',
            'event_id' => 'événement',
            'allocations' => 'billets assignés',
        ]);

        // Seuls les types de billet de l'événement choisi sont retenus (les
        // autres groupes de champs sont désactivés dans le formulaire).
        $typeIds = TicketType::query()
            ->where('event_id', $data['event_id'])
            ->where('is_active', true)
            ->pluck('id')
            ->flip();

        $allocations = [];
        foreach ((array) $request->input('allocations', []) as $typeId => $qty) {
            if ($typeIds->has((int) $typeId) && is_numeric($qty)) {
                $allocations[(int) $typeId] = (int) $qty;
            }
        }

        if (! array_filter($allocations, fn (int $qty) => $qty >= 1)) {
            return back()->withInput()->withErrors([
                'allocations' => 'Assignez au moins 1 billet.',
            ]);
        }

        // Chaque type ne peut pas puiser dans le stock public réservé aux
        // autres points de vente (physique ou artiste).
        $errors = [];
        foreach ($allocations as $typeId => $qty) {
            if ($qty < 1) {
                continue;
            }

            $max = $this->maxAllocatable(TicketType::findOrFail($typeId), null);

            if ($max !== null && $qty > $max) {
                $errors["allocations.{$typeId}"] = "Il ne reste que {$max} billet(s) disponible(s) pour ce type.";
            }
        }

        if ($errors) {
            return back()->withInput()->withErrors($errors);
        }

        $artistPos = DB::transaction(function () use ($data, $allocations) {
            $artistPos = ArtistPointOfSale::create([
                'artist_id' => $data['artist_id'],
                'event_id' => $data['event_id'],
                'token' => ArtistPointOfSale::makeToken(),
            ]);

            foreach ($allocations as $typeId => $qty) {
                if ($qty < 1) {
                    continue;
                }

                ArtistPosAllocation::create([
                    'artist_pos_id' => $artistPos->id,
                    'ticket_type_id' => $typeId,
                    'allocated' => $qty,
                    'sold' => 0,
                ]);
            }

            return $artistPos;
        });

        return redirect()->route('organizer.artist-pos.show', $artistPos)
            ->with('success', 'Point de vente créé — partagez son lien à l\'artiste.');
    }

    public function show(Request $request, ArtistPointOfSale $artistPos): View
    {
        $this->authorizePos($request, $artistPos);

        $artistPos->load(['artist', 'event', 'allocations']);

        $types = $artistPos->event
            ? $artistPos->event->ticketTypes()->where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('organizer.artist-pos.show', [
            'artistPos' => $artistPos,
            // Types de billets du formulaire d'ajustement + allocations.
            'types' => $types,
            'allocations' => $artistPos->allocations->keyBy('ticket_type_id'),
            // Toutes les ventes du lien (statut de paiement compris).
            'sales' => $artistPos->reservations()
                ->with(['ticketType', 'tickets'])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'sold' => $artistPos->soldQuantity(),
            'revenue' => $artistPos->revenue(),
            'totalAllocated' => $artistPos->totalAllocated(),
            'remaining' => $artistPos->remaining(),
        ]);
    }

    public function update(Request $request, ArtistPointOfSale $artistPos): RedirectResponse
    {
        $this->authorizePos($request, $artistPos);

        $data = $request->validate([
            'ticket_type_id' => [
                'required',
                'integer',
                Rule::exists('ticket_types', 'id')
                    ->where('event_id', $artistPos->event_id)
                    ->where('is_active', true),
            ],
            'allocations' => ['required', 'array'],
            'allocations.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], [
            'ticket_type_id.required' => 'Type de billet invalide.',
            'allocations.required' => 'Indiquez le nombre de billets assignés.',
            'allocations.*.integer' => 'Nombre de billets invalide.',
        ], [
            'ticket_type_id' => 'type de billet',
            'allocations' => 'nombre de billets',
        ]);

        $type = TicketType::findOrFail($data['ticket_type_id']);

        // Champ nommé allocations[{typeId}] : la valeur ciblée est celle du
        // type de billet du formulaire soumis.
        $allocated = $data['allocations'][$type->id] ?? null;

        if ($allocated === null) {
            return back()->withInput()->withErrors([
                'allocations.'.$type->id => 'Indiquez le nombre de billets assignés.',
            ]);
        }

        $allocated = (int) $allocated;
        $allocation = $artistPos->allocations()->where('ticket_type_id', $type->id)->first();
        $soldForType = (int) ($allocation->sold ?? 0);

        if ($allocated < $soldForType) {
            return back()->with('error', "Impossible : {$soldForType} billet(s) déjà vendu(s) via ce lien pour « {$type->name} ».");
        }

        $max = $this->maxAllocatable($type, $artistPos);

        if ($max !== null && $allocated > $max) {
            return back()->with('error', "Impossible : il ne reste que {$max} billet(s) disponible(s) pour « {$type->name} ».");
        }

        ArtistPosAllocation::updateOrCreate(
            ['artist_pos_id' => $artistPos->id, 'ticket_type_id' => $type->id],
            ['allocated' => $allocated, 'sold' => $soldForType],
        );

        return back()->with('success', "Nombre de billets mis à jour pour « {$type->name} ».");
    }

    /** Désactiver / réactiver le lien : les ventes passées sont conservées. */
    public function toggle(Request $request, ArtistPointOfSale $artistPos): RedirectResponse
    {
        $this->authorizePos($request, $artistPos);

        $artistPos->update(['is_active' => ! $artistPos->is_active]);

        return back()->with(
            'success',
            $artistPos->is_active
                ? 'Lien réactivé — l\'artiste peut de nouveau vendre via son lien.'
                : 'Lien désactivé — plus aucune vente via ce lien. Les ventes passées et leur attribution sont conservées.'
        );
    }

    public function destroy(Request $request, ArtistPointOfSale $artistPos): RedirectResponse
    {
        $this->authorizePos($request, $artistPos);

        // Une vente rattachée ne doit jamais être détachée de l'artiste
        // (l'attribution disparaîtrait de la liste des réservations) :
        // on impose la désactivation, comme le stock physique qui refuse
        // de supprimer une allocation déjà vendue.
        if ($artistPos->reservations()->exists()) {
            return redirect()->route('organizer.artist-pos.index')->with(
                'error',
                'Impossible de supprimer : des ventes sont déjà rattachées à ce lien. Désactivez-le plutôt — l\'historique reste conservé.'
            );
        }

        $artistPos->delete();

        return redirect()->route('organizer.artist-pos.index')
            ->with('success', 'Point de vente supprimé.');
    }

    /**
     * Nombre maximal de billets de ce type encore assignables à ce point de
     * vente (null = stock illimité).
     *
     * Invariant du stock public : quantity − stock physique − Σ(restantes
     * artiste) − vendus en ligne ≥ 0. Les ventes via un lien d'artiste sont
     * comptées dans sold_online mais décomptent le quota de l'artiste : le
     * propre sold du point de vente est donc recrédité ici.
     */
    private function maxAllocatable(TicketType $type, ?ArtistPointOfSale $pos): ?int
    {
        if ($type->quantity === null) {
            return null;
        }

        $othersRemaining = ArtistPosAllocation::query()
            ->where('ticket_type_id', $type->id)
            ->when($pos, fn ($q) => $q->where('artist_pos_id', '!=', $pos->id))
            ->get()
            ->sum(fn (ArtistPosAllocation $a) => $a->remaining);

        $ownSold = $pos
            ? (int) $pos->allocations()->where('ticket_type_id', $type->id)->value('sold')
            : 0;

        return $ownSold + max(
            0,
            $type->quantity - $type->physical_allocated - $type->sold_online - $othersRemaining
        );
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
