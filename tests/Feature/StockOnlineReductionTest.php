<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\StockAllocation;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'attribution d'un stock physique réduit le stock en ligne :
 * stock en ligne + stock physique = stock total (quantity).
 */
class StockOnlineReductionTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Event $event;

    private TicketType $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->organizer()->create();

        $this->event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Concert Réduction',
            'slug' => 'concert-reduction',
            'location' => 'Dakar',
            'event_date' => now()->addDays(10),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $this->type = $this->event->ticketTypes()->create([
            'name' => 'Tribune', 'price' => 2000, 'quantity' => 30, 'is_active' => true,
        ]);
    }

    private function allocate(User $pos, int $allocated): StockAllocation
    {
        return StockAllocation::create([
            'ticket_type_id' => $this->type->id,
            'user_id' => $pos->id,
            'allocated' => $allocated,
            'sold' => 0,
        ]);
    }

    public function test_physical_allocation_reduces_the_online_quantity(): void
    {
        $this->allocate(User::factory()->pointOfSale($this->organizer)->create(), 20);

        $this->assertSame(10, $this->type->online_quantity);
        $this->assertSame(10, $this->type->available_online);
    }

    public function test_sold_online_and_physical_allocation_share_the_same_pool(): void
    {
        $this->type->increment('sold_online', 5);
        $this->allocate(User::factory()->pointOfSale($this->organizer)->create(), 15);

        $this->assertSame(10, $this->type->available_online);
        $this->assertSame(15, $this->type->online_quantity);
    }

    public function test_event_global_online_stock_deducts_the_physical_allocations(): void
    {
        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Grand Concert',
            'slug' => 'grand-concert',
            'location' => 'Dakar',
            'event_date' => now()->addDays(10),
            'total_tickets' => 800,
            'status' => Event::STATUS_PUBLISHED,
        ]);
        $type = $event->ticketTypes()->create([
            'name' => 'Tribune', 'price' => 2000, 'quantity' => 800, 'is_active' => true,
        ]);
        $type->increment('sold_online', 5);
        StockAllocation::create([
            'ticket_type_id' => $type->id,
            'user_id' => User::factory()->pointOfSale($this->organizer)->create()->id,
            'allocated' => 80,
            'sold' => 0,
        ]);

        $this->assertSame(715, $event->refresh()->available_tickets);

        $this->actingAs($this->organizer)
            ->get(route('organizer.events.ticket-types', $event))
            ->assertOk()
            ->assertSee('715', false);
    }

    public function test_online_booking_is_limited_by_the_remaining_online_pool(): void
    {
        $pos = User::factory()->pointOfSale($this->organizer)->create();
        $this->allocate($pos, 20);

        $this->post(route('booking.store', $this->event), [
            'customer_name' => 'Acheteur Test',
            'customer_email' => 'acheteur@test.sn',
            'customer_phone' => '+221770000000',
            'ticket_type_id' => $this->type->id,
            'quantity' => 12,
        ])->assertSessionHas('error');

        $this->assertSame(0, Reservation::where('ticket_type_id', $this->type->id)->count());
    }

    public function test_online_booking_fits_within_the_remaining_online_pool(): void
    {
        $this->allocate(User::factory()->pointOfSale($this->organizer)->create(), 20);

        $this->post(route('booking.store', $this->event), [
            'customer_name' => 'Acheteur Test',
            'customer_email' => 'acheteur@test.sn',
            'customer_phone' => '+221770000000',
            'ticket_type_id' => $this->type->id,
            'quantity' => 10,
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame(1, Reservation::where('ticket_type_id', $this->type->id)->count());
    }

    public function test_an_allocation_is_capped_by_the_remaining_online_stock(): void
    {
        $posA = User::factory()->pointOfSale($this->organizer)->create();
        $this->allocate($posA, 20);
        $posB = User::factory()->pointOfSale($this->organizer)->create();

        $this->actingAs($this->organizer)
            ->from(route('organizer.stocks.index'))
            ->post(route('organizer.stocks.store'), [
                'user_id' => $posB->id,
                'ticket_type_id' => $this->type->id,
                'allocated' => 15,
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, StockAllocation::where('user_id', $posB->id)->count());
    }

    public function test_an_allocation_within_the_remaining_online_stock_is_saved(): void
    {
        $posA = User::factory()->pointOfSale($this->organizer)->create();
        $this->allocate($posA, 20);
        $posB = User::factory()->pointOfSale($this->organizer)->create();

        $this->actingAs($this->organizer)
            ->post(route('organizer.stocks.store'), [
                'user_id' => $posB->id,
                'ticket_type_id' => $this->type->id,
                'allocated' => 10,
            ])
            ->assertSessionHas('success');

        $this->assertSame(10, StockAllocation::where('user_id', $posB->id)->firstOrFail()->allocated);
    }

    public function test_updating_an_allocation_is_capped_by_other_allocations(): void
    {
        $posA = User::factory()->pointOfSale($this->organizer)->create();
        $allocationA = $this->allocate($posA, 20);
        $posB = User::factory()->pointOfSale($this->organizer)->create();
        $this->allocate($posB, 5);

        $this->actingAs($this->organizer)
            ->from(route('organizer.stocks.index'))
            ->put(route('organizer.stocks.update', $allocationA), [
                'allocated' => 26,
            ])
            ->assertSessionHas('error');

        $this->assertSame(20, $allocationA->fresh()->allocated);
    }
}
