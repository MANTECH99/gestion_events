<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Thème des espaces de gestion organisateur/administrateur :
 * sombre par défaut sur toutes les pages, bascule vers l'ancien
 * rendu blanc via le cookie « theme ».
 */
class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_dashboard_is_dark_by_default(): void
    {
        $html = $this->actingAs(User::factory()->organizer()->create())
            ->get(route('organizer.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<main class="flex-1 bg-ink-950"', $html);
        $this->assertStringContainsString('input-dark', $html);
        $this->assertStringContainsString('label-dark', $html);
        $this->assertStringNotContainsString('class="input w-auto', $html);
        // En sombre, la bascule propose le retour au clair.
        $this->assertStringContainsString('title="Passer en mode clair"', $html);
        $this->assertStringNotContainsString('title="Passer en mode sombre"', $html);
    }

    public function test_admin_dashboard_is_dark_by_default(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<main class="flex-1 bg-ink-950"', $html);
        $this->assertStringContainsString('input-dark', $html);
        $this->assertStringContainsString('title="Passer en mode clair"', $html);
    }

    public function test_theme_cookie_renders_the_legacy_light_dashboard(): void
    {
        $html = $this->actingAs(User::factory()->organizer()->create())
            ->withCookie('theme', 'light')
            ->get(route('organizer.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('<main class="flex-1 bg-ink-950"', $html);
        $this->assertStringNotContainsString('input-dark', $html);
        $this->assertStringContainsString('class="input w-auto', $html);
        // En clair, la bascule propose le sombre.
        $this->assertStringContainsString('title="Passer en mode sombre"', $html);
    }

    public function test_all_backoffice_pages_are_dark_by_default(): void
    {
        $organizer = User::factory()->organizer()->create();

        foreach (['organizer.events.index', 'organizer.reservations.index', 'organizer.stocks.index'] as $route) {
            $html = $this->actingAs($organizer)->get(route($route))->assertOk()->getContent();

            $this->assertStringContainsString('class="h-full dark"', $html, $route);
            $this->assertStringContainsString('<main class="flex-1 bg-ink-950"', $html, $route);
            $this->assertStringContainsString('title="Passer en mode clair"', $html, $route);
        }

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="h-full dark"', $html);
        $this->assertStringContainsString('<main class="flex-1 bg-ink-950"', $html);
    }

    public function test_the_light_cookie_renders_the_legacy_light_backoffice(): void
    {
        $html = $this->actingAs(User::factory()->organizer()->create())
            ->withCookie('theme', 'light')
            ->get(route('organizer.events.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('class="h-full dark"', $html);
        $this->assertStringNotContainsString('<main class="flex-1 bg-ink-950"', $html);
        $this->assertStringContainsString('title="Passer en mode sombre"', $html);
    }

    public function test_the_toggle_button_only_exists_on_backoffice_pages(): void
    {
        $action = 'action="'.route('theme.update').'"';
        $organizer = User::factory()->organizer()->create();

        $dashboard = $this->actingAs($organizer)
            ->get(route('organizer.dashboard'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($action, $dashboard);

        $events = $this->actingAs($organizer)
            ->get(route('organizer.events.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($action, $events);

        // Ni sur les pages publiques…
        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringNotContainsString($action, $home);

        // …ni dans l'espace client.
        $reservations = $this->actingAs($organizer)
            ->get(route('reservations.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString($action, $reservations);
    }

    public function test_toggle_queues_the_opposite_theme_cookie(): void
    {
        // Absence de cookie (sombre par défaut) → bascule vers le clair.
        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('theme.update'))
            ->assertRedirect()
            ->assertCookie('theme', 'light');

        // Cookie clair → retour au sombre.
        $this->withCookie('theme', 'light')
            ->post(route('theme.update'))
            ->assertRedirect()
            ->assertCookie('theme', 'dark');
    }

    public function test_toggle_requires_authentication(): void
    {
        $this->post(route('theme.update'))->assertRedirect(route('login'));
    }
}
