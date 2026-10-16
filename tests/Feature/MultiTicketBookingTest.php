<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTicketBookingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Event, 1: \App\Models\TicketType, 2: \App\Models\TicketType} */
    private function eventWithTypes(): array
    {
        $organizer = User::factory()->organizer()->create();

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Festival Multi',
            'slug' => 'festival-multi',
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 500,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);

        $normal = $event->ticketTypes()->create([
            'name' => 'Normal',
            'price' => 5000,
            'quantity' => 100,
            'is_active' => true,
        ]);

        $vip = $event->ticketTypes()->create([
            'name' => 'VIP',
            'price' => 15000,
            'quantity' => 50,
            'is_active' => true,
        ]);

        return [$event, $normal, $vip];
    }

    private function payload(array $qtys): array
    {
        return [
            'customer_name' => 'Client Test',
            'customer_email' => 'client@test.sn',
            'customer_phone' => '+221771234567',
            'qty' => $qtys,
        ];
    }

    public function test_booking_with_multiple_types_creates_one_reservation_per_type(): void
    {
        [$event, $normal, $vip] = $this->eventWithTypes();

        $response = $this->post(route('booking.store', $event), $this->payload([
            $normal->id => 2,
            $vip->id => 1,
        ]));

        $reservations = Reservation::orderBy('id')->get();

        $this->assertCount(2, $reservations);
        $this->assertTrue($reservations->every->isPending());

        $this->assertSame($normal->id, $reservations[0]->ticket_type_id);
        $this->assertSame(2, $reservations[0]->quantity);
        $this->assertSame(10000, $reservations[0]->total_amount);

        $this->assertSame($vip->id, $reservations[1]->ticket_type_id);
        $this->assertSame(1, $reservations[1]->quantity);
        $this->assertSame(15000, $reservations[1]->total_amount);

        $this->assertSame(
            [$reservations[0]->reference, $reservations[1]->reference],
            session('payment_refs'),
        );
        $this->assertTrue(session('reservation_access.'.$reservations[0]->id));
        $this->assertTrue(session('reservation_access.'.$reservations[1]->id));

        $response->assertRedirect(route('payment.show', $reservations[0]->reference));
    }

    public function test_booking_without_any_ticket_is_rejected(): void
    {
        [$event] = $this->eventWithTypes();

        $this->post(route('booking.store', $event), [
            'customer_name' => 'Client Test',
            'customer_email' => 'client@test.sn',
        ])->assertSessionHasErrors('qty');

        $this->assertSame(0, Reservation::count());
    }

    public function test_booking_rejects_an_unknown_ticket_type_in_the_recap(): void
    {
        [$event, $normal] = $this->eventWithTypes();

        $this->post(route('booking.store', $event), $this->payload([
            $normal->id => 1,
            999999 => 1,
        ]))->assertSessionHasErrors('qty');

        $this->assertSame(0, Reservation::count());
    }

    public function test_booking_rejects_a_quantity_over_stock_in_the_recap(): void
    {
        [$event] = $this->eventWithTypes();
        $scarce = $event->ticketTypes()->create([
            'name' => 'Rare',
            'price' => 1000,
            'quantity' => 5,
            'is_active' => true,
        ]);

        $this->post(route('booking.store', $event), $this->payload([$scarce->id => 10]))
            ->assertSessionHas('error');

        $this->assertSame(0, Reservation::count());
    }

    public function test_recap_page_reads_quantities_from_the_query_string(): void
    {
        [$event, $normal] = $this->eventWithTypes();

        $this->get(route('booking.create', $event).'?qty['.$normal->id.']=3')
            ->assertOk()
            ->assertSee('name="qty['.$normal->id.']"', false)
            ->assertSee('value="3"', false)
            ->assertSee('Normal', false);
    }

    public function test_success_page_lists_other_reservations_awaiting_payment(): void
    {
        [$event, $normal] = $this->eventWithTypes();

        $paid = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $normal->id,
            'user_id' => null,
            'customer_name' => 'Client Test',
            'customer_email' => 'client@test.sn',
            'quantity' => 1,
            'unit_price' => 5000,
            'total_amount' => 5000,
            'payment_status' => Reservation::STATUS_PAID,
            'channel' => Reservation::CHANNEL_ONLINE,
            'paid_at' => now(),
        ]);

        $pending = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $normal->id,
            'user_id' => null,
            'customer_name' => 'Client Test',
            'customer_email' => 'client@test.sn',
            'quantity' => 2,
            'unit_price' => 5000,
            'total_amount' => 10000,
            'payment_status' => Reservation::STATUS_PENDING,
            'channel' => Reservation::CHANNEL_ONLINE,
        ]);

        $this->withSession([
            'reservation_access.'.$paid->id => true,
            'payment_refs' => [$paid->reference, $pending->reference],
        ]);

        $this->get(route('payment.success', $paid->reference))
            ->assertOk()
            ->assertSee('attente de paiement')
            ->assertSee($pending->reference, false);

        // Sans référence restante : pas de panneau.
        $this->withSession([
            'reservation_access.'.$paid->id => true,
            'payment_refs' => [$paid->reference],
        ]);

        $this->get(route('payment.success', $paid->reference))
            ->assertOk()
            ->assertDontSee('attente de paiement');
    }
}
