<?php

namespace Tests\Feature;

use App\Models\Payout;
use App\Models\User;
use App\Services\DexPay\DexPayAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Régression : l'API DexPay renvoie l'objet payout à la racine (pas toujours
 * sous « data » / « payout »). Un parsing trop restrictif laissait dexpay_id,
 * fees et status vides : les retraits restaient « En attente » malgré le
 * webhook payout.completed.
 */
class PayoutReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private function payout(User $organizer, string $reference = 'PO-BEX-TESTREF'): Payout
    {
        return Payout::create([
            'organizer_id' => $organizer->id,
            'reference' => $reference,
            'amount' => 250,
            'currency' => 'XOF',
            'fees' => 0,
            'destination_phone' => '772607977',
            'operator' => 'wave_sn_payout',
            'country_iso' => 'SN',
            'status' => 'PENDING',
        ]);
    }

    public function test_create_payout_parses_a_flat_root_response(): void
    {
        Http::fake([
            'api-sandbox.dexpay.africa/api/v1/payouts' => Http::response([
                'id' => '6ac91e8e1b1d82d1274a747e',
                'reference' => 'PO_20261009_DD4C34',
                'amount' => 250,
                'fees' => 4,
                'status' => 'COMPLETED',
                'provider_tx_id' => 'txn_flat',
            ], 201),
        ]);

        $remote = app(DexPayAccount::class)->createPayout(250, '+221772607977', 'wave_sn_payout', 'Abdoul');

        $this->assertSame('6ac91e8e1b1d82d1274a747e', $remote['id']);
        $this->assertSame(4, $remote['fees']);
        $this->assertSame('COMPLETED', $remote['status']);
    }

    public function test_get_payout_parses_a_flat_root_response(): void
    {
        Http::fake([
            'api-sandbox.dexpay.africa/api/v1/payouts/*' => Http::response([
                'id' => '6ac91e8e1b1d82d1274a747e',
                'status' => 'COMPLETED',
                'fee_amount' => 4,
                'provider_tx_id' => 'txn_flat',
            ]),
        ]);

        $remote = app(DexPayAccount::class)->getPayout('6ac91e8e1b1d82d1274a747e');

        $this->assertSame('COMPLETED', $remote['status']);
        $this->assertSame(4, $remote['fee_amount']);
    }

    public function test_payout_webhook_matches_by_merchant_reference_when_dexpay_id_is_unknown(): void
    {
        $organizer = User::factory()->organizer()->create();
        $payout = $this->payout($organizer);

        $body = json_encode([
            'event' => 'payout.completed',
            'reference' => 'PO_20261009_DD4C34',
            'payout_id' => '6ac91e8e1b1d82d1274a747e',
            'merchant_reference' => null,
            'status' => 'completed',
            'amount' => 250,
            'currency' => 'XOF',
            'fee_amount' => 4,
            'provider_tx_id' => '443a230c-12ad-4d98-9f50-2248eb329938',
            'metadata' => ['merchant_reference' => $payout->reference],
        ]);

        $signature = hash_hmac('sha256', $body, config('dexpay.api_secret'));

        $this->call('POST', route('webhooks.dexpay'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $body)->assertOk();

        $payout->refresh();

        $this->assertSame('COMPLETED', $payout->status);
        $this->assertSame(4, $payout->fees);
        $this->assertSame('6ac91e8e1b1d82d1274a747e', $payout->dexpay_id);
        $this->assertSame('443a230c-12ad-4d98-9f50-2248eb329938', $payout->provider_tx_id);
    }

    public function test_manual_sync_resolves_a_missing_dexpay_id_via_list_payouts(): void
    {
        $organizer = User::factory()->organizer()->create();
        $payout = $this->payout($organizer);

        Http::fake(function ($request) use ($payout) {
            if (str_contains($request->url(), '/payouts/dexpayout_1')) {
                return Http::response([
                    'id' => 'dexpayout_1',
                    'status' => 'COMPLETED',
                    'fee_amount' => 4,
                    'provider_tx_id' => 'txn_sync',
                ]);
            }

            if (str_contains($request->url(), '/payouts')) {
                return Http::response([
                    'data' => [[
                        'id' => 'dexpayout_1',
                        'reference' => 'PO_20261009_DD4C34',
                        'status' => 'COMPLETED',
                        'metadata' => ['merchant_reference' => $payout->reference],
                    ]],
                    'hasNextPage' => false,
                    'totalCount' => 1,
                ]);
            }

            return Http::response([], 404);
        });

        $this->actingAs($organizer)
            ->post(route('organizer.payouts.sync', $payout))
            ->assertRedirect();

        $payout->refresh();

        $this->assertSame('COMPLETED', $payout->status);
        $this->assertSame(4, $payout->fees);
        $this->assertSame('dexpayout_1', $payout->dexpay_id);
        $this->assertSame('txn_sync', $payout->provider_tx_id);
    }
}
