<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Fusion réservation + paiement : le formulaire /reserver crée la réservation
 * ET démarre la checkout DexPay, puis redirige directement vers la page hébergée.
 * Il n'y a plus d'étape intermédiaire /paiement/{reference} dans le parcours nominal.
 */
class BookingPaymentMergeTest extends TestCase
{
    use RefreshDatabase;

    /** Réponse réelle de GET /payment-providers (sandbox), avant filtrage applicatif. */
    private const PROVIDERS = [
        ['provider_name' => 'Wave', 'provider_short_name' => 'wave_sn', 'provider_country' => 'SN', 'provider_currency' => 'XOF', 'provider_type' => 'mobile_money', 'provider_status' => 'active', 'provider_fee' => 1.5, 'provider_fee_type' => 'percentage'],
        ['provider_name' => 'Orange Money', 'provider_short_name' => 'om_sn', 'provider_country' => 'SN', 'provider_currency' => 'XOF', 'provider_type' => 'mobile_money', 'provider_status' => 'active'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dexpay.api_key' => 'pk_test_unitaires',
            'dexpay.api_secret' => 'sk_test_unitaires',
            'dexpay.country_iso' => 'SN',
            'dexpay.currency' => 'XOF',
        ]);
    }

    /** Fake DexPay : providers + stubs de checkout demandés par le test. */
    private function fakeDexPay(array $checkoutStubs = []): void
    {
        Http::fake(array_merge([
            'api-sandbox.dexpay.africa/api/v1/payment-providers*' => Http::response([
                'data' => self::PROVIDERS,
            ]),
        ], $checkoutStubs));

        Cache::flush();
    }

    private function ticketType(array $overrides = []): TicketType
    {
        static $counter = 0;
        $counter++;

        $organizer = User::factory()->organizer()->create();

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Fusion Test '.$counter,
            'slug' => Event::slugFor('fusion-test-'.$counter),
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 500,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);

        return $event->ticketTypes()->create([
            'name' => 'Standard',
            'price' => 5000,
            'quantity' => 100,
            'is_active' => true,
            ...$overrides,
        ]);
    }

    /** Payload du formulaire fusionné (format récapitulatif + opérateur). */
    private function payload(TicketType $type, array $overrides = []): array
    {
        return [
            'customer_name' => 'Client Test',
            'customer_phone' => '+221771234567',
            'qty' => [$type->id => 2],
            'operator' => 'wave_sn',
            ...$overrides,
        ];
    }

    public function test_booking_page_shows_the_payment_section_and_a_pregenerated_reference(): void
    {
        $this->fakeDexPay();

        $type = $this->ticketType();

        $response = $this->get(route('booking.create', $type->event));

        $response->assertOk()
            ->assertSee('Réglez votre commande')
            ->assertSee('Choisissez votre moyen de paiement')
            ->assertSee('name="operator"', false)
            ->assertSee('name="reference"', false)
            ->assertSee('BEX-', false)
            ->assertSee('Payer maintenant', false);
    }

    public function test_posting_with_an_operator_redirects_to_the_dexpay_checkout(): void
    {
        $type = $this->ticketType();

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
                    'reference' => 'BEX-TEST-000001',
                    'status' => 'initiated',
                    'amount' => 10150,
                    'currency' => 'XOF',
                    'payment_url' => 'https://pay.dexpay.africa/BEX-TEST-000001',
                ],
            ], 201),
        ]);

        $response = $this->post(route('booking.store', $type->event), $this->payload($type));

        // Redirection directe vers la page hébergée DexPay (cashout_url de la tentative).
        $response->assertRedirect('https://pay.wave.com/m/abc123');

        $reservation = Reservation::firstOrFail();

        $this->assertSame('wave_sn', $reservation->payment_operator);
        $this->assertSame('+221771234567', $reservation->customer_phone);
        $this->assertTrue($reservation->isPending());
    }

    public function test_posting_as_json_returns_the_payment_url(): void
    {
        $type = $this->ticketType();

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*/transaction-attempt' => Http::response([
                'data' => ['status' => 'processing', 'operator' => 'wave_sn'],
            ], 201),
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => [
                    'reference' => 'BEX-TEST-000002',
                    'status' => 'initiated',
                    'payment_url' => 'https://pay.dexpay.africa/BEX-TEST-000002',
                ],
            ], 201),
        ]);

        $this->postJson(route('booking.store', $type->event), $this->payload($type))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('payment_url', 'https://pay.dexpay.africa/BEX-TEST-000002')
            ->assertJsonPath('reference', Reservation::firstOrFail()->reference);
    }

    public function test_an_unknown_operator_is_rejected_before_any_write(): void
    {
        $type = $this->ticketType();

        $this->fakeDexPay();

        $this->post(route('booking.store', $type->event), $this->payload($type, [
            'operator' => 'operateur_inconnu',
        ]))->assertSessionHasErrors('operator');

        // Aucune réservation ne doit avoir été créée.
        $this->assertSame(0, Reservation::count());

        // Aucune session de checkout ne doit avoir été appelée.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/checkout-sessions')
            && $request->method() === 'POST');
    }

    public function test_the_phone_is_required_when_an_operator_is_chosen(): void
    {
        $type = $this->ticketType();

        $this->fakeDexPay();

        $this->post(route('booking.store', $type->event), $this->payload($type, [
            'customer_phone' => '',
        ]))->assertSessionHasErrors('customer_phone');

        $this->assertSame(0, Reservation::count());
    }

    public function test_the_pregenerated_reference_is_reused_for_the_first_reservation(): void
    {
        $type = $this->ticketType();

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*/transaction-attempt' => Http::response([
                'data' => ['status' => 'processing', 'operator' => 'wave_sn'],
            ], 201),
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => ['status' => 'initiated', 'payment_url' => 'https://pay.dexpay.africa/x'],
            ], 201),
        ]);

        $shown = $this->get(route('booking.create', $type->event))->getContent();

        preg_match('/name="reference" value="(BEX-[0-9]{6}-[A-Z0-9]{6})"/', $shown, $matches);
        $this->assertNotEmpty($matches, 'La page de réservation doit afficher une référence BEX pré-générée.');

        $this->post(route('booking.store', $type->event), $this->payload($type, [
            'reference' => $matches[1],
        ]));

        $this->assertSame($matches[1], Reservation::firstOrFail()->reference);
    }

    public function test_an_already_taken_reference_is_ignored(): void
    {
        $type = $this->ticketType();

        $this->fakeDexPay([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*/transaction-attempt' => Http::response([
                'data' => ['status' => 'processing', 'operator' => 'wave_sn'],
            ], 201),
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions' => Http::response([
                'data' => ['status' => 'initiated', 'payment_url' => 'https://pay.dexpay.africa/x'],
            ], 201),
        ]);

        $taken = 'BEX-250101-TAKEN1';

        Reservation::create([
            'reference' => $taken,
            'event_id' => $type->event_id,
            'ticket_type_id' => $type->id,
            'customer_name' => 'Autre Client',
            'customer_phone' => '+221770000000',
            'quantity' => 1,
            'unit_price' => $type->price,
            'total_amount' => $type->price,
            'payment_status' => Reservation::STATUS_PENDING,
            'channel' => Reservation::CHANNEL_ONLINE,
        ]);

        $this->post(route('booking.store', $type->event), $this->payload($type, [
            'reference' => $taken,
        ]));

        // La référence prise n'est pas réutilisée : la nouvelle réservation en a une autre.
        $new = Reservation::where('customer_name', 'Client Test')->firstOrFail();
        $this->assertNotSame($taken, $new->reference);
    }

    public function test_posting_without_an_operator_falls_back_to_the_payment_page(): void
    {
        $type = $this->ticketType();

        // Sans opérateur, aucun appel réseau n'est fait (validation sautée),
        // mais on garde un fake défensif pour ne jamais dépendre du réseau.
        $this->fakeDexPay();

        $this->post(route('booking.store', $type->event), $this->payload($type, [
            'operator' => '',
        ]))->assertRedirect(route('payment.show', Reservation::firstOrFail()->reference));

        $this->assertSame(1, Reservation::count());
    }
}
