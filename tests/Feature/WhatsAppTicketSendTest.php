<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Après paiement, le navigateur du client génère l'image du billet et l'envoie
 * au serveur, qui la transmet à l'API WhatsApp Cloud (modèle utilitaire). Un
 * seul message par réservation.
 */
class WhatsAppTicketSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.enabled' => true,
            'whatsapp.token' => 'jeton-test',
            'whatsapp.phone_number_id' => '123456',
            'whatsapp.api_version' => 'v21.0',
            'whatsapp.base_url' => 'https://graph.facebook.com',
            'whatsapp.template.name' => 'receipt_billeterie_express',
            'whatsapp.template.lang' => 'fr',
            'whatsapp.template.header' => 'image',
            'whatsapp.template.has_button' => true,
            'whatsapp.default_country_code' => '221',
        ]);

        Http::fake([
            'graph.facebook.com/*/media' => Http::response(['id' => 'MEDIA_ID'], 200),
            'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);
    }

    private function reservation(string $phone = '+221771234567', string $status = 'paid'): Reservation
    {
        $organizer = User::factory()->organizer()->create();

        $event = Event::create([
            'organizer_id' => $organizer->id,
            'title' => 'Spectacle WhatsApp',
            'slug' => Event::slugFor('spectacle-whatsapp-'.uniqid()),
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);

        $ticketType = TicketType::create([
            'event_id' => $event->id,
            'name' => 'Standard',
            'price' => 5000,
        ]);

        $reservation = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'customer_name' => 'Awa Diop',
            'customer_email' => 'awa@test.sn',
            'customer_phone' => $phone,
            'quantity' => 1,
            'unit_price' => 5000,
            'total_amount' => 5000,
            'payment_status' => $status,
            'channel' => 'online',
            'payment_operator' => 'wave_sn',
            'paid_at' => $status === 'paid' ? now() : null,
        ]);

        Ticket::create([
            'code' => Ticket::makeCode(),
            'reservation_id' => $reservation->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'holder_name' => 'Awa Diop',
            'price' => 5000,
        ]);

        return $reservation;
    }

    private function imagePayload(): string
    {
        return 'data:image/png;base64,'.base64_encode(str_repeat('billet-image-', 40));
    }

    private function signedUrl(Reservation $reservation): string
    {
        return URL::signedRoute('reservations.whatsapp', ['reference' => $reservation->reference]);
    }

    public function test_it_sends_the_ticket_image_on_whatsapp(): void
    {
        $reservation = $this->reservation();

        $this->post($this->signedUrl($reservation), ['image' => $this->imagePayload()])
            ->assertOk()
            ->assertJson(['sent' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/media')
            && $request->method() === 'POST'
            && str_contains($request->body(), 'messaging_product')
            && str_contains($request->body(), 'image/png'));

        Http::assertSent(function ($request) use ($reservation) {
            if (! str_contains($request->url(), '/messages')) {
                return false;
            }

            $data = $request->data();

            return $data['to'] === '221771234567'
                && $data['type'] === 'template'
                && $data['template']['name'] === 'receipt_billeterie_express'
                && $data['template']['language']['code'] === 'fr'
                && $data['template']['components'][0]['parameters'][0]['image']['id'] === 'MEDIA_ID'
                && $data['template']['components'][1]['parameters'][0]['text'] === 'Awa Diop'
                && $data['template']['components'][1]['parameters'][1]['text'] === $reservation->reference
                && $data['template']['components'][2]['sub_type'] === 'url'
                && str_starts_with($data['template']['components'][2]['parameters'][0]['text'], 'reservation/')
                && str_contains($data['template']['components'][2]['parameters'][0]['text'], $reservation->reference)
                && ! str_contains($data['template']['components'][2]['parameters'][0]['text'], 'http');
        });

        $this->assertNotNull($reservation->fresh()->whatsapp_sent_at);
    }

    public function test_a_reservation_is_never_sent_twice(): void
    {
        $reservation = $this->reservation();

        $this->post($this->signedUrl($reservation), ['image' => $this->imagePayload()])->assertOk();

        Http::fake();

        $this->post($this->signedUrl($reservation), ['image' => $this->imagePayload()])
            ->assertOk()
            ->assertJson(['sent' => false, 'reason' => 'already_sent']);

        Http::assertNothingSent();
    }

    public function test_a_local_phone_number_gets_the_default_country_code(): void
    {
        $reservation = $this->reservation('77 123 45 67');

        $this->post($this->signedUrl($reservation), ['image' => $this->imagePayload()])->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/messages')
            && $request->data()['to'] === '221771234567');
    }

    public function test_it_does_nothing_when_whatsapp_is_disabled(): void
    {
        config(['whatsapp.enabled' => false]);

        $reservation = $this->reservation();

        $this->post($this->signedUrl($reservation), ['image' => $this->imagePayload()])
            ->assertStatus(503)
            ->assertJson(['sent' => false, 'reason' => 'disabled']);

        Http::assertNothingSent();
        $this->assertNull($reservation->fresh()->whatsapp_sent_at);
    }

    public function test_it_refuses_an_unpaid_reservation(): void
    {
        $reservation = $this->reservation(status: 'pending');

        $this->post($this->signedUrl($reservation), ['image' => $this->imagePayload()])
            ->assertStatus(422)
            ->assertJson(['sent' => false, 'reason' => 'unpaid']);

        Http::assertNothingSent();
    }

    public function test_it_rejects_an_invalid_image_payload(): void
    {
        $reservation = $this->reservation();

        $this->post($this->signedUrl($reservation), ['image' => 'not-an-image'])
            ->assertStatus(422)
            ->assertJson(['sent' => false, 'reason' => 'invalid_image']);

        Http::assertNothingSent();
    }

    public function test_the_endpoint_requires_a_valid_signature(): void
    {
        $reservation = $this->reservation();

        $this->post(route('reservations.whatsapp', $reservation->reference), ['image' => $this->imagePayload()])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_the_whatsapp_button_link_opens_the_ticket_page_on_any_host(): void
    {
        $reservation = $this->reservation();

        // Le bouton du modèle vaut « https://billeteriexpress.com/{{1}} » : le
        // chemin transmis est signé de façon relative (indépendant du domaine).
        $path = URL::signedRoute('tickets.reservation', ['reference' => $reservation->reference], absolute: false);

        $this->get($path)->assertOk()->assertSee($reservation->reference);
    }

    public function test_the_phone_normalization_covers_the_common_formats(): void
    {
        $this->assertSame('221771234567', whatsapp_phone('77 123 45 67'));
        $this->assertSame('221771234567', whatsapp_phone('0771234567'));
        $this->assertSame('221771234567', whatsapp_phone('+221 77 123 45 67'));
        $this->assertSame('221771234567', whatsapp_phone('00221771234567'));
        $this->assertSame('33612345678', whatsapp_phone('+33 6 12 34 56 78'));
        $this->assertNull(whatsapp_phone(''));
        $this->assertNull(whatsapp_phone(null));
    }
}
