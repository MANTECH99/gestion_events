<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Reservation;
use App\Models\StockAllocation;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\Validation;
use App\Services\ReservationPaymentManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Chaque page doit rendre sans erreur, y compris ses états vides et remplis.
 */
class PageRenderingTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private User $pos;

    private User $agent;

    private User $customer;

    private User $admin;

    private Event $event;

    private TicketType $type;

    private StockAllocation $allocation;

    private Reservation $reservation;

    private Validation $validation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->organizer()->create();
        $this->pos = User::factory()->pointOfSale($this->organizer)->create();
        $this->agent = User::factory()->agent($this->organizer)->create();
        $this->customer = User::factory()->create();
        $this->admin = User::factory()->admin()->create();

        $this->event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Concert Rendu',
            'slug' => 'concert-rendu',
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 300,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);

        $this->type = $this->event->ticketTypes()->create([
            'name' => 'VIP',
            'price' => 10000,
            'quantity' => 60,
            'is_active' => true,
        ]);

        $this->allocation = StockAllocation::create([
            'ticket_type_id' => $this->type->id,
            'user_id' => $this->pos->id,
            'allocated' => 40,
            'sold' => 4,
        ]);

        $this->post(route('booking.store', $this->event), [
            'customer_name' => 'Client Rendu',
            'customer_email' => 'rendu@test.sn',
            'customer_phone' => '+221770000000',
            'ticket_type_id' => $this->type->id,
            'quantity' => 2,
        ]);

        $this->reservation = Reservation::firstOrFail();
        $this->reservation->update(['user_id' => $this->customer->id]);
        app(ReservationPaymentManager::class)->markPaid($this->reservation);
        $this->reservation->refresh();

        $ticket = Ticket::where('reservation_id', $this->reservation->id)->firstOrFail();

        $this->validation = Validation::create([
            'ticket_id' => $ticket->id,
            'event_id' => $this->event->id,
            'agent_id' => $this->agent->id,
            'ticket_code' => $ticket->code,
            'status' => Validation::STATUS_VALID,
            'message' => Validation::MSG_WELCOME,
            'scanned_code' => $ticket->code,
        ]);
    }

    public function test_organizer_pages_render(): void
    {
        $routes = [
            route('organizer.dashboard'),
            route('organizer.events.index'),
            route('organizer.events.create'),
            route('organizer.events.edit', $this->event),
            route('organizer.events.ticket-types', $this->event),
            route('organizer.users.index'),
            route('organizer.validations.index'),
            route('organizer.validations.by-agent'),
            route('organizer.reservations.index'),
            route('organizer.reports.index'),
            route('organizer.reports.show', $this->event),
            route('organizer.stocks.index'),
            route('organizer.pos.index'),
            route('organizer.pos.show', $this->pos),
            route('organizer.payouts.index'),
            route('organizer.payment-sessions.index'),
            route('pos.dashboard'),
            route('reservations.index'),
        ];

        $this->assertPagesRender($this->organizer, $routes);
    }

    public function test_payment_session_row_displays_the_service_fee(): void
    {
        // DexPay ne joint pas de frais au webhook checkout.completed : la page
        // doit afficher la surcharge de service (montant payé − prix des billets).
        Payment::create([
            'reservation_id' => $this->reservation->id,
            'provider' => 'dexpay',
            'reference' => $this->reservation->reference,
            'status' => 'completed',
            'currency' => 'XOF',
            'amount' => $this->reservation->total_amount + 30,
            'operator' => 'wave_sn',
            'payload' => ['event' => 'checkout.completed'],
        ]);

        $this->actingAs($this->organizer)
            ->get(route('organizer.payment-sessions.index'))
            ->assertOk()
            ->assertSee('30 FCFA');
    }

    public function test_admin_pages_render(): void
    {
        $routes = [
            route('admin.dashboard'),
            route('admin.organizers.index'),
            route('admin.organizers.show', $this->organizer),
            route('admin.users.index'),
            route('organizer.dashboard'),
            route('organizer.users.index'),
            route('organizer.stocks.index'),
            route('organizer.reports.index'),
            route('organizer.payment-sessions.index'),
            route('pos.dashboard'),
        ];

        $this->assertPagesRender($this->admin, $routes);
    }

    public function test_admin_organizer_row_action_toggles_the_edit_row(): void
    {
        // Régression : la ligne d'édition était hors de la portée Alpine (x-data
        // posé sur la 1re <tr>), donc le bouton « crayon » ne montrait rien.
        $content = $this->actingAs($this->admin)
            ->get(route('admin.organizers.index'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('x-data="{ openId: null }"', $content);
        $this->assertStringContainsString('x-show="openId === '.$this->organizer->id.'"', $content);
        $this->assertStringNotContainsString('{{ $', $content);
        $this->assertStringNotContainsString('@{{', $content);
    }

    /** @param  list<string>  $urls */
    private function assertPagesRender(User $user, array $urls): void
    {
        foreach ($urls as $url) {
            $response = $this->actingAs($user)->get($url);

            $reason = $response->exception
                ? $response->exception::class.': '.$response->exception->getMessage()
                : 'no exception captured';

            $this->assertTrue(
                $response->isOk() || $response->isRedirect(),
                sprintf('%s responded %d for %s — %s', $url, $response->status(), $user->role, $reason)
            );

            $this->assertStringNotContainsString(
                'class="\'',
                $response->getContent() ?? '',
                sprintf('Attribut class malformé sur %s : @icon() aurait injecté des apostrophes', $url)
            );

            $this->assertStringNotContainsString(
                '{{ $',
                $response->getContent(),
                sprintf('Blade non compilé (@{{ }} littéral) sur %s', $url)
            );
        }
    }

    public function test_icons_receive_their_size_classes(): void
    {
        // Régression : @icon('x', 'w-4 h-4') compilait en class="'w-4 h-4'".
        $content = $this->get(route('events.show', $this->event))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertMatchesRegularExpression('/<svg[^>]*class="w-4 h-4"/', $content);
        $this->assertStringNotContainsString('class="\'', $content);
    }

    public function test_point_of_sale_page_renders(): void
    {
        $this->actingAs($this->pos)
            ->get(route('pos.dashboard'))
            ->assertOk()
            ->assertSee('VIP');
    }

    public function test_customer_pages_render(): void
    {
        $this->actingAs($this->customer)->get(route('reservations.index'))->assertOk();

        $this->actingAs($this->customer)
            ->get(route('reservations.show', $this->reservation->reference))
            ->assertOk()
            ->assertSee($this->reservation->reference);
    }

    public function test_ticket_pages_render(): void
    {
        $ticket = Ticket::where('reservation_id', $this->reservation->id)->firstOrFail();

        // Un visiteur sans session d'accès ne voit pas les tickets.
        $this->flushSession();
        $this->get(route('tickets.show', $ticket))->assertForbidden();
        $this->get(route('tickets.reservation', $this->reservation->reference))->assertForbidden();

        $this->actingAs($this->customer)->get(route('tickets.show', $ticket))->assertOk();
        $this->actingAs($this->customer)
            ->get(route('tickets.reservation', $this->reservation->reference))
            ->assertOk();

        // Un organisateur / agent peut contrôler un billet.
        $this->actingAs($this->organizer)->get(route('tickets.show', $ticket))->assertOk();
        $this->actingAs($this->agent)->get(route('tickets.show', $ticket))->assertOk();
    }

    public function test_agent_pages_render(): void
    {
        $this->actingAs($this->agent)->get(route('agent.index'))->assertOk();
        $this->actingAs($this->agent)->get(route('agent.scan'))->assertOk();
        $this->actingAs($this->agent)->get(route('agent.result', $this->validation))->assertOk();
    }

    public function test_status_badges_render_concrete_classes(): void
    {
        // Le badge de l'événement construit sa classe en Blade : le fragment dynamique
        // ne doit jamais fuiter et la classe doit exister dans le CSS compilé.
        $this->get(route('events.show', $this->event))
            ->assertOk()
            ->assertSee('bg-orange-500', false)
            ->assertDontSee('bg-{{', false);

        $ticket = Ticket::where('reservation_id', $this->reservation->id)->firstOrFail();

        $this->actingAs($this->agent)
            ->get(route('agent.result', $this->validation))
            ->assertOk()
            ->assertSee(sprintf('bg-%s-100', $ticket->status_color), false)
            ->assertDontSee('bg-{{', false);
    }

    public function test_organizer_cannot_open_another_organizers_event(): void
    {
        $other = User::factory()->organizer()->create();

        $foreign = Event::create([
            'organizer_id' => $other->id,
            'title' => 'Evenement Etranger',
            'slug' => 'evenement-etranger',
            'location' => 'Thies',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $this->actingAs($this->organizer)->get(route('organizer.events.edit', $foreign))->assertForbidden();
        $this->actingAs($this->organizer)->get(route('organizer.reports.show', $foreign))->assertForbidden();
    }

    public function test_payout_summary_cards_render_totals(): void
    {
        Payout::create([
            'organizer_id' => $this->organizer->id,
            'reference' => 'PO-TEST-1001',
            'amount' => 50000,
            'currency' => 'XOF',
            'fees' => 750,
            'destination_phone' => '+221771234567',
            'operator' => 'wave_sn',
            'country_iso' => 'SN',
            'status' => 'COMPLETED',
        ]);

        Payout::create([
            'organizer_id' => $this->organizer->id,
            'reference' => 'PO-TEST-1002',
            'amount' => 25000,
            'currency' => 'XOF',
            'fees' => 0,
            'destination_phone' => '+221771234567',
            'operator' => 'wave_sn',
            'country_iso' => 'SN',
            'status' => 'PENDING',
        ]);

        $content = $this->actingAs($this->organizer)
            ->get(route('organizer.payouts.index'))
            ->assertOk()
            ->getContent();

        $this->assertIsString($content);
        $this->assertStringContainsString('Retraits', $content);
        $this->assertStringContainsString('Montant total envoyé', $content);
        $this->assertStringContainsString('Frais total', $content);
        $this->assertStringContainsString('75 000 FCFA', $content);
        $this->assertStringContainsString('750 FCFA', $content);

        // Soldes : portefeuille renommé + revenus de l'organisateur (2 × 10 000).
        $this->assertStringContainsString('Solde billetterie express', $content);
        $this->assertStringContainsString("Solde de l'organisateur", $content);
        $this->assertStringContainsString('20 000 FCFA', $content);
    }

    public function test_agent_area_is_hidden_from_plain_customers(): void
    {
        $this->actingAs($this->customer)
            ->post(route('agent.validate'), ['code' => 'BXT-AAAA-BBBB'])
            ->assertForbidden();
    }
}
