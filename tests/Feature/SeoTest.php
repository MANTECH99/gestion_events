<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $overrides = []): Event
    {
        $organizer = User::factory()->organizer()->create();

        return Event::create([
            'title' => 'Grand Concert',
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 500,
            'description' => 'Une soirée inoubliable au cœur de Dakar.',
            'image_path' => 'events/cover.jpg',
            ...$overrides,
            'organizer_id' => $organizer->id,
            'slug' => Event::slugFor($overrides['title'] ?? 'grand-concert'),
            'status' => $overrides['status'] ?? Event::STATUS_PUBLISHED,
            'online_sales_enabled' => $overrides['online_sales_enabled'] ?? true,
        ]);
    }

    public function test_home_page_exposes_open_graph_and_website_schema(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('rel="canonical"', false)
            ->assertSee('og:site_name', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('SearchAction', false);
    }

    public function test_event_page_exposes_event_schema_and_cover_image(): void
    {
        $event = $this->event();
        $event->ticketTypes()->create(['name' => 'Standard', 'price' => 5000, 'quantity' => 100, 'is_active' => true]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('"@type":"Event"', false)
            ->assertSee('"@type":"Offer"', false)
            ->assertSee('"priceCurrency":"XOF"', false)
            ->assertSee('http://localhost/storage/events/cover.jpg', false)
            ->assertSee('rel="canonical"', false);
    }

    public function test_private_pages_are_marked_noindex(): void
    {
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->get(route('organizer.dashboard'))
            ->assertOk()
            ->assertSee('content="noindex, nofollow"', false);
    }

    public function test_robots_file_points_to_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /organisateur')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_sitemap_lists_home_and_published_events_only(): void
    {
        $published = $this->event(['title' => 'Festival Jazz']);
        $draft = $this->event(['title' => 'Evenement Secret', 'status' => Event::STATUS_DRAFT]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('events.show', $published), false)
            ->assertDontSee(route('events.show', $draft), false);
    }
}
