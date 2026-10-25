<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\PosSale;
use App\Models\Reservation;
use App\Models\StockAllocation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Espace point de vente : suivi du stock physique et déclaration des ventes.
 */
class PointOfSaleController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $allocations = StockAllocation::query()
            ->with(['ticketType.event'])
            ->where('user_id', $user->id)
            ->get();

        return view('pos.dashboard', [
            'allocations' => $allocations,
            'sales' => $user->posSales()->with('ticketType.event')->latest()->paginate(10),
            'totals' => [
                'allocated' => (int) $allocations->sum('allocated'),
                'sold' => (int) $allocations->sum('sold'),
                'remaining' => (int) $allocations->sum(fn ($a) => $a->remaining),
                'revenue' => (int) $user->posSales()->sum('total_amount'),
                'sales_count' => $user->posSales()->count(),
            ],
        ]);
    }

    /** Déclaration d'une vente de tickets physiques. */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'stock_allocation_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'buyer_name' => ['required', 'string', 'max:120'],
            'buyer_phone' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], [
            'quantity' => 'quantité',
            'buyer_name' => 'nom de l\'acheteur',
        ]);

        abort_unless(
            StockAllocation::where('user_id', $user->id)->whereKey($data['stock_allocation_id'])->exists(),
            404
        );

        try {
            $reservation = DB::transaction(function () use ($user, $data) {
                // Verrou pessimiste : deux pointants ne peuvent pas vendre le
                // dernier ticket en même temps.
                $allocation = StockAllocation::with('ticketType.event')
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->findOrFail($data['stock_allocation_id']);

                if ($allocation->remaining < $data['quantity']) {
                    throw new InsufficientStockException($allocation->remaining);
                }

                $ticketType = $allocation->ticketType;
                $unitPrice = $ticketType->price;
                $total = $unitPrice * $data['quantity'];

                $allocation->increment('sold', $data['quantity']);
                $ticketType->increment('sold_physical', $data['quantity']);

                $reservation = Reservation::create([
                    'reference' => Reservation::makeReference(),
                    'event_id' => $ticketType->event_id,
                    'ticket_type_id' => $ticketType->id,
                    'user_id' => $user->id,
                    'customer_name' => $data['buyer_name'],
                    'customer_email' => null,
                    'customer_phone' => $data['buyer_phone'] ?? null,
                    'quantity' => $data['quantity'],
                    'unit_price' => $unitPrice,
                    'total_amount' => $total,
                    'payment_status' => Reservation::STATUS_PAID,
                    'channel' => Reservation::CHANNEL_PHYSICAL,
                    'sold_by' => $user->id,
                    'paid_at' => now(),
                ]);

                PosSale::create([
                    'stock_allocation_id' => $allocation->id,
                    'ticket_type_id' => $ticketType->id,
                    'user_id' => $user->id,
                    'quantity' => $data['quantity'],
                    'unit_price' => $unitPrice,
                    'total_amount' => $total,
                    'buyer_name' => $data['buyer_name'],
                    'buyer_phone' => $data['buyer_phone'] ?? null,
                    'buyer_email' => null,
                    'note' => $data['note'] ?? null,
                ]);

                return $reservation;
            });
        } catch (InsufficientStockException $e) {
            return back()->withInput()->with(
                'error',
                "Stock insuffisant : {$e->remaining} ticket(s) restant(s)."
            );
        }

        return back()->with('success', "{$data['quantity']} ticket(s) enregistré(s).")
            ->with('tickets_reference', $reservation->reference);
    }
}
