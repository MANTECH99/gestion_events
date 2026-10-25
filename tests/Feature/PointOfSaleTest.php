<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\PosSale;
use App\Models\Reservation;
use App\Models\StockAllocation;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointOfSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private User $pos;

    private TicketType $type;

    private StockAllocation $allocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->organizer()->create();
        $this->pos = User::factory()->pointOfSale($this->organizer)->create();

        $event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Match Test',
            'slug' => 'match-test',
            'location' => 'Dakar',
            'event_date' => now()->addDays(10),
            'total_tickets' => 1000,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $this->type = $event->ticketTypes()->create([
            'name' => 'Tribune', 'price' => 2000, 'quantity' => 500, 'is_active' => true,
        ]);

        $this->allocation = StockAllocation::create([
            'ticket_type_id' => $this->type->id,
            'user_id' => $this->pos->id,
            'allocated' => 50,
            'sold' => 0,
        ]);
    }

    private function salePayload(array $overrides = []): array
    {
        return [
            'stock_allocation_id' => $this->allocation->id,
            'quantity' => 2,
            'buyer_name' => 'Acheteur Test',
            'buyer_phone' => '+221771111111',
            ...$overrides,
        ];
    }

    public function test_pos_dashboard_renders_for_point_of_sale(): void
    {
        $this->actingAs($this->pos)->get(route('pos.dashboard'))->assertOk()->assertSee('Tribune');
    }

    public function test_physical_sale_decrements_stock_and_generates_tickets(): void
    {
        $this->actingAs($this->pos)
            ->from(route('pos.dashboard'))
            ->post(route('pos.sales.store'), $this->salePayload(['quantity' => 3]))
            ->assertRedirect(route('pos.dashboard'))
            ->assertSessionHas('success');

        $this->allocation->refresh();
        $this->type->refresh();

        $this->assertSame(3, $this->allocation->sold);
        $this->assertSame(47, $this->allocation->remaining);
        $this->assertSame(3, $this->type->sold_physical);
        $this->assertSame(0, $this->type->sold_online);

        $reservation = Reservation::firstOrFail();
        $this->assertTrue($reservation->isPhysical());
        $this->assertTrue($reservation->isPaid());
        $this->assertSame(6000, $reservation->total_amount);
        $this->assertSame($this->pos->id, $reservation->sold_by);
        $this->assertSame(0, $reservation->tickets()->count());

        $this->assertSame(1, PosSale::count());
        $this->assertSame(6000, PosSale::firstOrFail()->total_amount);
    }

    public function test_sale_is_rejected_when_stock_is_insufficient(): void
    {
        $this->actingAs($this->pos)
            ->from(route('pos.dashboard'))
            ->post(route('pos.sales.store'), $this->salePayload(['quantity' => 999]))
            ->assertRedirect(route('pos.dashboard'))
            ->assertSessionHas('error');

        $this->allocation->refresh();

        $this->assertSame(0, $this->allocation->sold);
        $this->assertSame(0, PosSale::count());
        $this->assertSame(0, Ticket::count());
        $this->assertSame(0, Reservation::count());
    }

    public function test_selling_exactly_the_remaining_stock_is_allowed(): void
    {
        $this->actingAs($this->pos)
            ->post(route('pos.sales.store'), $this->salePayload(['quantity' => 50]))
            ->assertSessionHas('success');

        $this->assertSame(0, $this->allocation->fresh()->remaining);
        $this->assertSame(0, Ticket::count());
    }

    public function test_pos_cannot_use_another_sellers_stock(): void
    {
        $otherPos = User::factory()->pointOfSale($this->organizer)->create();

        $this->actingAs($otherPos)
            ->from(route('pos.dashboard'))
            ->post(route('pos.sales.store'), $this->salePayload())
            ->assertNotFound();

        $this->assertSame(0, $this->allocation->fresh()->sold);
        $this->assertSame(0, Ticket::count());
    }

    public function test_sale_requires_a_buyer_name(): void
    {
        $this->actingAs($this->pos)
            ->post(route('pos.sales.store'), $this->salePayload(['buyer_name' => '']))
            ->assertSessionHasErrors('buyer_name');

        $this->assertSame(0, $this->allocation->fresh()->sold);
    }

    public function test_sale_rejects_zero_quantity(): void
    {
        $this->actingAs($this->pos)
            ->post(route('pos.sales.store'), $this->salePayload(['quantity' => 0]))
            ->assertSessionHasErrors('quantity');
    }

    public function test_organizer_can_attribute_stock_to_a_pos(): void
    {
        $pos = User::factory()->pointOfSale($this->organizer)->create();

        $this->actingAs($this->organizer)
            ->from(route('organizer.stocks.index'))
            ->post(route('organizer.stocks.store'), [
                'user_id' => $pos->id,
                'ticket_type_id' => $this->type->id,
                'allocated' => 100,
            ])
            ->assertRedirect(route('organizer.stocks.index'))
            ->assertSessionHas('success');

        $allocation = StockAllocation::where('user_id', $pos->id)
            ->where('ticket_type_id', $this->type->id)
            ->firstOrFail();

        $this->assertSame(100, $allocation->allocated);
    }

    public function test_organizer_cannot_attribute_stock_to_another_organizers_pos(): void
    {
        $otherOrganizer = User::factory()->organizer()->create();
        $foreignPos = User::factory()->pointOfSale($otherOrganizer)->create();

        $this->actingAs($this->organizer)
            ->post(route('organizer.stocks.store'), [
                'user_id' => $foreignPos->id,
                'ticket_type_id' => $this->type->id,
                'allocated' => 100,
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_stock_cannot_be_reduced_below_sold_quantity(): void
    {
        $this->actingAs($this->pos)
            ->post(route('pos.sales.store'), $this->salePayload(['quantity' => 10]));

        $this->actingAs($this->organizer)
            ->from(route('organizer.stocks.index'))
            ->put(route('organizer.stocks.update', $this->allocation), [
                'allocated' => 5,
            ])
            ->assertSessionHas('error');

        $this->assertSame(50, $this->allocation->fresh()->allocated);
    }
}
