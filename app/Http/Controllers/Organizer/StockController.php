<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\StockAllocation;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Attribution du stock de tickets physiques aux points de vente.
 */
class StockController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $ticketTypes = TicketType::query()
            ->with('event')
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('event', fn ($e) => $e->where('organizer_id', $user->id)))
            ->orderByDesc('id')
            ->get();

        $allocations = StockAllocation::query()
            ->with(['ticketType.event', 'user'])
            ->whereHas('ticketType.event', fn ($q) => $q->when(! $user->isAdmin(), fn ($w) => $w->where('organizer_id', $user->id)))
            ->orderByDesc('id')
            ->get();

        return view('organizer.stocks.index', [
            'ticketTypes' => $ticketTypes,
            'sellers' => User::query()
                ->where('role', User::ROLE_USER)
                ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
                ->orderBy('first_name')
                ->get(),
            'allocations' => $allocations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'ticket_type_id' => ['required', Rule::exists('ticket_types', 'id')],
            'user_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where(fn ($query) => $query
                        ->where('role', User::ROLE_USER)
                        ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))),
            ],
            'allocated' => ['required', 'integer', 'min:0', 'max:1000000'],
        ], [], [
            'ticket_type_id' => 'type de ticket',
            'user_id' => 'point de vente',
            'allocated' => 'quantité attribuée',
        ]);

        $ticketType = TicketType::with('event')->findOrFail($data['ticket_type_id']);
        $seller = User::findOrFail($data['user_id']);

        $this->authorizeOwner($user, $ticketType->event?->organizer_id, $seller->organizer_id);

        $max = $this->maxPhysical($ticketType, excludeUserId: $seller->id);

        if ($max !== null && $data['allocated'] > $max) {
            return back()->withInput()->with(
                'error',
                "Le stock en ligne de « {$ticketType->name} » ne permet pas d'attribuer {$data['allocated']} ticket(s) en physique : il en reste {$max}."
            );
        }

        StockAllocation::updateOrCreate(
            ['ticket_type_id' => $ticketType->id, 'user_id' => $seller->id],
            ['allocated' => $data['allocated']]
        );

        return back()->with('success', "Stock attribué : {$data['allocated']} ticket(s) pour {$seller->full_name}.");
    }

    public function update(Request $request, StockAllocation $allocation): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'allocated' => ['required', 'integer', 'min:0', 'max:1000000'],
        ], [], ['allocated' => 'quantité attribuée']);

        $this->authorizeOwner($user, $allocation->ticketType?->event?->organizer_id, $allocation->user?->organizer_id);

        if ($data['allocated'] < $allocation->sold) {
            return back()->withInput()->with(
                'error',
                "Impossible : {$allocation->sold} ticket(s) ont déjà été vendus sur ce stock."
            );
        }

        $max = $this->maxPhysical($allocation->ticketType, excludeAllocationId: $allocation->id);

        if ($max !== null && $data['allocated'] > $max) {
            return back()->withInput()->with(
                'error',
                "Le stock en ligne de « {$allocation->ticketType->name} » ne permet pas d'attribuer {$data['allocated']} ticket(s) en physique : il en reste {$max}."
            );
        }

        $allocation->update(['allocated' => $data['allocated']]);

        return back()->with('success', 'Stock mis à jour.');
    }

    public function destroy(Request $request, StockAllocation $allocation): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeOwner($user, $allocation->ticketType?->event?->organizer_id, $allocation->user?->organizer_id);

        if ($allocation->sold > 0) {
            return back()->with('error', 'Impossible de retirer : des ventes ont déjà été déclarées sur ce stock.');
        }

        DB::transaction(fn () => $allocation->delete());

        return back()->with('success', 'Attribution supprimée.');
    }

    private function authorizeOwner(User $user, ?int $eventOwnerId, ?int $sellerOwnerId): void
    {
        abort_unless($user->isAdmin() || $eventOwnerId === $user->id, 403);

        abort_unless($user->isAdmin() || $sellerOwnerId === $user->id || $sellerOwnerId === null, 403);
    }

    /**
     * Stock physique maximal attribuable : stock total du type (quantity) moins
     * les ventes en ligne et les attributions des autres points de vente.
     * `null` pour un stock illimité.
     */
    private function maxPhysical(TicketType $ticketType, ?int $excludeUserId = null, ?int $excludeAllocationId = null): ?int
    {
        if ($ticketType->quantity === null) {
            return null;
        }

        $query = $ticketType->stockAllocations()
            ->when($excludeUserId !== null, fn ($q) => $q->where('user_id', '!=', $excludeUserId))
            ->when($excludeAllocationId !== null, fn ($q) => $q->where('id', '!=', $excludeAllocationId));

        $otherAllocated = (int) $query->sum('allocated');

        return max(0, $ticketType->quantity - $ticketType->sold_online - $otherAllocated);
    }
}
