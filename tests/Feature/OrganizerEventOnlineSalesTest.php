<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La case « Activer la vente en ligne » du formulaire organisateur
 * doit se désactiver et se réactiver correctement à l'enregistrement.
 */
class OrganizerEventOnlineSalesTest extends TestCase
{
    use RefreshDatabase;

    private function organizer(): User
    {
        return User::factory()->organizer()->create();
    }

    private function eventFor(User $organizer, bool $onlineSalesEnabled = true): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Festival Test',
            'slug' => Event::slugFor('festival-test-'.uniqid()),
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'event_type' => 'festival',
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => $onlineSalesEnabled,
        ]);
    }

    private function payload(Event $event, ?bool $onlineSalesEnabled = null): array
    {
        $data = [
            'title' => $event->title,
            'event_date' => $event->event_date->format('Y-m-d\TH:i'),
            'location' => $event->location,
            'event_type' => 'festival',
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ];

        if ($onlineSalesEnabled !== null) {
            $data['online_sales_enabled'] = $onlineSalesEnabled ? '1' : '0';
        }

        return $data;
    }

    public function test_the_event_form_exposes_the_online_sales_checkbox(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $html = $this->actingAs($organizer)
            ->get(route('organizer.events.edit', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="online_sales_enabled"', $html);
        $this->assertStringContainsString('Activer la vente en ligne', $html);
    }

    public function test_an_organizer_can_disable_online_sales(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer, true);

        $this->actingAs($organizer)
            ->put(route('organizer.events.update', $event), $this->payload($event, false))
            ->assertRedirect(route('organizer.events.index'));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'online_sales_enabled' => false,
        ]);
    }

    public function test_an_organizer_can_enable_online_sales(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer, false);

        $this->actingAs($organizer)
            ->put(route('organizer.events.update', $event), $this->payload($event, true))
            ->assertRedirect(route('organizer.events.index'));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'online_sales_enabled' => true,
        ]);
    }
}
