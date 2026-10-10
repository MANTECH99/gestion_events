<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Validation;
use App\Services\StatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrganizerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Event $event;

    private int $typeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->organizer()->create();

        $this->event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Gala Dashboard',
            'slug' => 'gala-dashboard',
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $this->typeId = $this->event->ticketTypes()->create([
            'name' => 'Standard', 'price' => 5000, 'quantity' => 100, 'is_active' => true,
        ])->id;
    }

    private function reservation(int $quantity, int $total, string $channel, Carbon $at, string $status = Reservation::STATUS_PAID): Reservation
    {
        $reservation = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $this->event->id,
            'ticket_type_id' => $this->typeId,
            'customer_name' => 'Client Test',
            'customer_email' => 'client@test.sn',
            'quantity' => $quantity,
            'unit_price' => (int) round($total / max($quantity, 1)),
            'total_amount' => $total,
            'payment_status' => $status,
            'channel' => $channel,
            'paid_at' => $at,
        ]);

        $reservation->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        return $reservation;
    }

    private function validation(string $status, Carbon $at): Validation
    {
        $validation = Validation::create([
            'event_id' => $this->event->id,
            'status' => $status,
            'message' => 'Test',
        ]);

        $validation->forceFill(['created_at' => $at, 'updated_at' => $at])->save();

        return $validation;
    }

    public function test_summary_breaks_sales_by_channel_and_validations_by_outcome(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $this->reservation(2, 5000, Reservation::CHANNEL_ONLINE, now());
        $this->reservation(5, 10000, Reservation::CHANNEL_PHYSICAL, now());
        $this->reservation(3, 3000, Reservation::CHANNEL_ONLINE, now(), Reservation::STATUS_PENDING);

        $this->validation(Validation::STATUS_VALID, now());
        $this->validation(Validation::STATUS_INVALID, now());
        $this->validation(Validation::STATUS_INVALID, now());

        $summary = app(StatsService::class)->summary(null, null, [$this->event->id]);

        $this->assertSame(7, $summary['total_tickets']);
        $this->assertSame(2, $summary['tickets_online']);
        $this->assertSame(5, $summary['tickets_physical']);

        $this->assertSame(15000, $summary['total_revenue']);
        $this->assertSame(5000, $summary['revenue_online']);
        $this->assertSame(10000, $summary['revenue_physical']);

        $this->assertSame(3, $summary['reservations']);
        $this->assertSame(2, $summary['paid_reservations']);
        $this->assertSame(1, $summary['reservations_online']);
        $this->assertSame(1, $summary['reservations_physical']);

        $this->assertSame(3, $summary['validations']);
        $this->assertSame(1, $summary['validations_valid']);
        $this->assertSame(2, $summary['validations_invalid']);
    }

    public function test_today_preset_excludes_previous_days(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $this->reservation(2, 5000, Reservation::CHANNEL_ONLINE, now());
        $this->reservation(5, 10000, Reservation::CHANNEL_PHYSICAL, now()->subDay());

        $this->actingAs($this->organizer)
            ->get(route('organizer.dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('2 en ligne / 0 en physique', false)
            ->assertSee('5 000 F en ligne · 0 F en physique', false);
    }

    public function test_all_preset_includes_every_channel(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $this->reservation(2, 5000, Reservation::CHANNEL_ONLINE, now());
        $this->reservation(5, 10000, Reservation::CHANNEL_PHYSICAL, now()->subDay());

        $this->actingAs($this->organizer)
            ->get(route('organizer.dashboard', ['period' => 'all']))
            ->assertOk()
            ->assertSee('2 en ligne / 5 en physique', false)
            ->assertSee('5 000 F en ligne · 10 000 F en physique', false);
    }

    public function test_a_valid_preset_wins_over_stale_from_and_to_parameters(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $this->reservation(2, 5000, Reservation::CHANNEL_ONLINE, now());
        $this->reservation(5, 10000, Reservation::CHANNEL_PHYSICAL, now()->subDay());

        // Le formulaire « preset » ne doit plus transmettre from/to : même si des
        // valeurs traînaient dans l'URL, le preset valide doit primer.
        $this->actingAs($this->organizer)
            ->get(route('organizer.dashboard', [
                'period' => 'today',
                'from' => now()->subDay()->toDateString(),
                'to' => now()->subDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('2 en ligne / 0 en physique', false)
            ->assertDontSee('0 en ligne / 5 en physique', false);
    }

    public function test_custom_dates_are_used_when_no_preset_is_given(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $this->reservation(2, 5000, Reservation::CHANNEL_ONLINE, now());
        $this->reservation(5, 10000, Reservation::CHANNEL_PHYSICAL, now()->subDay());

        $this->actingAs($this->organizer)
            ->get(route('organizer.dashboard', [
                'from' => now()->subDay()->toDateString(),
                'to' => now()->subDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('0 en ligne / 5 en physique', false)
            ->assertSee('0 F en ligne · 10 000 F en physique', false)
            ->assertSee('Période personnalisée', false);
    }

    public function test_new_period_presets_are_offered(): void
    {
        $response = $this->actingAs($this->organizer)
            ->get(route('organizer.dashboard'))
            ->assertOk();

        $response->assertSee('value="yesterday"', false)
            ->assertSee('value="week"', false)
            ->assertSee('value="month"', false)
            ->assertSee('Hier', false)
            ->assertSee('Cette semaine', false)
            ->assertSee('Ce mois-ci', false);
    }

    public function test_dashboard_defaults_to_all_periods(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $this->reservation(2, 5000, Reservation::CHANNEL_ONLINE, now());
        $this->reservation(5, 10000, Reservation::CHANNEL_PHYSICAL, now()->subDay());

        $this->actingAs($this->organizer)
            ->get(route('organizer.dashboard'))
            ->assertOk()
            ->assertSee('value="all" selected', false)
            ->assertSee('2 en ligne / 5 en physique', false);
    }

    public function test_charts_receive_flat_scalar_series(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $this->reservation(2, 5000, Reservation::CHANNEL_ONLINE, now());
        $this->reservation(5, 10000, Reservation::CHANNEL_PHYSICAL, now()->subDay());

        // collect($array, 'clé') ignorait la clé et envoyait des objets à
        // Chart.js (« [object Object] »). Les séries doivent être des scalaires.
        $this->actingAs($this->organizer)
            ->get(route('organizer.dashboard', ['period' => 'all']))
            ->assertOk()
            ->assertDontSee('"date":"', false)
            ->assertDontSee('"tickets":', false)
            ->assertDontSee('"physical":', false);
    }

    public function test_validations_card_shows_accepted_and_refused(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $this->validation(Validation::STATUS_VALID, now());
        $this->validation(Validation::STATUS_INVALID, now());
        $this->validation(Validation::STATUS_INVALID, now());

        $this->actingAs($this->organizer)
            ->get(route('organizer.dashboard', ['period' => 'today']))
            ->assertOk()
            ->assertSee('2 refus(s) / 1 accepté(s)', false);
    }
}
