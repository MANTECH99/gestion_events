<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Services\QrCodeService;
use App\Services\TicketPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Les vues PDF ne sont compilees qu'au moment du clic : une vue renommee,
 * supprimee ou une variable non transmise echouent uniquement a l'usage.
 */
class PdfDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function reservationFixture(): Reservation
    {
        $organizer = User::factory()->organizer()->create();

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Soirée Test PDF',
            'slug' => Event::slugFor('soiree-test-pdf'),
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 50,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);

        $ticketType = TicketType::create([
            'event_id' => $event->id,
            'name' => 'Standard',
            'price' => 2000,
        ]);

        $reservation = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'customer_name' => 'Client PDF',
            'customer_email' => 'pdf@test.sn',
            'customer_phone' => '+221700000000',
            'quantity' => 1,
            'unit_price' => 2000,
            'total_amount' => 2000,
            'payment_status' => 'paid',
            'channel' => 'online',
            'payment_operator' => 'wave_sn',
            'paid_at' => now(),
        ]);

        Ticket::create([
            'code' => Ticket::makeCode(),
            'reservation_id' => $reservation->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'holder_name' => 'Client PDF',
            'price' => 2000,
        ]);

        return $reservation->fresh(['event.organizer', 'ticketType', 'tickets']);
    }

    private function skipWithoutPdfEngine(): void
    {
        if (! TicketPdfService::isAvailable()) {
            $this->markTestSkipped('Aucun moteur PDF (Chrome/Edge/wkhtmltopdf) disponible.');
        }
    }

    private function pdfPageCount(string $content): int
    {
        return preg_match('/\/Count (\d+)/', $content, $m) ? (int) $m[1] : 0;
    }

    public function test_organizer_downloads_the_reservation_pdf(): void
    {
        $reservation = $this->reservationFixture();
        $organizer = $reservation->event->organizer;

        $this->actingAs($organizer)
            ->get(route('organizer.reservations.pdf', $reservation))
            ->assertOk();
    }

    public function test_reservation_pdf_returns_an_actual_pdf_document(): void
    {
        $reservation = $this->reservationFixture();

        $response = $this->actingAs($reservation->event->organizer)
            ->get(route('organizer.reservations.pdf', $reservation));

        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_organizer_cannot_download_a_reservation_from_another_organizer(): void
    {
        $reservation = $this->reservationFixture();

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('organizer.reservations.pdf', $reservation))
            ->assertForbidden();
    }

    public function test_organizer_downloads_only_the_tickets_pdf(): void
    {
        $this->skipWithoutPdfEngine();

        $reservation = $this->reservationFixture();

        Ticket::create([
            'code' => Ticket::makeCode(),
            'reservation_id' => $reservation->id,
            'event_id' => $reservation->event_id,
            'ticket_type_id' => $reservation->ticket_type_id,
            'holder_name' => 'Client PDF',
            'price' => 2000,
        ]);

        $response = $this->actingAs($reservation->event->organizer)
            ->get(route('organizer.reservations.tickets', $reservation));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        // Un billet = une page, à l'image de la carte affichée.
        $this->assertSame(2, $this->pdfPageCount($response->getContent()));
    }

    public function test_print_view_reproduces_the_ticket_card(): void
    {
        $reservation = $this->reservationFixture();
        $ticket = $reservation->tickets->first();
        $qr = app(QrCodeService::class);

        $html = view('tickets.print', [
            'tickets' => $reservation->tickets,
            'qr' => fn (Ticket $item) => $qr->svg($item->qrPayload(), 512),
            'css' => '',
        ])->render();

        $this->assertStringContainsString($ticket->code, $html);
        $this->assertStringContainsString($ticket->event->title, $html);
        $this->assertStringContainsString($ticket->holder_name, $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function test_organizer_cannot_download_tickets_from_another_organizer(): void
    {
        $reservation = $this->reservationFixture();

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('organizer.reservations.tickets', $reservation))
            ->assertForbidden();
    }

    public function test_guest_with_session_access_downloads_the_reservation_summary(): void
    {
        $reservation = $this->reservationFixture();

        $response = $this->withSession(['reservation_access.'.$reservation->id => true])
            ->get(route('reservations.download', $reservation->reference));

        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_ticket_pdf_requires_a_granted_access(): void
    {
        $this->skipWithoutPdfEngine();

        $reservation = $this->reservationFixture();
        $ticket = $reservation->tickets->first();

        $this->get(route('tickets.download', $ticket))->assertForbidden();

        $response = $this->withSession(['ticket_access.'.$ticket->id => true])
            ->get(route('tickets.download', $ticket));

        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_guest_with_a_signed_url_opens_the_ticket_without_session(): void
    {
        $reservation = $this->reservationFixture();
        $ticket = $reservation->tickets->first();

        $this->get(route('tickets.show', $ticket))->assertForbidden();

        $this->get(URL::signedRoute('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($ticket->code);
    }

    public function test_guest_with_a_signed_url_downloads_the_ticket_without_session(): void
    {
        $this->skipWithoutPdfEngine();

        $reservation = $this->reservationFixture();
        $ticket = $reservation->tickets->first();

        $this->get(route('tickets.download', $ticket))->assertForbidden();

        $response = $this->get(URL::signedRoute('tickets.download', $ticket));

        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_guest_with_a_signed_url_opens_the_ticket_list_without_session(): void
    {
        $reservation = $this->reservationFixture();

        $this->get(route('tickets.reservation', $reservation->reference))->assertForbidden();

        $this->get(URL::signedRoute('tickets.reservation', $reservation->reference))
            ->assertOk()
            ->assertSee($reservation->reference);
    }

    public function test_guest_with_a_signed_url_downloads_the_reservation_summary(): void
    {
        $reservation = $this->reservationFixture();

        $response = $this->get(URL::signedRoute('reservations.download', $reservation->reference));

        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }
}
