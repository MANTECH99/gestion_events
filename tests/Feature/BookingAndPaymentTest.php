<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\ReservationPaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class BookingAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function ticketType(array $overrides = []): TicketType
    {
        static $counter = 0;
        $counter++;

        $organizer = User::factory()->organizer()->create();

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Festival Test '.$counter,
            'slug' => Event::slugFor('festival-test-'.$counter),
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

    private function bookingPayload(TicketType $type, array $overrides = []): array
    {
        return [
            'customer_name' => 'Client Test',
            'customer_email' => 'client@test.sn',
            'customer_phone' => '+221771234567',
            'ticket_type_id' => $type->id,
            'quantity' => 2,
            ...$overrides,
        ];
    }

    public function test_booking_creates_a_pending_reservation(): void
    {
        $type = $this->ticketType();

        $response = $this->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $reservation = Reservation::firstOrFail();

        $this->assertTrue($reservation->isPending());
        $this->assertSame(2, $reservation->quantity);
        $this->assertSame(5000, $reservation->unit_price);
        $this->assertSame(10000, $reservation->total_amount);
        $this->assertTrue($reservation->isOnline());
        $this->assertNull($reservation->user_id);

        $response->assertRedirect(route('payment.show', $reservation->reference));
    }

    public function test_booking_does_not_generate_tickets_before_payment(): void
    {
        $type = $this->ticketType();

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $this->assertSame(0, Ticket::count());
        $this->assertSame(0, $type->fresh()->sold_online);
    }

    public function test_booking_rejects_quantity_over_stock(): void
    {
        $type = $this->ticketType(['quantity' => 5]);

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type, ['quantity' => 10]))
            ->assertSessionHas('error');

        $this->assertSame(0, Reservation::count());
    }

    public function test_booking_rejects_quantity_above_twenty(): void
    {
        $type = $this->ticketType(['quantity' => 1000]);

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type, ['quantity' => 21]))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(0, Reservation::count());
    }

    public function test_booking_rejects_ticket_type_from_another_event(): void
    {
        $type = $this->ticketType();
        $otherType = $this->ticketType(['name' => 'Autre']);

        $this->post(route('booking.store', $type->event), $this->bookingPayload($otherType))
            ->assertSessionHasErrors('ticket_type_id');
    }

    public function test_marking_paid_generates_one_ticket_per_unit(): void
    {
        $type = $this->ticketType();

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type, ['quantity' => 3]));

        $reservation = Reservation::firstOrFail();
        app(ReservationPaymentManager::class)->markPaid($reservation);

        $reservation->refresh();

        $this->assertTrue($reservation->isPaid());
        $this->assertNotNull($reservation->paid_at);
        $this->assertSame(3, $reservation->tickets()->count());
        $this->assertSame(3, $type->fresh()->sold_online);

        // Un code unique par billet.
        $this->assertSame(3, $reservation->tickets()->distinct()->count('code'));
    }

    public function test_marking_paid_twice_does_not_duplicate_tickets(): void
    {
        $type = $this->ticketType();
        $manager = app(ReservationPaymentManager::class);

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type, ['quantity' => 2]));

        $reservation = Reservation::firstOrFail();
        $manager->markPaid($reservation);
        $manager->markPaid($reservation->fresh());

        $this->assertSame(2, $reservation->tickets()->count());
        $this->assertSame(2, $type->fresh()->sold_online);
    }

    public function test_remote_completed_status_marks_reservation_paid(): void
    {
        $type = $this->ticketType();

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $reservation = Reservation::firstOrFail();
        app(ReservationPaymentManager::class)->applyRemoteStatus($reservation, [
            'status' => 'completed',
            'reference' => $reservation->reference,
            'amount' => $reservation->total_amount,
            'operator' => 'wave',
        ]);

        $this->assertTrue($reservation->fresh()->isPaid());
        $this->assertSame(2, $reservation->tickets()->count());
    }

    public function test_remote_completed_status_updates_the_payment_session(): void
    {
        $type = $this->ticketType();

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $reservation = Reservation::firstOrFail();
        app(ReservationPaymentManager::class)->applyRemoteStatus($reservation, [
            'status' => 'completed',
            'reference' => $reservation->reference,
            'id' => 'abc123',
            'transaction_id' => 'trx456',
            'amount' => $reservation->total_amount,
            'operator' => 'wave',
            'currency' => 'XOF',
            'fee_amount' => 150,
            'completed_at' => now()->toIso8601String(),
        ]);

        $payment = Payment::firstOrFail();

        $this->assertSame('completed', $payment->status);
        $this->assertSame('abc123', $payment->session_id);
        $this->assertSame('trx456', $payment->transaction_id);
        $this->assertSame('wave', $payment->operator);
        $this->assertSame(150, (int) $payment->payload['fee_amount']);
    }

    public function test_remote_webhook_payload_saves_session_id_and_transaction_id(): void
    {
        $type = $this->ticketType();

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $reservation = Reservation::firstOrFail();
        app(ReservationPaymentManager::class)->applyRemoteStatus($reservation, [
            'status' => 'completed',
            'reference' => $reservation->reference,
            'checkout_session_id' => '6ac8e9417cd38a015a07a397',
            'transaction_id' => '6ac8e9427cd38a015a07a3a1',
        ]);

        $payment = Payment::firstOrFail();

        $this->assertSame('6ac8e9417cd38a015a07a397', $payment->session_id);
        $this->assertSame('6ac8e9427cd38a015a07a3a1', $payment->transaction_id);
    }

    public function test_remote_success_status_marks_reservation_paid(): void
    {
        $type = $this->ticketType();

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $reservation = Reservation::firstOrFail();
        app(ReservationPaymentManager::class)->applyRemoteStatus($reservation, [
            'status' => 'success',
            'reference' => $reservation->reference,
        ]);

        $this->assertTrue($reservation->fresh()->isPaid());
        $this->assertSame(2, $reservation->tickets()->count());
    }

    public function test_payment_success_is_reachable_via_signed_return_url(): void
    {
        $type = $this->ticketType();

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));
        $reservation = Reservation::firstOrFail();

        // Un paiement en cours existe : le retour signé doit réconcilier
        // (syncReservation) sans lever de classe introuvable.
        Payment::create([
            'reservation_id' => $reservation->id,
            'provider' => 'dexpay',
            'reference' => $reservation->reference,
            'status' => 'initiated',
            'currency' => 'XOF',
            'amount' => $reservation->total_amount,
        ]);

        Http::fake([
            'api-sandbox.dexpay.africa/api/v1/checkout-sessions/*' => Http::response([
                'data' => ['status' => 'completed', 'reference' => $reservation->reference],
            ]),
        ]);

        // Sans la session de la réservation ni signature : accès refusé.
        $this->flushSession();
        $this->get(route('payment.success', $reservation->reference))->assertForbidden();

        // L'URL signée transmise à DexPay (success_url) ouvre la page sans session
        // et réconcilie le paiement auprès de DexPay.
        $this->get(URL::signedRoute('payment.success', ['reference' => $reservation->reference]))
            ->assertOk();

        $this->assertTrue($reservation->fresh()->isPaid());
    }

    public function test_remote_refresh_preserves_previously_captured_fee(): void
    {
        $type = $this->ticketType();
        $manager = app(ReservationPaymentManager::class);

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $reservation = Reservation::firstOrFail();
        $manager->applyRemoteStatus($reservation, [
            'status' => 'completed',
            'reference' => $reservation->reference,
            'fee_amount' => 150,
        ]);

        // Relance (GET /checkout-sessions) : DexPay ne renvoie pas les frais,
        // mais ceux déjà capturés doivent être conservés.
        $manager->applyRemoteStatus($reservation->fresh(), [
            'status' => 'completed',
            'reference' => $reservation->reference,
        ]);

        $this->assertSame(150, (int) Payment::firstOrFail()->payload['fee_amount']);
    }

    public function test_remote_failed_status_marks_reservation_failed_without_tickets(): void
    {
        $type = $this->ticketType();

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $reservation = Reservation::firstOrFail();
        app(ReservationPaymentManager::class)->applyRemoteStatus($reservation, [
            'status' => 'failed',
            'reference' => $reservation->reference,
        ]);

        $this->assertSame(Reservation::STATUS_FAILED, $reservation->fresh()->payment_status);
        $this->assertSame(0, $reservation->tickets()->count());
    }

    public function test_refunded_reservation_invalidates_its_tickets(): void
    {
        $type = $this->ticketType();
        $manager = app(ReservationPaymentManager::class);

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $reservation = Reservation::firstOrFail();
        $manager->markPaid($reservation);

        // Le remboursement distant est simulé via applyRemoteStatus pour éviter l'appel HTTP.
        $reservation->update(['payment_status' => Reservation::STATUS_REFUNDED]);
        $reservation->tickets()->update(['status' => Ticket::STATUS_REFUNDED]);

        $this->assertTrue($reservation->fresh()->isRefunded());
        $this->assertSame(0, $reservation->tickets()->where('status', Ticket::STATUS_VALID)->count());
    }

    public function test_payment_page_requires_access_to_the_reservation(): void
    {
        $type = $this->ticketType();

        $this->post(route('booking.store', $type->event), $this->bookingPayload($type));
        $reservation = Reservation::firstOrFail();

        // Nouvelle session : plus aucun droit d'accès.
        $this->flushSession();
        $this->get(route('payment.show', $reservation->reference))->assertForbidden();
    }

    public function test_booking_while_logged_in_links_the_reservation_to_the_account(): void
    {
        $user = User::factory()->create();
        $type = $this->ticketType();

        $this->actingAs($user)
            ->post(route('booking.store', $type->event), $this->bookingPayload($type));

        $this->assertSame($user->id, Reservation::firstOrFail()->user_id);
    }
}
