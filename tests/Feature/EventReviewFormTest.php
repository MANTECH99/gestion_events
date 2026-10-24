<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Avis publics de la page événement : ouvert à tous, compte ou non
 * (le nom du compte remplace alors le nom saisi par l'invité).
 */
class EventReviewFormTest extends TestCase
{
    use RefreshDatabase;

    private function event(): Event
    {
        $organizer = User::factory()->organizer()->create();

        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Concert Avis',
            'slug' => Event::slugFor('concert-avis-'.uniqid()),
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'event_type' => 'concert',
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);
    }

    public function test_the_event_page_shows_the_review_form(): void
    {
        $event = $this->event();

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="rating"', $html);
        $this->assertStringContainsString('name="author_name"', $html);
        $this->assertStringContainsString('name="comment"', $html);
        $this->assertStringContainsString('Publier mon avis', $html);
    }

    public function test_a_guest_can_publish_a_review_with_their_name(): void
    {
        $event = $this->event();

        $this->withHeader('referer', route('events.show', $event))
            ->post(route('events.reviews.store', $event), [
                'rating' => 5,
                'author_name' => 'Fatou Ndiaye',
                'comment' => 'Super soirée !',
            ])
            ->assertRedirect(route('events.show', $event))
            ->assertSessionHas('review_success');

        $this->assertDatabaseHas('event_reviews', [
            'event_id' => $event->id,
            'user_id' => null,
            'author_name' => 'Fatou Ndiaye',
            'rating' => 5,
            'comment' => 'Super soirée !',
        ]);
    }

    public function test_a_logged_in_review_uses_the_account_name(): void
    {
        $event = $this->event();
        $user = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Fall']);

        $this->actingAs($user)
            ->withHeader('referer', route('events.show', $event))
            ->post(route('events.reviews.store', $event), [
                'rating' => 4,
                'author_name' => 'Nom usurpé',
            ])
            ->assertSessionHas('review_success');

        $this->assertDatabaseHas('event_reviews', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'author_name' => 'Aminata Fall',
            'rating' => 4,
            'comment' => null,
        ]);
    }

    public function test_rating_and_name_are_required_and_validated(): void
    {
        $event = $this->event();

        $this->withHeader('referer', route('events.show', $event))
            ->post(route('events.reviews.store', $event), [
                'rating' => 0,
                'author_name' => '',
            ])
            ->assertSessionHasErrors(['rating', 'author_name']);

        $this->withHeader('referer', route('events.show', $event))
            ->post(route('events.reviews.store', $event), [
                'rating' => 6,
                'author_name' => 'Fatou Ndiaye',
            ])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('event_reviews', 0);
    }
}
