<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_an_active_account(): void
    {
        $this->post(route('register.store'), [
            'first_name' => 'Nouvelle',
            'last_name' => 'Client',
            'username' => 'nouvelle-client',
            'email' => 'nouvelle@test.sn',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ])->assertRedirect(route('reservations.index'));

        $user = User::where('email', 'nouvelle@test.sn')->firstOrFail();

        $this->assertTrue($user->is_active);
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_newly_registered_client_can_reach_their_reservations(): void
    {
        $this->post(route('register.store'), [
            'first_name' => 'Nouvelle',
            'last_name' => 'Client',
            'username' => 'nouvelle-client',
            'email' => 'nouvelle@test.sn',
            'password' => 'motdepasse123',
            'password_confirmation' => 'motdepasse123',
        ]);

        // Le compte créé est un client : il ne doit pas voir l'espace point de vente.
        $this->assertAuthenticated();
        $this->get(route('reservations.index'))->assertOk();
    }

    public function test_registration_requires_matching_passwords(): void
    {
        $this->post(route('register.store'), [
            'first_name' => 'Nouvelle',
            'last_name' => 'Client',
            'username' => 'nouvelle-client',
            'email' => 'nouvelle@test.sn',
            'password' => 'motdepasse123',
            'password_confirmation' => 'autrechose123',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        // Un compte de point de vente (rôle par défaut) est redirigé vers son espace.
        $user = User::factory()->create(['email' => 'client@test.sn']);

        $this->post(route('login.store'), [
            'email' => 'client@test.sn',
            'password' => 'password',
        ])->assertRedirect(route('reservations.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_redirects_each_role_to_its_own_space(): void
    {
        $organizer = User::factory()->organizer()->create([
            'email' => 'orga@test.sn',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), ['email' => 'orga@test.sn', 'password' => 'password'])
            ->assertRedirect(route('organizer.dashboard'));

        $this->post(route('logout'));

        $admin = User::factory()->admin()->create([
            'email' => 'admin@test.sn',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), ['email' => 'admin@test.sn', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->post(route('logout'));

        $agent = User::factory()->agent()->create([
            'email' => 'agent@test.sn',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), ['email' => 'agent@test.sn', 'password' => 'password'])
            ->assertRedirect(route('agent.index'));

        $this->post(route('logout'));

        $organizerForPos = User::factory()->organizer()->create();
        $pos = User::factory()->pointOfSale($organizerForPos)->create([
            'email' => 'pos@test.sn',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), ['email' => 'pos@test.sn', 'password' => 'password'])
            ->assertRedirect(route('pos.dashboard'));

        $this->assertAuthenticatedAs($pos);
        $this->assertNotNull($agent->id);
        $this->assertNotNull($organizer->id);
        $this->assertNotNull($admin->id);
    }

    public function test_login_fails_with_bad_credentials(): void
    {
        User::factory()->create(['email' => 'client@test.sn']);

        $this->post(route('login.store'), [
            'email' => 'client@test.sn',
            'password' => 'mauvais-mot-de-passe',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        User::factory()->inactive()->create(['email' => 'desactive@test.sn']);

        $this->post(route('login.store'), [
            'email' => 'desactive@test.sn',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
