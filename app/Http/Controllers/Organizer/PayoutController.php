<?php

namespace App\Http\Controllers\Organizer;

use App\Exceptions\DexPayException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payout;
use App\Services\DexPay\DexPayAccount;
use App\Services\StatsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayoutController extends Controller
{
    public function __construct(
        private readonly DexPayAccount $account,
        private readonly StatsService $stats,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $payouts = Payout::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->latest()
            ->paginate(15);

        $summaryRows = Payout::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->get(['amount', 'fees']);

        $summary = [
            'count' => $summaryRows->count(),
            'total_amount' => (int) $summaryRows->sum('amount'),
            'total_fees' => (int) $summaryRows->sum('fees'),
        ];

        // Revenus de l'organisateur = total des ventes payées de ses événements
        // (tous les événements pour un administrateur).
        $organizerEventIds = $user->isAdmin()
            ? null
            : Event::where('organizer_id', $user->id)->pluck('id')->all();
        $organizerRevenue = $this->stats->summary(null, null, $organizerEventIds)['total_revenue'];

        $configured = $this->account->client()->isConfigured();

        $providers = $configured ? collect($this->account->payoutProviders())->map(fn (array $p) => [
            // DexPay attend `provider_short_name` (ex : wave_sn) comme opérateur,
            // et non le nom commercial (ex : « Wave SN »).
            'code' => $p['provider_short_name'] ?? null,
            'name' => $p['provider_name'] ?? null,
        ])->filter(fn (array $p) => $p['code'] !== null && $p['name'] !== null)->values()->all() : [];
        $balance = null;
        $balanceError = null;

        if ($configured) {
            try {
                $balance = $this->account->balanceSummary();
            } catch (\Throwable $e) {
                $balanceError = $e->getMessage();
            }
        }

        return view('organizer.payouts.index', [
            'payouts' => $payouts,
            'summary' => $summary,
            'providers' => $providers,
            'balance' => $balance,
            'balanceError' => $balanceError,
            'organizerRevenue' => $organizerRevenue,
            'sandbox' => $this->account->client()->isSandbox(),
            'configured' => $configured,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:250', 'max:100000000'],
            'destination_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\s\-()]+$/'],
            'operator' => ['required', 'string', 'max:40'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
        ], [], [
            'amount' => 'montant',
            'destination_phone' => 'numéro de téléphone',
            'operator' => 'opérateur',
        ]);

        $phone = $this->normalizePhone($data['destination_phone']);

        if (! preg_match('/^\+?[0-9]{9,15}$/', $phone)) {
            return back()->withInput()->with('error', 'Numéro de téléphone invalide (ex : +221771234567).');
        }

        // Le Sénégal uniquement : on refuse tout opérateur absent de la liste du pays.
        $operators = collect($this->account->payoutProviders())
            ->map(fn (array $p) => $p['provider_short_name'] ?? null)
            ->filter()
            ->values()
            ->all();

        if ($operators !== [] && ! in_array($data['operator'], $operators, true)) {
            return back()->withInput()->with('error', 'Opérateur invalide : choisissez un opérateur du Sénégal dans la liste.');
        }

        $reference = 'PO-BEX-'.strtoupper(Str::random(10));

        try {
            $remote = $this->account->createPayout(
                amount: (int) $data['amount'],
                destinationPhone: $phone,
                operator: $data['operator'],
                recipientName: $data['recipient_name'] ?? null,
                webhookUrl: route('webhooks.dexpay'),
                metadata: ['organizer_id' => (string) $user->id, 'merchant_reference' => $reference],
            );
        } catch (DexPayException $e) {
            return back()->withInput()->with('error', 'Retrait refusé : '.$e->getMessage());
        }

        Payout::create([
            'organizer_id' => $user->id,
            'reference' => $reference,
            'dexpay_id' => $remote['id'] ?? null,
            'amount' => (int) $data['amount'],
            'currency' => config('dexpay.currency', 'XOF'),
            'fees' => (int) ($remote['fees'] ?? $remote['fee_amount'] ?? 0),
            'destination_phone' => $phone,
            'recipient_name' => $data['recipient_name'] ?? null,
            'operator' => $data['operator'],
            'country_iso' => config('dexpay.country_iso', 'SN'),
            'status' => $remote['status'] ?? 'PENDING',
            'provider_name' => $remote['provider_name'] ?? null,
            'provider_tx_id' => $remote['provider_tx_id'] ?? null,
        ]);

        return back()->with('success', 'Demande de retrait envoyée. Vous serez notifié par webhook.');
    }

    /** Resynchronise un payout avec DexPay. */
    public function sync(Request $request, Payout $payout): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || $payout->organizer_id === $user->id, 403);

        try {
            $this->applyRemoteStatus($payout);
        } catch (DexPayException $e) {
            return back()->with('error', 'Synchronisation impossible : '.$e->getMessage());
        }

        return back()->with('success', 'Statut du retrait actualisé.');
    }

    /**
     * Met à jour le payout depuis DexPay. Si l'identifiant distant manque
     * (retraits créés avant correctif), on le retrouve par la référence métier.
     */
    private function applyRemoteStatus(Payout $payout): void
    {
        if (! $payout->dexpay_id) {
            $remote = $this->findRemotePayout($payout->reference);

            if (! $remote) {
                throw new DexPayException('Retrait introuvable chez DexPay.');
            }

            $payout->update(['dexpay_id' => $remote['id'] ?? $remote['payout_id'] ?? null]);
        }

        if (! $payout->dexpay_id) {
            throw new DexPayException('Identifiant DexPay introuvable.');
        }

        $remote = $this->account->getPayout($payout->dexpay_id);

        $payout->update([
            'status' => $remote['status'] ?? $payout->status,
            'fees' => (int) ($remote['fee_amount'] ?? $remote['fees'] ?? $payout->fees),
            'provider_tx_id' => $remote['provider_tx_id'] ?? $payout->provider_tx_id,
            'failure_reason' => $remote['failure_reason'] ?? null,
            'completed_at' => $remote['completed_at'] ?? $payout->completed_at,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findRemotePayout(string $reference): ?array
    {
        $list = $this->account->listPayouts([], 1, 50);

        foreach ($list['data'] as $item) {
            if (($item['metadata']['merchant_reference'] ?? null) === $reference) {
                return $item;
            }
        }

        return null;
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[\s\-().]/', '', trim($phone)) ?? '';

        return str_starts_with($clean, '00') ? '+'.substr($clean, 2) : $clean;
    }
}
