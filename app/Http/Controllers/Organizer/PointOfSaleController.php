<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Points de vente de l'organisateur : liste et détail des ventes au comptoir.
 */
class PointOfSaleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $pointsOfSale = User::query()
            ->where('role', User::ROLE_USER)
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->withSum('posSales as tickets_sold_sum', 'quantity')
            ->withSum('posSales as revenue_sum', 'total_amount')
            ->withCount('posSales as operations_count')
            ->orderBy('first_name')
            ->get();

        return view('organizer.pos.index', ['pointsOfSale' => $pointsOfSale]);
    }

    public function show(Request $request, User $pointOfSale): View
    {
        $user = $request->user();

        abort_unless($pointOfSale->isPointOfSale(), 404);
        abort_unless(
            $user->isAdmin() || $pointOfSale->organizer_id === $user->id,
            404
        );

        $sales = $pointOfSale->posSales()
            ->with('ticketType.event')
            ->latest()
            ->paginate(15);

        return view('organizer.pos.show', [
            'pointOfSale' => $pointOfSale,
            'sales' => $sales,
            'stats' => [
                'tickets' => (int) $pointOfSale->posSales()->sum('quantity'),
                'revenue' => (int) $pointOfSale->posSales()->sum('total_amount'),
                'operations' => $pointOfSale->posSales()->count(),
            ],
        ]);
    }
}
