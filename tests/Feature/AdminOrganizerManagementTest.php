<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrganizerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_edit_form_spoofs_put(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = User::factory()->organizer()->create();

        $content = $this->actingAs($admin)
            ->get(route('admin.organizers.index'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('name="_method" value="PUT"', $content);
        $this->assertStringContainsString('action="'.route('admin.organizers.update', $organizer).'"', $content);
    }

    public function test_an_organizer_can_be_updated(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($admin)->put(route('admin.organizers.update', $organizer), [
            'first_name' => 'Moussa',
            'last_name' => 'Diop',
            'username' => 'moussa_diop',
            'email' => 'moussa@test.sn',
            'phone' => '+221770000000',
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertSame('Moussa', $organizer->fresh()->first_name);
        $this->assertSame('moussa@test.sn', $organizer->fresh()->email);
    }

    public function test_an_organizer_can_be_created_with_spaces_in_the_username(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.organizers.store'), [
            'first_name' => 'Galsen',
            'last_name' => 'Events',
            'username' => 'Galsen events',
            'email' => 'galsen.events@test.sn',
            'phone' => '+221770000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['username' => 'Galsen events']);
    }

    public function test_an_organizer_username_rejects_invalid_characters(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.organizers.store'), [
            'first_name' => 'Galsen',
            'last_name' => 'Events',
            'username' => 'Galsen@events',
            'email' => 'galsen.events@test.sn',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertDatabaseMissing('users', ['username' => 'Galsen@events']);
    }

    public function test_admin_can_view_an_organizer_detail_page(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = User::factory()->organizer()->create([
            'first_name' => 'Moussa',
            'last_name' => 'Diop',
        ]);
        User::factory()->pointOfSale($organizer)->create();
        User::factory()->agent($organizer)->create();

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Grand derby ASKO',
            'slug' => 'grand-derby-asko',
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $type = $event->ticketTypes()->create([
            'name' => 'Standard', 'price' => 13000, 'quantity' => 50, 'is_active' => true,
        ]);

        Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
            'customer_name' => 'Client',
            'customer_email' => 'client@test.sn',
            'quantity' => 3,
            'unit_price' => 13000,
            'total_amount' => 39000,
            'payment_status' => Reservation::STATUS_PAID,
            'channel' => Reservation::CHANNEL_ONLINE,
            'paid_at' => now(),
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.organizers.show', $organizer))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('Moussa Diop', $content);
        $this->assertStringContainsString('Événements (1)', $content);
        $this->assertStringContainsString('Points de vente (1)', $content);
        $this->assertStringContainsString('Agents (1)', $content);
        $this->assertStringContainsString('Billets vendus', $content);
        $this->assertStringContainsString('39 000 FCFA', $content);
        $this->assertStringContainsString('Grand derby ASKO', $content);
    }

    public function test_organizer_detail_shows_empty_states_when_there_is_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = User::factory()->organizer()->create();

        $content = $this->actingAs($admin)
            ->get(route('admin.organizers.show', $organizer))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('Événements (0)', $content);
        $this->assertStringContainsString('Points de vente (0)', $content);
        $this->assertStringContainsString('Agents (0)', $content);
        $this->assertStringContainsString('Aucun événement', $content);
        $this->assertStringContainsString('Aucun point de vente', $content);
        $this->assertStringContainsString('Aucun agent', $content);
    }

    public function test_viewing_a_non_organizer_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.organizers.show', $customer))
            ->assertNotFound();
    }
}
