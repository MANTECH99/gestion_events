<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\PosSale;
use App\Models\StockAllocation;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrganizerPointOfSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private User $pos;

    private Event $event;

    private TicketType $vipCourse;

    private TicketType $tribune;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->organizer()->create();
        $this->pos = User::factory()->pointOfSale($this->organizer)->create([
            'first_name' => 'Ibrahima',
            'last_name' => 'Fall',
            'point_of_sale' => 'Boutique Centre-ville',
        ]);

        $this->event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Grand derby ASKO — Casa',
            'slug' => 'grand-derby-asko-casa',
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 200,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $this->vipCourse = $this->event->ticketTypes()->create([
            'name' => 'Vip course', 'price' => 10000, 'quantity' => 50, 'is_active' => true,
        ]);
        $this->tribune = $this->event->ticketTypes()->create([
            'name' => 'Tribune', 'price' => 3000, 'quantity' => 50, 'is_active' => true,
        ]);

        StockAllocation::create([
            'ticket_type_id' => $this->vipCourse->id, 'user_id' => $this->pos->id, 'allocated' => 25, 'sold' => 8,
        ]);
        StockAllocation::create([
            'ticket_type_id' => $this->tribune->id, 'user_id' => $this->pos->id, 'allocated' => 25, 'sold' => 8,
        ]);
    }

    private function sale(TicketType $type, int $quantity, int $total, string $date): PosSale
    {
        $sale = PosSale::create([
            'stock_allocation_id' => StockAllocation::where('user_id', $this->pos->id)
                ->where('ticket_type_id', $type->id)->firstOrFail()->id,
            'ticket_type_id' => $type->id,
            'user_id' => $this->pos->id,
            'quantity' => $quantity,
            'unit_price' => (int) ($total / $quantity),
            'total_amount' => $total,
            'buyer_name' => 'Client comptoir',
        ]);

        $sale->forceFill(['created_at' => Carbon::parse($date)])->save();

        return $sale;
    }

    public function test_the_index_lists_the_points_of_sale(): void
    {
        $content = $this->actingAs($this->organizer)
            ->get(route('organizer.pos.index'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('Ibrahima Fall', $content);
        $this->assertStringContainsString('Boutique Centre-ville', $content);
        $this->assertStringContainsString(route('organizer.pos.show', $this->pos), $content);
    }

    public function test_the_index_is_scoped_to_the_organizer(): void
    {
        $other = User::factory()->organizer()->create();
        User::factory()->pointOfSale($other)->create([
            'first_name' => 'Moussa', 'last_name' => 'Diop',
        ]);

        // Le header affiche le nom du compte connecté : on le fixe pour que
        // Faker ne puisse pas générer « Moussa Diop » et fausser l'assertion.
        $this->organizer->update(['first_name' => 'Awa', 'last_name' => 'Ndiaye']);

        $content = $this->actingAs($this->organizer)
            ->get(route('organizer.pos.index'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringNotContainsString('Moussa Diop', $content);
    }

    public function test_the_index_never_lists_clients(): void
    {
        User::factory()->create([
            'first_name' => 'Abdoul ahad', 'last_name' => 'Gueye',
        ]);

        $admin = User::factory()->admin()->create();

        // Même garde-fou que le test ci-dessus : nom du connecté fixé pour
        // éviter toute collision avec le client à ne pas afficher.
        $admin->update(['first_name' => 'Cheikh', 'last_name' => 'Sarr']);

        $content = $this->actingAs($admin)
            ->get(route('organizer.pos.index'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('Ibrahima Fall', $content);
        $this->assertStringNotContainsString('Abdoul ahad Gueye', $content);
    }

    public function test_the_show_displays_the_cards_and_sales_history(): void
    {
        $this->sale($this->vipCourse, 8, 80000, '2026-09-30 14:20:00');
        $this->sale($this->tribune, 8, 24000, '2026-09-19 11:05:00');

        $content = $this->actingAs($this->organizer)
            ->get(route('organizer.pos.show', $this->pos))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);

        $this->assertStringContainsString('Billets vendus', $content);
        $this->assertStringContainsString('>16<', $content);
        $this->assertStringContainsString('Recette encaissée', $content);
        $this->assertStringContainsString('>104 000 FCFA<', $content);
        $this->assertStringContainsString('Opérations', $content);
        $this->assertStringContainsString('>2<', $content);

        $this->assertStringContainsString('Historique des ventes', $content);
        $this->assertStringContainsString('30 sept. 2026', $content);
        $this->assertStringContainsString('19 sept. 2026', $content);
        $this->assertStringContainsString('Grand derby ASKO — Casa', $content);
        $this->assertStringContainsString('Vip course', $content);
        $this->assertStringContainsString('Tribune', $content);
        $this->assertStringContainsString('Client comptoir', $content);
        $this->assertStringContainsString('>80 000 F<', $content);
        $this->assertStringContainsString('>24 000 F<', $content);
    }

    public function test_an_organizer_cannot_open_another_organizer_point_of_sale(): void
    {
        $other = User::factory()->organizer()->create();
        $foreign = User::factory()->pointOfSale($other)->create();

        $this->actingAs($this->organizer)
            ->get(route('organizer.pos.show', $foreign))
            ->assertNotFound();
    }

    public function test_a_non_point_of_sale_user_returns_not_found(): void
    {
        $agent = User::factory()->agent($this->organizer)->create();

        $this->actingAs($this->organizer)
            ->get(route('organizer.pos.show', $agent))
            ->assertNotFound();
    }

    public function test_admin_can_browse_the_points_of_sale(): void
    {
        $admin = User::factory()->admin()->create();
        $this->sale($this->vipCourse, 2, 20000, '2026-09-30 14:20:00');

        $this->actingAs($admin)
            ->get(route('organizer.pos.index'))
            ->assertOk()
            ->assertSee('Ibrahima Fall');

        $this->actingAs($admin)
            ->get(route('organizer.pos.show', $this->pos))
            ->assertOk()
            ->assertSee('Historique des ventes');
    }
}
