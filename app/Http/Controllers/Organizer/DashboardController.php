<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\DexPay\DexPayAccount;
use App\Services\StatsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StatsService $stats,
        private readonly DexPayAccount $account,
    ) {}

    /** Tableau de bord Billeterie Express + filtre de période. */
    public function index(Request $request): View
    {
        $user = $request->user();

        $eventIds = $user->isAdmin()
            ? null
            : Event::where('organizer_id', $user->id)->pluck('id')->all();

        [$from, $to, $preset] = $this->period($request);

        $balance = null;
        $balanceError = null;

        if ($user->isOrganizer() || $user->isAdmin()) {
            try {
                if ($this->account->client()->isConfigured()) {
                    $balance = $this->account->balanceSummary();
                }
            } catch (\Throwable $e) {
                $balanceError = 'Solde DexPay indisponible : '.$e->getMessage();
            }
        }

        return view('organizer.dashboard', [
            'summary' => $this->stats->summary($from, $to, $eventIds),
            'evolution' => $this->stats->salesEvolution($from, $to, $eventIds),
            'topEvents' => $this->stats->topEvents($from, $to, $eventIds),
            'revenueByEvent' => $this->stats->revenueByEvent($from, $to, $eventIds),
            'ticketsByEvent' => $this->stats->ticketsByEvent($from, $to, $eventIds),
            'from' => $from,
            'to' => $to,
            'preset' => $preset,
            'balance' => $balance,
            'balanceError' => $balanceError,
        ]);
    }

    /** @var array<int, string> */
    private const PRESETS = ['today', 'yesterday', 'week', 'month', '7', '30', '90', 'year', 'all'];

    /** @return array{0: ?Carbon, 1: ?Carbon, 2: string} */
    private function period(Request $request): array
    {
        $preset = (string) $request->query('period', '');

        // Un preset valide prime ; les dates explicites ne s'appliquent qu'en
        // l'absence de preset (le formulaire « dates personnalisées » ne
        // transmet que from/to).
        if (! in_array($preset, self::PRESETS, true)) {
            if ($request->filled('from') || $request->filled('to')) {
                return [
                    $request->date('from')?->startOfDay(),
                    $request->date('to')?->endOfDay(),
                    'custom',
                ];
            }

            // Par défaut : aucune restriction de période (« Tout »).
            $preset = 'all';
        }

        return match ($preset) {
            'today' => [now()->startOfDay(), now()->endOfDay(), 'today'],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), 'yesterday'],
            'week' => [now()->startOfWeek(), now()->endOfWeek(), 'week'],
            'month' => [now()->startOfMonth(), now()->endOfMonth(), 'month'],
            '30' => [now()->subDays(29)->startOfDay(), now()->endOfDay(), '30'],
            '90' => [now()->subDays(89)->startOfDay(), now()->endOfDay(), '90'],
            'year' => [now()->startOfYear(), now()->endOfDay(), 'year'],
            'all' => [null, null, 'all'],
            default => [now()->subDays(6)->startOfDay(), now()->endOfDay(), '7'],
        };
    }
}
