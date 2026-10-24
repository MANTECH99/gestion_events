<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * En-tête du billet : la photo de l'artiste rattaché à l'événement (section
 * Artistes) remplace la couverture, avec le slogan manuscrit
 * « Le Sénégal vit en grand ! ». Repli sur la couverture sans photo d'artiste.
 * Pied du billet : photo de profil de l'organisateur, à défaut le logo.
 */
class TicketHeaderTest extends TestCase
{
    use RefreshDatabase;

    private function ticketFor(User $organizer, string $cover, ?Artist $artist = null): Ticket
    {
        $event = Event::create([
            'organizer_id' => $organizer->id,
            'artist_id' => $artist?->id,
            'title' => 'Wally Seck en live',
            'slug' => Event::slugFor('wally-seck-'.uniqid()),
            'location' => 'Stade Demba Diop, Dakar',
            'event_date' => now()->addWeek(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
            'image_path' => $cover,
            'organizer_display_name' => 'Arena Events',
        ]);

        $ticketType = TicketType::create([
            'event_id' => $event->id,
            'name' => 'Normal',
            'price' => 5000,
        ]);

        $reservation = Reservation::create([
            'reference' => Reservation::makeReference(),
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'customer_name' => 'MAN_TECH',
            'customer_email' => 'man@tech.sn',
            'customer_phone' => '+221770000000',
            'quantity' => 1,
            'unit_price' => 5000,
            'total_amount' => 5000,
            'payment_status' => 'paid',
            'channel' => 'online',
            'payment_operator' => 'wave_sn',
            'paid_at' => now(),
        ]);

        return Ticket::create([
            'code' => Ticket::makeCode(),
            'reservation_id' => $reservation->id,
            'event_id' => $event->id,
            'ticket_type_id' => $ticketType->id,
            'holder_name' => 'MAN_TECH',
            'price' => 5000,
        ]);
    }

    public function test_the_artist_photo_replaces_the_event_cover(): void
    {
        Storage::disk('public')->put('artists/artist-qa.png', file_get_contents(public_path('images/apple-touch-icon.png')));
        Storage::disk('public')->put('avatars/organizer-qa.png', file_get_contents(public_path('images/apple-touch-icon.png')));
        Storage::disk('public')->put('events/cover-qa.png', file_get_contents(public_path('images/apple-touch-icon.png')));

        $artist = Artist::create(['name' => 'Wally Seck', 'title' => 'Chanteur • Mbalax', 'image_path' => 'artists/artist-qa.png']);
        $organizer = User::factory()->organizer()->create(['avatar_path' => 'avatars/organizer-qa.png']);
        $ticket = $this->ticketFor($organizer, 'events/cover-qa.png', $artist);

        $html = $this->get(URL::signedRoute('tickets.show', $ticket))->assertOk()->getContent();

        // L'en-tête est bien porté par la photo de l'artiste, jamais par l'avatar.
        $this->assertSame(1, preg_match('/<img src="([^"]+)"[^>]*object-cover object-\[center_30%\]/', $html, $header));
        $this->assertStringContainsString('/storage/artists/artist-qa.png', $header[1]);
        $this->assertStringNotContainsString('/storage/events/cover-qa.png', $html);

        // La photo de profil de l'organisateur est affichée dans le pied du billet.
        $this->assertMatchesRegularExpression('#<img src="[^"]*storage/avatars/organizer-qa\.png"[^>]*rounded-full#', $html);
    }

    public function test_the_event_cover_is_the_fallback_without_artist_photo(): void
    {
        Storage::disk('public')->put('events/cover-qa.png', file_get_contents(public_path('images/apple-touch-icon.png')));

        $ticket = $this->ticketFor(User::factory()->organizer()->create(), 'events/cover-qa.png');

        $html = $this->get(URL::signedRoute('tickets.show', $ticket))->assertOk()->getContent();

        $this->assertStringContainsString('/storage/events/cover-qa.png', $html);
        // Sans photo de profil, le pied du billet affiche le logo.
        $this->assertMatchesRegularExpression('#<img src="[^"]*images/icon\.svg" alt="" class="h-4 w-4 shrink-0">#', $html);
    }

    public function test_the_ticket_header_shows_the_brand_slogan(): void
    {
        $ticket = $this->ticketFor(User::factory()->organizer()->create(), 'events/cover-qa.png');

        $html = $this->get(URL::signedRoute('tickets.show', $ticket))->assertOk()->getContent();

        $this->assertStringContainsString('font-handwritten', $html);
        $this->assertStringContainsString('Le Sénégal<br>vit en grand !', $html);
        $this->assertStringContainsString('stroke="#fb923c"', $html);
    }
}
