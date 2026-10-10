<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $organizerA;

    private User $organizerB;

    private Event $eventA1;

    private Event $eventA2;

    private Event $eventB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->organizerA = User::factory()->organizer()->create();
        $this->organizerB = User::factory()->organizer()->create();

        $this->eventA1 = $this->event($this->organizerA, 'a-un');
        $this->eventA2 = $this->event($this->organizerA, 'a-deux');
        $this->eventB1 = $this->event($this->organizerB, 'b-un');
    }

    private function event(User $organizer, string $slug): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Événement '.$slug,
            'slug' => 'filtrer-'.$slug,
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ]);
    }

    public function test_an_organizer_filter_scopes_the_count_cards(): void
    {
        User::factory()->agent($this->organizerA)->create();
        User::factory()->pointOfSale($this->organizerA)->create();
        User::factory()->agent($this->organizerB)->create();
        User::factory()->pointOfSale($this->organizerB)->create();

        $this->paid($this->eventA1, 1, 2000);
        $reservation = $this->paid($this->eventA2, 1, 3000);
        Ticket::create([
            'code' => Ticket::makeCode(),
            'reservation_id' => $reservation->id,
            'event_id' => $reservation->event_id,
            'ticket_type_id' => $reservation->ticket_type_id,
            'holder_name' => 'Détenteur',
            'price' => 3000,
        ]);

        $content = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['organizer_id' => $this->organizerA->id]))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertSame('1', $this->statValue($content, 'Agents'));
        $this->assertSame('1', $this->statValue($content, 'Points de vente'));
        $this->assertSame('2', $this->statValue($content, 'Événements'));
        $this->assertSame('1', $this->statValue($content, 'Billets émis'));
        $this->assertSame('2', $this->statValue($content, 'Organisateurs'));
    }

    public function test_an_event_filter_scopes_the_count_cards(): void
    {
        User::factory()->agent($this->organizerA)->create();
        User::factory()->pointOfSale($this->organizerA)->create();

        $content = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['organizer_id' => $this->organizerA->id, 'event_id' => $this->eventA1->id]))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertSame('1', $this->statValue($content, 'Agents'));
        $this->assertSame('1', $this->statValue($content, 'Points de vente'));
        $this->assertSame('1', $this->statValue($content, 'Événements'));
    }

    public function test_organizer_detail_offers_an_event_filter(): void
    {
        $content = $this->actingAs($this->admin)
            ->get(route('admin.organizers.show', $this->organizerA))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('name="event_id"', $content);
        $this->assertStringContainsString('Tous les événements', $content);
        $this->assertStringContainsString('Événement a-un', $content);
        $this->assertStringContainsString('Événement a-deux', $content);
    }

    public function test_organizer_detail_event_filter_scopes_the_summary(): void
    {
        $this->paid($this->eventA1, 2, 10000);
        $this->paid($this->eventA2, 1, 3000);

        $content = $this->actingAs($this->admin)
            ->get(route('admin.organizers.show', [$this->organizerA, 'event_id' => $this->eventA1->id]))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('10 000 FCFA', $content);
        $this->assertStringNotContainsString('13 000 FCFA', $content);
    }

    public function test_the_organizers_leaderboard_sums_their_events_paid_revenue(): void
    {
        $this->paid($this->eventA1, 2, 10000);
        $this->paid($this->eventA2, 1, 3000);
        $this->paid($this->eventB1, 3, 6000);

        $content = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString(money_short(13000), $content);
        $this->assertStringContainsString(money_short(6000), $content);
    }

    public function test_the_count_cards_are_global_without_a_filter(): void
    {
        $content = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertSame('3', $this->statValue($content, 'Événements'));
    }

    private function statValue(string $content, string $label): ?string
    {
        preg_match(
            '/'.preg_quote($label, '/').'<\/p>\s*<p class="mt-2[^"]*">(-?\d+)<\/p>/u',
            $content,
            $matches
        );

        return $matches[1] ?? null;
    }

    private function paid(Event $event, int $quantity, int $total): Reservation
    {
        $type = $event->ticketTypes()->create([
            'name' => 'Standard', 'price' => $total, 'quantity' => 50, 'is_active' => true,
        ]);

        return Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
            'customer_name' => 'Client',
            'customer_email' => 'client@test.sn',
            'quantity' => $quantity,
            'unit_price' => (int) round($total / $quantity),
            'total_amount' => $total,
            'payment_status' => Reservation::STATUS_PAID,
            'channel' => Reservation::CHANNEL_ONLINE,
            'paid_at' => now(),
        ]);
    }

    public function test_the_filter_form_proposes_organizers_and_events(): void
    {
        $content = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('name="organizer_id"', $content);
        $this->assertStringContainsString('name="event_id"', $content);
        $this->assertStringContainsString($this->organizerA->full_name, $content);
        $this->assertStringContainsString($this->organizerB->full_name, $content);
        $this->assertStringContainsString('Événement b-un', $content);
    }

    public function test_an_organizer_filter_narrows_the_stats_to_its_events(): void
    {
        $this->paid($this->eventA1, 2, 10000);
        $this->paid($this->eventA2, 1, 3000);
        $this->paid($this->eventB1, 3, 9000);

        $content = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['organizer_id' => $this->organizerA->id]))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('13 000 FCFA', $content);
        $this->assertStringNotContainsString('22 000 FCFA', $content);
        $this->assertStringContainsString('Événement a-un', $content);
        $this->assertStringContainsString('Événement a-deux', $content);
    }

    public function test_an_event_filter_narrows_the_stats_to_that_event(): void
    {
        $this->paid($this->eventA1, 2, 10000);
        $this->paid($this->eventA2, 1, 3000);
        $this->paid($this->eventB1, 3, 9000);

        // L'organisateur A cumule 13 000 FCFA : filtrer sur a-un uniquement doit
        // exclure la part de a-deux.
        $content = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['organizer_id' => $this->organizerA->id, 'event_id' => $this->eventA1->id]))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('10 000 FCFA', $content);
        $this->assertStringNotContainsString('13 000 FCFA', $content);
    }

    public function test_an_event_outside_the_selected_organizer_pulls_back_to_the_organizer(): void
    {
        $this->paid($this->eventA1, 2, 10000);
        $this->paid($this->eventB1, 3, 9000);

        // L'événement B1 n'appartient pas à l'organisateur A : le filtre retombe
        // sur l'organisateur A, donc B1 (9 000) est exclu du total global (19 000).
        $content = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['organizer_id' => $this->organizerA->id, 'event_id' => $this->eventB1->id]))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('10 000 FCFA', $content);
        $this->assertStringNotContainsString('19 000 FCFA', $content);
    }
}
