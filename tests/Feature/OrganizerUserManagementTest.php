<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_organizer_can_toggle_an_agent(): void
    {
        // Régression : la route attendait {user} alors que le contrôleur type
        // User $target — le modèle lié était vide et l'action échouait.
        $organizer = User::factory()->organizer()->create();
        $pos = User::factory()->pointOfSale($organizer)->create();

        $this->actingAs($organizer)
            ->post(route('organizer.users.toggle-agent', $pos))
            ->assertRedirect();

        $this->assertTrue($pos->fresh()->isAgent());
    }

    public function test_an_organizer_can_deactivate_a_point_of_sale(): void
    {
        $organizer = User::factory()->organizer()->create();
        $pos = User::factory()->pointOfSale($organizer)->create();

        $this->actingAs($organizer)
            ->post(route('organizer.users.toggle-active', $pos))
            ->assertRedirect();

        $this->assertFalse($pos->fresh()->is_active);
    }

    public function test_an_organizer_can_delete_a_point_of_sale(): void
    {
        $organizer = User::factory()->organizer()->create();
        $pos = User::factory()->pointOfSale($organizer)->create();

        $this->actingAs($organizer)
            ->delete(route('organizer.users.destroy', $pos))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $pos->id]);
    }
}
