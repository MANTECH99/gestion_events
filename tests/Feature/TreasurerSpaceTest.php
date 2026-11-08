<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payout;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationPaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Espace trésorière : compte créé par l'organisateur, connexion dirigée vers
 * la page retrait, consultation des sessions de paiement et des réservations,
 * et retrait effectué avec le code défini par l'organisateur.
 */
class TreasurerSpaceTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private User $treasurer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->organizer()->create();
        $this->treasurer = User::factory()->treasurer($this->organizer)->create();
    }

    public function test_organizer_can_create_a_treasurer_account(): void
    {
        $this->actingAs($this->organizer)->post(route('organizer.users.store'), [
            'account_type' => 'treasurer',
            'first_name' => 'Aissatou',
            'last_name' => 'Fall',
            'username' => 'aissatou-fall',
            'email' => 'aissatou@test.sn',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])->assertSessionHas('success');

        $created = User::where('email', 'aissatou@test.sn')->firstOrFail();

        $this->assertSame(User::ROLE_TREASURER, $created->role);
        $this->assertTrue($created->isTreasurer());
        $this->assertSame($this->organizer->id, $created->organizer_id);
        // L'organisateur est invité à lui communquer son code de retrait.
        $this->assertStringContainsString('code de retrait', (string) session('success'));
    }

    public function test_login_redirects_the_treasurer_to_the_payout_page(): void
    {
        $this->post(route('login.store'), [
            'email' => $this->treasurer->email,
            'password' => 'password',
        ])->assertRedirect(route('treasurer.payouts.index'));
    }

    public function test_treasurer_can_view_retraits_sessions_and_reservations(): void
    {
        $this->actingAs($this->treasurer)
            ->get(route('treasurer.payouts.index'))
            ->assertOk()
            ->assertSee('Retraits');

        $this->actingAs($this->treasurer)
            ->get(route('treasurer.payment-sessions.index'))
            ->assertOk()
            ->assertSee('Sessions de paiement');

        $this->actingAs($this->treasurer)
            ->get(route('treasurer.reservations.index'))
            ->assertOk()
            ->assertSee('Réservations');
    }

    public function test_other_roles_cannot_access_the_treasurer_space(): void
    {
        $client = User::factory()->create();

        $this->actingAs($client)
            ->get(route('treasurer.payouts.index'))
            ->assertRedirect(route('home'));

        $this->actingAs($this->organizer)
            ->get(route('treasurer.payouts.index'))
            ->assertRedirect(route('home'));
    }

    public function test_payout_uses_the_organizers_withdrawal_code(): void
    {
        $this->organizer->setPayoutPin('4242');

        Http::fake(['*' => Http::response(['id' => 'dexpay_t1', 'status' => 'PENDING', 'fees' => 3], 201)]);

        // Mauvais code : refusé avant tout appel DexPay.
        $this->actingAs($this->treasurer)->post(route('treasurer.payouts.store'), [
            'amount' => 5000,
            'destination_phone' => '+221771234567',
            'operator' => 'wave_sn_payout',
            'recipient_name' => 'Organisateur',
            'payout_pin' => '0000',
        ])->assertSessionHasErrors(['payout_pin']);

        Http::assertNothingSent();
        $this->assertSame(0, Payout::count());

        // Code de l'organisateur : le retrait est créé pour son organisation.
        $this->actingAs($this->treasurer)->post(route('treasurer.payouts.store'), [
            'amount' => 5000,
            'destination_phone' => '+221771234567',
            'operator' => 'wave_sn_payout',
            'recipient_name' => 'Organisateur',
            'payout_pin' => '4242',
        ])->assertSessionHas('success');

        $payout = Payout::firstOrFail();

        $this->assertSame($this->organizer->id, $payout->organizer_id);
        $this->assertNotSame($this->treasurer->id, $payout->organizer_id);
    }

    public function test_payout_explains_when_the_organizer_has_no_code(): void
    {
        $this->actingAs($this->treasurer)->post(route('treasurer.payouts.store'), [
            'amount' => 5000,
            'destination_phone' => '+221771234567',
            'operator' => 'wave_sn_payout',
            'payout_pin' => '4242',
        ])->assertSessionHas('error');

        $this->assertStringContainsString('organisateur n\'a pas encore défini', (string) session('error'));
        $this->assertSame(0, Payout::count());
    }

    public function test_the_treasurer_sees_only_her_organizers_reservations(): void
    {
        $mine = $this->makePaidReservation($this->organizer, 'moi@test.sn', 'moi');

        $foreignOrganizer = User::factory()->organizer()->create();
        $theirs = $this->makePaidReservation($foreignOrganizer, 'autre@test.sn', 'autre');

        $this->actingAs($this->treasurer)
            ->get(route('treasurer.reservations.index'))
            ->assertOk()
            ->assertSee($mine->reference)
            ->assertDontSee($theirs->reference);
    }

    public function test_the_treasurer_cannot_refund_or_delete_a_reservation(): void
    {
        $reservation = $this->makePaidReservation($this->organizer, 'client@test.sn', 'remboursement');

        // Réservé à l'espace organisateur : la trésorière est renvoyée.
        $this->actingAs($this->treasurer)
            ->post(route('organizer.reservations.refund', $reservation))
            ->assertRedirect(route('home'));

        $this->actingAs($this->treasurer)
            ->delete(route('organizer.reservations.destroy', $reservation))
            ->assertRedirect(route('home'));

        $this->assertNotNull($reservation->refresh());
    }

    private function makePaidReservation(User $organizer, string $email, string $slug): Reservation
    {
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Concert '.$slug,
            'slug' => 'concert-'.$slug,
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);

        $type = $event->ticketTypes()->create([
            'name' => 'VIP',
            'price' => 10000,
            'quantity' => 50,
            'is_active' => true,
        ]);

        $this->post(route('booking.store', $event), [
            'customer_name' => 'Client '.$slug,
            'customer_email' => $email,
            'customer_phone' => '+221770000000',
            'ticket_type_id' => $type->id,
            'quantity' => 1,
        ]);

        $reservation = Reservation::where('event_id', $event->id)->firstOrFail();
        app(ReservationPaymentManager::class)->markPaid($reservation);

        return $reservation->refresh();
    }
}
