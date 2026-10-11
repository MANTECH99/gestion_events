<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Services\DexPay\DexPayAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * L'API DexPay sandbox ignore `filters[provider_country]` et renvoie les
 * providers de tous les pays : la page de paiement ne doit proposer que ceux
 * du pays configuré, et le choix doit être exploité par l'API.
 */
class PaymentOperatorSelectionTest extends TestCase
{
    use RefreshDatabase;

    /** Réponse réelle de GET /payment-providers (sandbox), avant filtrage applicatif. */
    private const PROVIDERS = [
        ['provider_name' => 'Wave', 'provider_short_name' => 'wave_sn', 'provider_country' => 'SN', 'provider_currency' => 'XOF', 'provider_type' => 'mobile_money', 'provider_status' => 'active', 'provider_fee' => 1.5, 'provider_fee_type' => 'percentage'],
        ['provider_name' => 'Orange Money', 'provider_short_name' => 'om_sn', 'provider_country' => 'SN', 'provider_currency' => 'XOF', 'provider_type' => 'mobile_money', 'provider_status' => 'active'],
        ['provider_name' => 'Orange Money', 'provider_short_name' => 'om_ci', 'provider_country' => 'CI', 'provider_currency' => 'XOF', 'provider_type' => 'mobile_money', 'provider_status' => 'active'],
        ['provider_name' => 'MTN', 'provider_short_name' => 'mtn_ci', 'provider_country' => 'CI', 'provider_currency' => 'XOF', 'provider_type' => 'mobile_money', 'provider_status' => 'active'],
        ['provider_name' => 'Orange Money', 'provider_short_name' => 'om_cm', 'provider_country' => 'CM', 'provider_currency' => 'XAF', 'provider_type' => 'mobile_money', 'provider_status' => 'active'],
        ['provider_name' => 'MTN', 'provider_short_name' => 'mtn_cm', 'provider_country' => 'CM', 'provider_currency' => 'XAF', 'provider_type' => 'mobile_money', 'provider_status' => 'active'],
        ['provider_name' => 'Carte Bancaire', 'provider_short_name' => 'card_gn', 'provider_country' => 'GLOBAL', 'provider_currency' => 'GNF', 'provider_type' => 'card', 'provider_status' => 'active'],
        ['provider_name' => 'Mixx By Yas', 'provider_short_name' => 'mixx_sn', 'provider_country' => 'SN', 'provider_currency' => 'XOF', 'provider_type' => 'mobile_money', 'provider_status' => 'active'],
    ];

    /** @var array<int, array<string, mixed>> */
    private array $providers = self::PROVIDERS;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dexpay.api_key' => 'pk_test_unitaires',
            'dexpay.api_secret' => 'sk_test_unitaires',
            'dexpay.country_iso' => 'SN',
            'dexpay.currency' => 'XOF',
        ]);

        $this->providers = self::PROVIDERS;
    }

    /**
     * Enregistre UN seul jeu de stubs : /payment-providers plus les endpoints
     * de checkout demandés par le test, et vide le cache applicatif.
     */
    private function fakeDexPay(array $checkoutStubs = []): void
    {
        Http::fake(array_merge([
            'api-sandbox.dexpay.africa/api/v1/payment-providers*' => Http::response([
                'data' => $this->providers,
            ]),
        ], $checkoutStubs));

        Cache::flush();
    }

    /** Réservation en attente avec droit d'accès dans la session du test. */
    private function reservation(): Reservation
    {
        $organizer = User::factory()->organizer()->create();

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Test Paiement Opérateur',
            'slug' => Event::slugFor('test-paiement-operateur-'.uniqid()),
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 200,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);

        $type = $event->ticketTypes()->create([
            'name' => 'Standard',
            'price' => 5000,
            'quantity' => 100,
            'is_active' => true,
        ]);

        $reservation = Reservation::create([
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
            'reference' => 'BEX-TEST-'.strtoupper(substr(md5(uniqid('', true)), 0, 6)),
            'customer_name' => 'Client Test',
            'customer_email' => 'client@test.sn',
            'customer_phone' => '+221771234567',
            'quantity' => 1,
            'unit_price' => 5000,
            'total_amount' => 5000,
            'payment_status' => Reservation::STATUS_PENDING,
            'channel' => 'online',
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->withSession(['reservation_access.'.$reservation->id => true]);

        return $reservation;
    }

    public function test_only_providers_of_the_configured_country_are_kept(): void
    {
        $this->fakeDexPay();

        $operators = app(DexPayAccount::class)->collectOperators();

        $this->assertSame(['wave_sn', 'om_sn', 'mixx_sn'], array_column($operators, 'code'));

        foreach ($operators as $operator) {
            $this->assertSame('SN', $operator['country']);
            $this->assertSame('XOF', $operator['currency']);
        }
    }

    public function test_operators_are_not_duplicated(): void
    {
        $this->fakeDexPay();

        $codes = array_column(app(DexPayAccount::class)->collectOperators(), 'code');

        $this->assertSame(array_unique($codes), $codes);
    }

    public function test_card_providers_are_flagged_as_card(): void
    {
        $this->providers = [
            ['provider_name' => 'Wave', 'provider_short_name' => 'wave_sn', 'provider_country' => 'SN', 'provider_type' => 'mobile_money'],
            ['provider_name' => 'Carte', 'provider_short_name' => 'card_sn', 'provider_country' => 'SN', 'provider_type' => 'card'],
        ];

        $this->fakeDexPay();

        $operators = collect(app(DexPayAccount::class)->collectOperators())->keyBy('code');

        $this->assertSame('mobile_money', $operators['wave_sn']['payment_method']);
        $this->assertSame('card', $operators['card_sn']['payment_method']);
    }

    public function test_the_country_filter_follows_the_configuration(): void
    {
        config(['dexpay.country_iso' => 'CI']);
        $this->fakeDexPay();

        $this->assertSame(
            ['om_ci', 'mtn_ci'],
            array_column(app(DexPayAccount::class)->collectOperators(), 'code')
        );
    }

    public function test_find_operator_rejects_an_unknown_code(): void
    {
        $this->fakeDexPay();

        $account = app(DexPayAccount::class);

        $this->assertNull($account->findOperator('om_ci'));
        $this->assertNull($account->findOperator('wave'));
        $this->assertNull($account->findOperator(''));
        $this->assertSame('wave_sn', $account->findOperator('wave_sn')['code']);
    }

    public function test_payment_page_renders_one_radio_per_local_operator(): void
    {
        $this->fakeDexPay();

        $reservation = $this->reservation();

        $html = $this->get(route('payment.show', $reservation->reference))
            ->assertOk()
            ->getContent();

        $this->assertSame(3, substr_count($html, 'type="radio"'));
        $this->assertStringContainsString('value="wave_sn"', $html);
        $this->assertStringContainsString('value="om_sn"', $html);
        $this->assertStringContainsString('value="mixx_sn"', $html);

        // Les providers des autres pays ne doivent jamais apparaître.
        foreach (['om_ci', 'mtn_ci', 'om_cm', 'mtn_cm', 'card_gn'] as $foreign) {
            $this->assertStringNotContainsString('value="'.$foreign.'"', $html);
        }

        $this->assertStringNotContainsString('Carte Bancaire', $html);
        $this->assertStringNotContainsString('MTN', $html);
        $this->assertSame(3, substr_count($html, 'Mobile Money'));
    }

    public function test_payment_page_preselects_the_first_local_operator_and_the_amount(): void
    {
        $this->fakeDexPay();

        $reservation = $this->reservation();

        $html = $this->get(route('payment.show', $reservation->reference))->getContent();

        // @js échappe les guillemets en \u0022 pour l'attribut HTML.
        $this->assertStringContainsString('operator\u0022:\u0022wave_sn', $html);
        // Le premier opérateur (wave_sn) applique 1,5 % : 5 000 + 75 (service)
        // + 75 (frais DexPay d'encaissement, même taux) = 5 150.
        $this->assertStringContainsString('amountLabel\u0022:\u00225 150 FCFA', $html);
        $this->assertStringContainsString('phone\u0022:\u0022+221771234567', $html);
    }

    public function test_checkout_session_marks_the_customer_as_supporting_the_fee(): void
    {
        config(['dexpay.client_support_fee' => true]);

        $reservation = $this->reservation();

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*/transaction-attempt' => Http::response([
                'data' => [
                    'id' => 'attempt_1',
                    'transaction_id' => 'tx_1',
                    'status' => 'processing',
                    'operator' => 'wave_sn',
                    'cashout_url' => 'https://pay.wave.com/m/abc123',
                ],
            ], 201),
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => [
                    'reference' => $reservation->reference,
                    'status' => 'initiated',
                    'amount' => $reservation->total_amount,
                    'currency' => 'XOF',
                    'payment_url' => 'https://pay.dexpay.africa/'.$reservation->reference,
                ],
            ], 201),
        ]);

        $this->postJson(route('payment.start', $reservation->reference), [
            'customer_phone' => '+221771234567',
            'operator' => 'wave_sn',
        ])->assertOk();

        // DexPay n'honore que « client_support_fee » (singulier) : toute autre
        // orthographe est ignorée et le marchand absorbe alors les frais.
        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/checkout-sessions')
                && $request->method() === 'POST'
                && $request['client_support_fee'] === true
                && ! array_key_exists('client_supports_fee', $request->data());
        });
    }

    public function test_starting_a_payment_with_an_unknown_operator_is_rejected(): void
    {
        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => ['status' => 'initiated'],
            ], 201),
        ]);

        $reservation = $this->reservation();

        $this->postJson(route('payment.start', $reservation->reference), [
            'customer_phone' => '+221771234567',
            'operator' => 'om_cm',
        ])->assertStatus(422)->assertJsonPath('ok', false);

        // Aucune session ne doit avoir été créée.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/checkout-sessions')
            && $request->method() === 'POST');
    }

    public function test_starting_a_payment_creates_a_session_and_an_attempt_for_the_chosen_operator(): void
    {
        $reservation = $this->reservation();

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*/transaction-attempt' => Http::response([
                'data' => [
                    'id' => 'attempt_1',
                    'transaction_id' => 'tx_1',
                    'status' => 'processing',
                    'operator' => 'wave_sn',
                    'cashout_url' => 'https://pay.wave.com/m/abc123',
                ],
            ], 201),
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => [
                    'id' => 'session_1',
                    'reference' => $reservation->reference,
                    'status' => 'initiated',
                    'amount' => $reservation->total_amount,
                    'currency' => 'XOF',
                    'payment_url' => 'https://pay.dexpay.africa/'.$reservation->reference,
                    'sandbox_payment_url' => 'https://api-sandbox.dexpay.africa/api/v1/checkout-sessions/sandbox/'.$reservation->reference,
                ],
            ], 201),
        ]);

        $this->postJson(route('payment.start', $reservation->reference), [
            'customer_phone' => '+221771234567',
            'operator' => 'wave_sn',
        ])->assertOk()->assertJsonPath('payment_url', 'https://pay.wave.com/m/abc123');

        $reservation->refresh();

        $this->assertSame('wave_sn', $reservation->payment_operator);
        $this->assertSame('wave_sn', $reservation->latestPayment->operator);
        $this->assertSame('tx_1', $reservation->latestPayment->transaction_id);

        // L'opérateur est transmis tel quel à l'API (provider_short_name).
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/transaction-attempt')
                && $request['operator'] === 'wave_sn'
                && $request['payment_method'] === 'mobile_money'
                && $request['countryISO'] === 'SN'
                && $request['customer']['phone'] === '+221771234567'
                && $request['customer']['email'] === 'client@test.sn';
        });
    }

    public function test_the_attempts_alias_is_used_when_transaction_attempt_is_missing(): void
    {
        $reservation = $this->reservation();

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*/transaction-attempt' => Http::response([
                'message' => 'Not found',
            ], 404),
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*/attempts' => Http::response([
                'data' => ['id' => 'attempt_1', 'status' => 'processing', 'operator' => 'om_sn'],
            ], 201),
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => [
                    'id' => 'session_1',
                    'reference' => $reservation->reference,
                    'status' => 'initiated',
                    'payment_url' => 'https://pay.dexpay.africa/'.$reservation->reference,
                ],
            ], 201),
        ]);

        $this->postJson(route('payment.start', $reservation->reference), [
            'customer_phone' => '+221771234567',
            'operator' => 'om_sn',
        ])->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/attempts')
            && $request['operator'] === 'om_sn');
    }

    public function test_payment_still_starts_when_the_attempt_endpoint_fails(): void
    {
        $reservation = $this->reservation();
        $hostedUrl = 'https://pay.dexpay.africa/'.$reservation->reference;

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*/transaction-attempt' => Http::response([
                'message' => 'Operator unavailable',
            ], 400),
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => [
                    'id' => 'session_1',
                    'reference' => $reservation->reference,
                    'status' => 'initiated',
                    'payment_url' => $hostedUrl,
                ],
            ], 201),
        ]);

        // Repli sur la page hébergée DexPay plutôt qu'un échec de paiement.
        $this->postJson(route('payment.start', $reservation->reference), [
            'customer_phone' => '+221771234567',
            'operator' => 'om_sn',
        ])->assertOk()->assertJsonPath('payment_url', $hostedUrl);

        $this->assertSame('om_sn', $reservation->fresh()->payment_operator);
    }

    public function test_payment_page_displays_the_service_fee_of_the_preselected_operator(): void
    {
        $this->fakeDexPay();

        $reservation = $this->reservation();

        $html = $this->get(route('payment.show', $reservation->reference))->getContent();

        // wave_sn (1,5 % sur 5 000) : les frais (service + encaissement) sont
        // réunis sur une seule ligne « Frais DexPay », mise à jour par le JS.
        $this->assertStringContainsString('Frais DexPay', $html);
        $this->assertStringContainsString('encaissement, prélevés au paiement', $html);
        $this->assertStringContainsString('x-show="totalFee > 0"', $html);
        $this->assertStringContainsString('\u0022fee\u0022:1.5', $html);

        // om_sn n'a pas de frais : la ligne est masquée tant que le total = 0.
        $this->assertStringNotContainsString('Frais de service', $html);
        $this->assertStringNotContainsString('couverture des frais de retrait', $html);
    }

    public function test_service_fee_helper_uses_the_operator_fee_when_known(): void
    {
        $this->assertSame(75, dexpay_service_fee(5000, ['fee' => 1.5, 'fee_type' => 'percentage']));
        $this->assertSame(0, dexpay_service_fee(5000, ['fee' => null]));
        $this->assertSame(50, dexpay_service_fee(5000, ['fee' => '1', 'fee_type' => 'percentage']));
        $this->assertSame(250, dexpay_service_fee(5000, ['fee' => 250, 'fee_type' => 'fixed']));

        // Sans opérateur, repli sur le taux configuré.
        config(['dexpay.service_fee_rate' => 2]);
        $this->assertSame(100, dexpay_service_fee(5000, null));
        $this->assertSame(0, dexpay_service_fee(0, ['fee' => 1.5, 'fee_type' => 'percentage']));
    }

    public function test_the_checkout_session_bills_the_amount_with_the_operator_fee_markup(): void
    {
        $reservation = $this->reservation();

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*/transaction-attempt' => Http::response([
                'data' => ['id' => 'attempt_1', 'status' => 'processing', 'operator' => 'wave_sn'],
            ], 201),
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => [
                    'id' => 'session_1',
                    'reference' => $reservation->reference,
                    'status' => 'initiated',
                    'amount' => 5075,
                    'currency' => 'XOF',
                    'payment_url' => 'https://pay.dexpay.africa/'.$reservation->reference,
                ],
            ], 201),
        ]);

        $this->postJson(route('payment.start', $reservation->reference), [
            'customer_phone' => '+221771234567',
            'operator' => 'wave_sn',
        ])->assertOk();

        // Le montant facturé = 5 000 (billets) + 75 (1,5 % Wave).
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/checkout-sessions')
            && $request->method() === 'POST'
            && (int) $request['amount'] === 5075);

        $this->assertSame(5075, $reservation->fresh()->latestPayment->amount);
        $this->assertSame(75, (int) $reservation->fresh()->latestPayment->payload['service_fee']);
    }

    public function test_no_service_fee_without_operator_fee(): void
    {
        $reservation = $this->reservation();

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => [
                    'id' => 'session_1',
                    'reference' => $reservation->reference,
                    'status' => 'initiated',
                    'amount' => 5000,
                    'payment_url' => 'https://pay.dexpay.africa/'.$reservation->reference,
                ],
            ], 201),
        ]);

        // om_sn ne transporte aucun provider_fee : le montant reste le total.
        $this->postJson(route('payment.start', $reservation->reference), [
            'customer_phone' => '+221771234567',
            'operator' => 'om_sn',
        ])->assertOk();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/checkout-sessions')
            && $request->method() === 'POST'
            && (int) $request['amount'] === 5000);
    }

    public function test_payment_operator_column_never_receives_the_phone_number(): void
    {
        $reservation = $this->reservation();

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => [
                    'id' => 'session_1',
                    'reference' => $reservation->reference,
                    'status' => 'initiated',
                    'payment_url' => 'https://pay.dexpay.africa/'.$reservation->reference,
                ],
            ], 201),
        ]);

        // Sans opérateur choisi : le téléphone ne doit pas finir dans payment_operator.
        $this->postJson(route('payment.start', $reservation->reference), [
            'customer_phone' => '+221771234567',
        ])->assertOk();

        $this->assertNull($reservation->fresh()->payment_operator);
    }
}
