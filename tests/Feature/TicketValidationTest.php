<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Validation;
use App\Services\ReservationPaymentManager;
use App\Services\TicketValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    private Event $event;

    private User $agent;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->organizer()->create();
        $this->agent = User::factory()->agent($this->organizer)->create();

        $this->event = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Soiree Validation',
            'slug' => 'soiree-validation',
            'location' => 'Dakar',
            'event_date' => now()->addWeek(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $type = $this->event->ticketTypes()->create([
            'name' => 'Standard', 'price' => 5000, 'quantity' => 100, 'is_active' => true,
        ]);

        $reservation = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $this->event->id,
            'ticket_type_id' => $type->id,
            'customer_name' => 'Porteur Test',
            'customer_email' => 'porteur@test.sn',
            'quantity' => 1,
            'unit_price' => 5000,
            'total_amount' => 5000,
            'payment_status' => Reservation::STATUS_PAID,
            'channel' => Reservation::CHANNEL_ONLINE,
            'paid_at' => now(),
        ]);

        app(ReservationPaymentManager::class)->generateTickets($reservation);

        $this->ticket = $reservation->tickets()->firstOrFail();
    }

    public function test_valid_ticket_is_accepted_and_marked_used(): void
    {
        $result = app(TicketValidationService::class)->scan($this->agent, $this->ticket->code);

        $this->assertSame('valid', $result['outcome']);

        $this->ticket->refresh();

        $this->assertSame(Ticket::STATUS_USED, $this->ticket->status);
        $this->assertNotNull($this->ticket->used_at);
        $this->assertSame($this->agent->id, $this->ticket->validated_by);

        $this->assertDatabaseHas('validations', [
            'ticket_id' => $this->ticket->id,
            'agent_id' => $this->agent->id,
            'status' => Validation::STATUS_VALID,
        ]);
    }

    public function test_ticket_cannot_be_used_twice(): void
    {
        $scanner = app(TicketValidationService::class);

        $first = $scanner->scan($this->agent, $this->ticket->code);
        $second = $scanner->scan($this->agent, $this->ticket->code);

        $this->assertSame('valid', $first['outcome']);
        $this->assertSame('invalid', $second['outcome']);
        $this->assertSame(Validation::MSG_ALREADY_USED, $second['message']);

        // Un seul ticket reste marqué utilisé, un refus est journalisé.
        $this->assertSame(1, $this->ticket->fresh()->validations()->where('status', Validation::STATUS_VALID)->count());
        $this->assertSame(1, $this->ticket->validations()->where('status', Validation::STATUS_INVALID)->count());
    }

    public function test_unknown_code_is_rejected(): void
    {
        $result = app(TicketValidationService::class)->scan($this->agent, 'BXT-XXXX-XXXX');

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame(Validation::MSG_UNKNOWN, $result['message']);
        $this->assertNull($result['ticket']);
    }

    public function test_refunded_ticket_is_rejected(): void
    {
        $this->ticket->update(['status' => Ticket::STATUS_REFUNDED]);

        $result = app(TicketValidationService::class)->scan($this->agent, $this->ticket->code);

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame(Validation::MSG_EXPIRED, $result['message']);
    }

    public function test_ticket_from_another_event_is_rejected_when_event_is_scoped(): void
    {
        $otherEvent = Event::create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Autre Soiree',
            'slug' => 'autre-soiree',
            'location' => 'Thies',
            'event_date' => now()->addWeek(),
            'total_tickets' => 50,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $result = app(TicketValidationService::class)
            ->scan($this->agent, $this->ticket->code, $otherEvent);

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame(Validation::MSG_EVENT_MISMATCH, $result['message']);
        $this->assertSame(Ticket::STATUS_VALID, $this->ticket->fresh()->status);
    }

    public function test_normalize_accepts_ticket_url_json_and_raw_code(): void
    {
        $scanner = app(TicketValidationService::class);

        $this->assertSame($this->ticket->code, $scanner->normalize($this->ticket->code));
        $this->assertSame($this->ticket->code, $scanner->normalize(strtolower($this->ticket->code)));
        $this->assertSame($this->ticket->code, $scanner->normalize(route('tickets.show', $this->ticket)));
        $this->assertSame($this->ticket->code, $scanner->normalize(json_encode(['code' => $this->ticket->code])));
    }

    public function test_clean_input_strips_spaces_and_uppercases(): void
    {
        $this->assertSame(
            $this->ticket->code,
            TicketValidationService::cleanInput(strtolower(str_replace('-', ' - ', $this->ticket->code)))
        );
    }

    public function test_agent_scan_endpoint_returns_json_and_redirect(): void
    {
        $response = $this->actingAs($this->agent)
            ->postJson(route('agent.validate'), ['code' => $this->ticket->code]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('outcome', 'valid')
            ->assertJsonPath('ticket.code', $this->ticket->code);

        $validation = Validation::firstOrFail();
        $response->assertJsonPath('redirect', route('agent.result', $validation->id));
    }

    public function test_agent_manual_endpoint_validates_code(): void
    {
        $this->actingAs($this->agent)
            ->postJson(route('agent.manual'), ['code' => strtolower($this->ticket->code)])
            ->assertOk()
            ->assertJsonPath('outcome', 'valid');

        $this->assertSame(Ticket::STATUS_USED, $this->ticket->fresh()->status);
    }

    public function test_non_agent_cannot_scan(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('agent.validate'), ['code' => $this->ticket->code])
            ->assertForbidden();

        $this->assertSame(Ticket::STATUS_VALID, $this->ticket->fresh()->status);
    }

    public function test_guest_cannot_scan(): void
    {
        $this->postJson(route('agent.validate'), ['code' => $this->ticket->code])->assertUnauthorized();

        $this->assertSame(Ticket::STATUS_VALID, $this->ticket->fresh()->status);
    }

    public function test_agent_index_only_lists_own_organizer_tickets(): void
    {
        [$otherEvent, $otherTicket] = $this->foreignTicket('Autre Organisateur');

        $response = $this->actingAs($this->agent)->get(route('agent.index'));

        $response->assertOk()
            ->assertSee($this->ticket->code)
            ->assertDontSee($otherTicket->code)
            ->assertDontSee($otherEvent->title);
    }

    public function test_agent_cannot_validate_a_foreign_organizer_ticket(): void
    {
        [, $otherTicket] = $this->foreignTicket('Autre Organisateur');

        $result = app(TicketValidationService::class)->scan($this->agent, $otherTicket->code);

        $this->assertSame('invalid', $result['outcome']);
        $this->assertSame(Validation::MSG_EVENT_MISMATCH, $result['message']);
        $this->assertSame(Ticket::STATUS_VALID, $otherTicket->fresh()->status);
    }

    /** Crée un événement + un billet pour un autre organisateur. */
    private function foreignTicket(string $name): array
    {
        $other = User::factory()->organizer()->create();

        $event = Event::create([
            'organizer_id' => $other->id,
            'title' => 'Evenement '.$name,
            'slug' => Event::slugFor('evenement-'.$name),
            'location' => 'Dakar',
            'event_date' => now()->addWeek(),
            'total_tickets' => 20,
            'status' => Event::STATUS_PUBLISHED,
        ]);

        $type = $event->ticketTypes()->create([
            'name' => 'Standard', 'price' => 1000, 'quantity' => 20, 'is_active' => true,
        ]);

        $reservation = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $type->id,
            'customer_name' => 'Porteur Externe',
            'customer_email' => 'externe@test.sn',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_amount' => 1000,
            'payment_status' => Reservation::STATUS_PAID,
            'channel' => Reservation::CHANNEL_ONLINE,
            'paid_at' => now(),
        ]);

        app(ReservationPaymentManager::class)->generateTickets($reservation);

        return [$event, $reservation->tickets()->firstOrFail()];
    }

    public function test_agent_scan_page_is_shown_to_agents(): void
    {
        $this->actingAs($this->agent)->get(route('agent.scan'))->assertOk();
        $this->actingAs($this->agent)->get(route('agent.index'))->assertOk();
    }

    public function test_agent_result_page_renders(): void
    {
        $validation = Validation::create([
            'ticket_id' => $this->ticket->id,
            'event_id' => $this->event->id,
            'agent_id' => $this->agent->id,
            'ticket_code' => $this->ticket->code,
            'status' => Validation::STATUS_VALID,
            'message' => Validation::MSG_WELCOME,
        ]);

        $this->actingAs($this->agent)
            ->get(route('agent.result', $validation->id))
            ->assertOk()
            ->assertSee($this->ticket->code);
    }
}
