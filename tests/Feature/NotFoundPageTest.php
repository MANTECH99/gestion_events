<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_url_renders_the_404_page(): void
    {
        $response = $this->get('/cette-page-nexiste-pas');

        $response->assertNotFound();
        $response->assertSee('Page introuvable');
        $response->assertSee('La page ou l\'événement que vous cherchez n\'existe pas ou a été supprimé.', false);
        $response->assertSee('Retour à l\'accueil', false);
        $response->assertSee('Page précédente', false);
    }

    public function test_an_unknown_event_slug_renders_the_404_page(): void
    {
        $response = $this->get(route('events.show', 'evenement-qui-nexiste-pas'));

        $response->assertNotFound();
        $response->assertSee('Page introuvable');
        $response->assertSee('Retour à l\'accueil', false);
        $response->assertDontSee("{{ route('home') }}", false);
    }

    public function test_a_back_office_404_uses_the_same_page(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.organizers.show', $customer));

        $response->assertNotFound();
        $response->assertSee('Page introuvable');
        $response->assertSee('Page précédente', false);
    }

    public function test_the_404_page_keeps_a_valid_event_reachable(): void
    {
        $organizer = User::factory()->organizer()->create();

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Concert rendu',
            'slug' => 'concert-rendu',
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Concert rendu');
    }
}
