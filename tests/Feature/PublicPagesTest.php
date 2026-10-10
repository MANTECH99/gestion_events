<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $overrides = []): Event
    {
        $organizer = User::factory()->organizer()->create();

        return Event::create([
            'title' => 'Evenement Test',
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 500,
            ...$overrides,
            'organizer_id' => $organizer->id,
            'slug' => Event::slugFor($overrides['title'] ?? 'evenement-test'),
            'status' => $overrides['status'] ?? Event::STATUS_PUBLISHED,
            'online_sales_enabled' => $overrides['online_sales_enabled'] ?? true,
        ]);
    }

    public function test_home_page_renders(): void
    {
        $this->get('/')->assertOk()->assertSee('Billeterie Express');
    }

    public function test_home_page_lists_published_events(): void
    {
        $event = $this->event(['title' => 'Festival Jazz', 'location' => 'Dakar', 'event_date' => now()->addMonth()]);

        $this->get('/')->assertOk()->assertSee('Festival Jazz');
    }

    public function test_home_page_hides_draft_events(): void
    {
        $this->event(['title' => 'Evenement Secret', 'status' => Event::STATUS_DRAFT]);

        $this->get('/')->assertOk()->assertDontSee('Evenement Secret');
    }

    public function test_event_show_renders(): void
    {
        $event = $this->event(['title' => 'Grand Concert', 'location' => 'Stade Demba Diop']);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Grand Concert')
            ->assertSee('Stade Demba Diop');
    }

    public function test_event_show_returns_404_for_unknown_slug(): void
    {
        $this->get('/evenements/inexistant')->assertNotFound();
    }

    public function test_auth_pages_render(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    public function test_booking_form_renders_for_published_event(): void
    {
        $event = $this->event(['title' => 'Soiree Test']);
        $event->ticketTypes()->create(['name' => 'Standard', 'price' => 5000, 'quantity' => 100]);

        $this->get(route('booking.create', $event))->assertOk()->assertSee('Standard');
    }

    public function test_booking_form_redirects_when_event_is_past(): void
    {
        $event = $this->event(['event_date' => now()->subDay()]);

        $this->get(route('booking.create', $event))->assertRedirect(route('events.show', $event));
    }

    public function test_pwa_endpoints_are_reachable(): void
    {
        $this->get(route('pwa.manifest'))
            ->assertOk()
            ->assertHeader('content-type', 'application/manifest+json');

        $this->get(route('pwa.offline'))->assertOk();
    }

    public function test_service_worker_is_served_with_js_content_type(): void
    {
        $this->get(route('pwa.sw'))
            ->assertOk()
            ->assertHeader('content-type', 'application/javascript');
    }

    public function test_guest_is_redirected_to_login_for_protected_pages(): void
    {
        $this->get(route('reservations.index'))->assertRedirect(route('login'));
        $this->get(route('organizer.dashboard'))->assertRedirect(route('login'));
        $this->get(route('pos.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
