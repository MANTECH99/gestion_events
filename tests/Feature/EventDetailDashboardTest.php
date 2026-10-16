<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Event;
use App\Models\EventReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Les tables de détail créées par add_event_detail_features (artistes,
 * galerie, avis) et le profil vérifié se pilotent depuis les dashboards.
 */
class EventDetailDashboardTest extends TestCase
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
            'title' => 'Concert Détail',
            'slug' => Event::slugFor('concert-detail-'.uniqid()),
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'event_type' => 'concert',
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);
    }

    public function test_event_edit_form_exposes_the_detail_fields(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);
        $artist = Artist::create(['name' => 'Awa Star']);

        $html = $this->actingAs($organizer)
            ->get(route('organizer.events.edit', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="duration_minutes"', $html);
        $this->assertStringContainsString('name="tags"', $html);
        $this->assertStringContainsString('name="venue_image"', $html);
        $this->assertStringContainsString('name="artist_id"', $html);
        $this->assertStringContainsString('name="avatar"', $html);
        $this->assertStringContainsString($artist->name, $html);
    }

    public function test_the_event_form_never_nests_the_gallery_forms(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $html = $this->actingAs($organizer)
            ->get(route('organizer.events.edit', $event))
            ->assertOk()
            ->getContent();

        $start = strpos($html, 'action="'.route('organizer.events.update', $event).'"');
        $this->assertNotFalse($start, 'Le formulaire principal de modification doit exister.');

        $main = substr($html, $start, strpos($html, '</form>', $start) - $start);

        $this->assertStringNotContainsString(
            'organizer.events.images',
            $main,
            'Les formulaires de la galerie ne doivent jamais être imbriqués dans le formulaire principal.'
        );
    }

    public function test_organizer_uploads_a_profile_photo_shown_on_the_event_page(): void
    {
        Storage::fake('public');

        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $this->actingAs($organizer)->put(route('organizer.events.update', $event), [
            'title' => $event->title,
            'event_date' => $event->event_date->format('Y-m-d\TH:i'),
            'location' => $event->location,
            'event_type' => 'concert',
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'avatar' => UploadedFile::fake()->create('profil.jpg', 50, 'image/jpeg'),
        ])->assertRedirect(route('organizer.events.index'));

        $organizer->refresh();
        $this->assertNotNull($organizer->avatar_path);
        Storage::disk('public')->assertExists($organizer->avatar_path);

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($organizer->avatar_url, $html);
    }

    public function test_the_event_page_falls_back_to_initials_without_a_photo(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $html = $this->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($organizer->initials, $html);
    }

    public function test_organizer_saves_the_new_event_details(): void
    {
        Storage::fake('public');

        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);
        $artist = Artist::create(['name' => 'Awa Star']);

        $this->actingAs($organizer)->put(route('organizer.events.update', $event), [
            'title' => $event->title,
            'event_date' => $event->event_date->format('Y-m-d\TH:i'),
            'location' => $event->location,
            'event_type' => 'concert',
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'duration_minutes' => 150,
            'tags' => 'afrobeat, plein air',
            'artist_id' => $artist->id,
            'venue_image' => UploadedFile::fake()->create('lieu.jpg', 60, 'image/jpeg'),
        ])->assertRedirect(route('organizer.events.index'));

        $event->refresh();

        $this->assertSame(150, $event->duration_minutes);
        $this->assertSame('afrobeat, plein air', $event->tags);
        $this->assertSame($artist->id, $event->artist_id);
        $this->assertNotNull($event->venue_image_path);
        Storage::disk('public')->assertExists($event->venue_image_path);
    }

    public function test_artist_crud_from_the_dashboard(): void
    {
        Storage::fake('public');

        $organizer = $this->organizer();

        $this->actingAs($organizer)->post(route('organizer.artists.store'), [
            'name' => 'Dip Doundou',
            'title' => 'Chanteur',
            'bio' => 'Une bio de test.',
            'image' => UploadedFile::fake()->create('artiste.jpg', 40, 'image/jpeg'),
        ])->assertRedirect(route('organizer.artists.index'));

        $artist = Artist::where('name', 'Dip Doundou')->firstOrFail();
        Storage::disk('public')->assertExists($artist->image_path);

        $this->actingAs($organizer)
            ->get(route('organizer.artists.index'))
            ->assertOk()
            ->assertSee('Dip Doundou');

        $this->actingAs($organizer)->put(route('organizer.artists.update', $artist), [
            'name' => 'Dip Doundou',
            'title' => 'Auteur-compositeur',
            'bio' => 'Bio mise à jour.',
        ])->assertRedirect(route('organizer.artists.index'));

        $this->assertSame('Auteur-compositeur', $artist->fresh()->title);

        $this->actingAs($organizer)
            ->delete(route('organizer.artists.destroy', $artist))
            ->assertRedirect(route('organizer.artists.index'));

        $this->assertDatabaseMissing('artists', ['id' => $artist->id]);
    }

    public function test_gallery_images_can_be_added_reordered_and_removed(): void
    {
        Storage::fake('public');

        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $this->actingAs($organizer)->post(route('organizer.events.images.store', $event), [
            'images' => [
                UploadedFile::fake()->create('g1.jpg', 30, 'image/jpeg'),
                UploadedFile::fake()->create('g2.jpg', 30, 'image/jpeg'),
            ],
        ])->assertRedirect();

        $images = $event->images()->get();
        $this->assertCount(2, $images);

        $second = $images[1];

        $this->actingAs($organizer)
            ->post(route('organizer.events.images.move', [$event, $second]), ['direction' => 'up'])
            ->assertRedirect();

        $this->assertSame($second->id, $event->images()->first()->id);

        $this->actingAs($organizer)
            ->delete(route('organizer.events.images.destroy', [$event, $second]))
            ->assertRedirect();

        $this->assertDatabaseMissing('event_images', ['id' => $second->id]);
    }

    public function test_another_organizer_cannot_touch_the_gallery(): void
    {
        Storage::fake('public');

        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $this->actingAs($organizer)->post(route('organizer.events.images.store', $event), [
            'images' => [UploadedFile::fake()->create('g1.jpg', 30, 'image/jpeg')],
        ]);

        $image = $event->images()->firstOrFail();

        $this->actingAs($this->organizer())
            ->delete(route('organizer.events.images.destroy', [$event, $image]))
            ->assertForbidden();

        $this->assertDatabaseHas('event_images', ['id' => $image->id]);
    }

    public function test_reviews_are_listed_and_can_be_moderated(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $review = EventReview::create([
            'event_id' => $event->id,
            'author_name' => 'Client Satisfait',
            'rating' => 5,
            'comment' => 'Super soirée !',
        ]);

        $this->actingAs($organizer)
            ->get(route('organizer.reviews.index'))
            ->assertOk()
            ->assertSee('Super soirée');

        $this->actingAs($organizer)
            ->delete(route('organizer.reviews.destroy', $review))
            ->assertRedirect();

        $this->assertDatabaseMissing('event_reviews', ['id' => $review->id]);
    }

    public function test_a_foreign_organizer_cannot_delete_the_review(): void
    {
        $organizer = $this->organizer();
        $event = $this->eventFor($organizer);

        $review = EventReview::create([
            'event_id' => $event->id,
            'author_name' => 'Client',
            'rating' => 3,
        ]);

        $this->actingAs($this->organizer())
            ->delete(route('organizer.reviews.destroy', $review))
            ->assertForbidden();

        $this->assertDatabaseHas('event_reviews', ['id' => $review->id]);
    }

    public function test_admin_toggles_the_verified_badge(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = $this->organizer();

        $this->actingAs($admin)
            ->patch(route('admin.users.verify', $organizer))
            ->assertRedirect();

        $this->assertTrue($organizer->fresh()->is_verified);

        $this->actingAs($admin)
            ->patch(route('admin.users.verify', $organizer))
            ->assertRedirect();

        $this->assertFalse($organizer->fresh()->is_verified);
    }

    public function test_an_organizer_cannot_verify_anyone(): void
    {
        $organizer = $this->organizer();
        $target = $this->organizer();

        $this->actingAs($organizer)
            ->patch(route('admin.users.verify', $target))
            ->assertRedirect(route('home'));

        $this->assertFalse($target->fresh()->is_verified);
    }
}
