<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les interfaces de gestion utilisent une colonne de navigation à gauche
 * (et non une barre horizontale en tête de contenu).
 */
class BackofficeSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_dashboard_renders_the_sidebar_before_the_content(): void
    {
        $html = $this->actingAs(User::factory()->organizer()->create())
            ->get(route('organizer.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="sidebar-gestion"', $html);

        // La colonne doit précéder le contenu dans le flux.
        $sidebar = strpos($html, 'id="sidebar-gestion"');
        $heading = strpos($html, '<h1');

        $this->assertNotFalse($sidebar);
        $this->assertNotFalse($heading);
        $this->assertLessThan($heading, $sidebar, 'Le sidebar doit être rendu avant le contenu.');

        // L'entrée courante est signalée aux lecteurs d'écran et mise en évidence.
        $this->assertMatchesRegularExpression(
            '#<a[^>]*href="[^"]*organisateur[^"]*"[^>]*aria-current="page"#s',
            $html
        );
    }

    public function test_the_active_sidebar_entry_follows_the_current_page(): void
    {
        $organizer = User::factory()->organizer()->create();

        $events = $this->actingAs($organizer)->get(route('organizer.events.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<a[^>]*href="[^"]*evenements[^"]*"[^>]*aria-current="page"#s',
            $events
        );

        $reservations = $this->actingAs($organizer)->get(route('organizer.reservations.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<a[^>]*href="[^"]*reservations[^"]*"[^>]*aria-current="page"#s',
            $reservations
        );
    }

    public function test_sidebar_exposes_every_management_entry(): void
    {
        $html = $this->actingAs(User::factory()->organizer()->create())
            ->get(route('organizer.dashboard'))
            ->assertOk()
            ->getContent();

        foreach ([
            'Tableau de bord',
            'Événements',
            'Réservations',
            'Stock physique',
            'Utilisateurs',
            'Validations',
            'Rapports',
            'Retraits',
            'Sessions de paiement',
            'Nouvel événement',
        ] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_admin_pages_use_the_same_sidebar(): void
    {
        $admin = User::factory()->admin()->create();

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('id="sidebar-gestion"', $html);
        $this->assertStringContainsString('Organisateurs', $html);
        $this->assertMatchesRegularExpression(
            '#<a[^>]*href="[^"]*admin[^"]*"[^>]*aria-current="page"#s',
            $html
        );

        $users = $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<a[^>]*href="[^"]*utilisateurs[^"]*"[^>]*aria-current="page"#s',
            $users
        );
    }

    public function test_public_pages_are_left_untouched(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        // Aucun marqueur du tiroir mobile ni de la colonne de gestion.
        $this->assertStringNotContainsString('sidebar-gestion', $html);
        $this->assertStringNotContainsString('Supervision de la plateforme', $html);

        // Pas de conteneur deux colonnes autour du contenu public.
        $this->assertStringNotContainsString('lg:items-start lg:gap-8', $html);

        // Le header public n'est pas décalé.
        $this->assertStringNotContainsString('<div class="lg:pl-64">', $html);
    }

    public function test_management_pages_have_no_footer(): void
    {
        // Les pages publiques gardent leur footer.
        $this->assertStringContainsString(
            '<footer',
            $this->get(route('home'))->assertOk()->getContent()
        );

        $organizer = User::factory()->organizer()->create();
        $admin = User::factory()->admin()->create();
        $pos = User::factory()->pointOfSale($organizer)->create();

        $managementPages = [
            $this->get(route('agent.index'))->assertOk()->getContent(),
            $this->actingAs($organizer)->get(route('organizer.dashboard'))->assertOk()->getContent(),
            $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent(),
            $this->actingAs($pos)->get(route('pos.dashboard'))->assertOk()->getContent(),
        ];

        foreach ($managementPages as $html) {
            $this->assertStringNotContainsString('<footer', $html);
        }
    }

    public function test_the_sidebar_title_sits_on_the_header_band(): void
    {
        $html = $this->actingAs(User::factory()->organizer()->create())
            ->get(route('organizer.dashboard'))
            ->assertOk()
            ->getContent();

        // Colonne de haut en bas, et bloc titre calé sur la bande de 64 px du header.
        $this->assertMatchesRegularExpression('#id="sidebar-gestion"[^>]*class="[^"]*fixed bottom-0 left-0 top-0#s', $html);
        $this->assertMatchesRegularExpression('#id="sidebar-gestion".*?flex h-16 items-center#s', $html);

        // Le header est décalé pour rester visible à côté de la colonne.
        $this->assertStringContainsString('<div class="lg:pl-64">', $html);
    }

    public function test_the_dashboard_content_uses_the_full_width(): void
    {
        $html = $this->actingAs(User::factory()->organizer()->create())
            ->get(route('organizer.dashboard'))
            ->assertOk()
            ->getContent();

        // Aucun plafond de largeur : contenu, header et footer occupent tout l'espace.
        $this->assertStringNotContainsString('max-w-7xl', $html);
        $this->assertGreaterThanOrEqual(3, substr_count($html, 'w-full'));

        $home = $this->get(route('home'))->assertOk()->getContent();

        // Les pages publiques restent centrées.
        $this->assertStringContainsString('max-w-7xl', $home);
    }
}
