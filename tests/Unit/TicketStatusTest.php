<?php

namespace Tests\Unit;

use App\Models\Payout;
use App\Models\Reservation;
use App\Models\Ticket;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Les badges sont rendus via des classes Tailwind construites dynamiquement
 * (bg-{{ $model->status_color }}-100). Une palette absente de app.css produit
 * un badge sans style : on verrouille donc le couple statut -> libelle/palette.
 */
class TicketStatusTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string, 2: string}> */
    public static function ticketStatuses(): array
    {
        return [
            'valide' => [Ticket::STATUS_VALID, 'Valide', 'emerald'],
            'utilisé' => [Ticket::STATUS_USED, 'Utilisé', 'sky'],
            'remboursé' => [Ticket::STATUS_REFUNDED, 'Remboursé', 'rose'],
            'annulé' => [Ticket::STATUS_CANCELLED, 'Annulé', 'rose'],
            'inconnu' => ['expire', 'Expire', 'amber'],
        ];
    }

    #[DataProvider('ticketStatuses')]
    public function test_it_exposes_a_label_and_a_badge_color(string $status, string $label, string $color): void
    {
        $ticket = new Ticket(['status' => $status]);

        $this->assertSame($label, $ticket->status_label);
        $this->assertSame($color, $ticket->status_color);
    }

    public function test_every_badge_color_is_declared_in_the_css_source(): void
    {
        $allowed = $this->declaredClasses(dirname(__DIR__, 2).'/resources/css/app.css');

        $colors = [
            ...$this->colorsFor(Ticket::class, ['valid', 'used', 'refunded', 'cancelled', 'inconnu']),
            ...$this->colorsFor(Reservation::class, ['pending', 'paid', 'failed', 'refunded', 'expired']),
            ...$this->colorsFor(Payout::class, ['PENDING', 'PROCESSING', 'COMPLETED', 'FAILED', 'CANCELLED']),
        ];

        $this->assertNotEmpty($colors);

        foreach (array_unique($colors) as $color) {
            // Les vues utilisent bg-{color}-100 text-{color}-700 et bg-{color}-500.
            foreach (['bg-'.$color.'-100', 'text-'.$color.'-700', 'bg-'.$color.'-500'] as $class) {
                $this->assertContains(
                    $class,
                    $allowed,
                    sprintf('La classe %s doit être déclarée dans resources/css/app.css', $class)
                );
            }
        }
    }

    /**
     * Toutes les palettes renvoyées par un accesseur status_color.
     *
     * @param  class-string  $model
     * @param  list<string>  $statuses
     * @return list<string>
     */
    private function colorsFor(string $model, array $statuses): array
    {
        $attribute = $model === Reservation::class ? 'payment_status' : 'status';

        return array_map(
            fn (string $status) => (new $model)->setAttribute($attribute, $status)->status_color,
            $statuses
        );
    }

    /**
     * Classes autorisées par les déclarations @source inline de Tailwind.
     *
     * @return list<string>
     */
    private function declaredClasses(string $cssPath): array
    {
        $css = file_get_contents($cssPath);
        $this->assertIsString($css);

        preg_match_all("/@source inline\('([^']+)'\)/", $css, $matches);

        $classes = [];

        foreach ($matches[1] as $pattern) {
            foreach ($this->expand($pattern) as $class) {
                $classes[] = $class;
            }
        }

        $this->assertNotEmpty($classes, 'Aucune déclaration @source inline trouvée.');

        return $classes;
    }

    /** Produit cartésien des listes {a,b}-{c,d}. @return list<string> */
    private function expand(string $pattern): array
    {
        if (! preg_match('/\{([^{}]+)\}/', $pattern, $matches)) {
            return [$pattern];
        }

        $results = [];

        foreach (explode(',', $matches[1]) as $value) {
            foreach ($this->expand(str_replace($matches[0], $value, $pattern)) as $expanded) {
                $results[] = $expanded;
            }
        }

        return $results;
    }
}
