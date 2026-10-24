<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La biographie de l'organisateur se saisit depuis le formulaire événement
 * et se lit dans le bloc Organisateur de la page publique.
 */
class OrganizerEventBioTest extends TestCase
{
    use RefreshDatabase;

    private function organizer(): User
    {
        return User::factory()->organizer()->create();
    }

    private function eventFor(User $organizer): Event
    {
        return Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Concert Bio',
            'slug' => Event::slugFor('concert-bio-'.uniqid()),
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'event_type' => 'concert',
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);
    }

    private function payload(Event $event, string $bio): array
    {
        return [
            'title' => $event->title,
            'event_date' => $event->event_date->format('Y-m-d\TH:i'),
            'location' => $event->location,
            'event_type' => 'concert',
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'organizer_bio' => $bio,
        ];
    }

    public function test_the_event_form_exposes_the_organizer_bio_field(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $html = $this->actingAs($organizer)
            ->get(route('organizer.events.edit', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="organizer_bio"', $html);
        $this->assertStringContainsString("Biographie de l'organisateur", $html);
    }

    public function test_the_organizer_bio_is_saved_from_the_event_form(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $this->actingAs($organizer)
            ->put(route('organizer.events.update', $event), $this->payload(
                $event,
                'Arena Events produit les plus grands concerts et festivals du Sénégal depuis 2018.'
            ))
            ->assertRedirect(route('organizer.events.index'));

        $this->assertDatabaseHas('users', [
            'id' => $organizer->id,
            'bio' => 'Arena Events produit les plus grands concerts et festivals du Sénégal depuis 2018.',
        ]);
    }

    public function test_clearing_the_field_removes_the_bio(): void
    {
        $organizer = $this->organizer();
        $organizer->update(['bio' => 'Ancienne bio']);
        $event = $this->eventFor($organizer);

        $this->actingAs($organizer)
            ->put(route('organizer.events.update', $event), $this->payload($event, ''))
            ->assertRedirect(route('organizer.events.index'));

        $this->assertNull($organizer->refresh()->bio);
    }

    public function test_the_public_event_page_displays_the_bio(): void
    {
        $organizer = $this->organizer();
        $organizer->update(['bio' => 'Arena Events produit les plus grands concerts et festivals du Sénégal depuis 2018.']);
        $event = $this->eventFor($organizer);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Arena Events produit les plus grands concerts');
    }
}
