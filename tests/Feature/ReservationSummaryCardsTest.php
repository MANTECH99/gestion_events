<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationSummaryCardsTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->organizer()->create();
    }

    private function paid(Event $event, int $quantity, int $total, string $channel): void
    {
        Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $event->ticketTypes()->create([
                'name' => 'Standard', 'price' => $total, 'quantity' => 50, 'is_active' => true,
            ])->id,
            'customer_name' => 'Client',
            'customer_email' => 'client@test.sn',
            'quantity' => $quantity,
            'unit_price' => (int) round($total / $quantity),
            'total_amount' => $total,
            'payment_status' => Reservation::STATUS_PAID,
            'channel' => $channel,
            'paid_at' => now(),
        ]);
    }

    private function event(string $slug): Event
    {
        return Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Événement '.$slug,
            'slug' => $slug,
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ]);
    }

    public function test_the_four_summary_cards_are_shown(): void
    {
        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.reservations.index'))
            ->assertOk();

        $response->assertSee('Revenus encaissés');
        $response->assertSee('Tickets vendus');
        $response->assertSee('Billets valides');
        $response->assertSee("Validations à l'entrée");
    }

    public function test_the_cards_sum_every_event_by_default(): void
    {
        $this->paid($this->event('concert-acoustique'), 2, 10000, Reservation::CHANNEL_ONLINE);
        $this->paid($this->event('grand-derby'), 3, 5000, Reservation::CHANNEL_PHYSICAL);

        $content = $this->actingAs($this->organizer)
            ->get(route('organizer.reservations.index'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('15 000 FCFA', $content);
        $this->assertStringContainsString('2 en ligne / 3 en physique', $content);
        $this->assertStringContainsString('10 000 F en ligne · 5 000 F en physique', $content);
    }

    public function test_the_cards_follow_the_selected_event(): void
    {
        $online = $this->event('concert-acoustique');
        $this->paid($online, 2, 10000, Reservation::CHANNEL_ONLINE);
        $this->paid($this->event('grand-derby'), 3, 5000, Reservation::CHANNEL_PHYSICAL);

        $content = $this->actingAs($this->organizer)
            ->get(route('organizer.reservations.index', ['event_id' => $online->id]))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('10 000 FCFA', $content);
        $this->assertStringContainsString('2 en ligne / 0 en physique', $content);
        $this->assertStringContainsString('10 000 F en ligne · 0 F en physique', $content);
        $this->assertStringNotContainsString('15 000 FCFA', $content);
    }

    public function test_an_organizer_cannot_read_another_organizer_event(): void
    {
        $this->paid($this->event('mon-evenement'), 2, 10000, Reservation::CHANNEL_ONLINE);

        $other = User::factory()->organizer()->create();
        $foreign = Event::create([
            'organizer_id' => $other->id,
            'title' => 'Autre événement',
            'slug' => 'autre-evenement',
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ]);
        $this->paid($foreign, 5, 50000, Reservation::CHANNEL_ONLINE);

        $content = $this->actingAs($this->organizer)
            ->get(route('organizer.reservations.index', ['event_id' => $foreign->id]))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('0 en ligne / 0 en physique', $content);
        $this->assertStringNotContainsString('50 000 FCFA', $content);
    }
}
