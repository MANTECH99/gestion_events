<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Derriere un reverse proxy / tunnel, le TLS est termine en amont : l'application
 * doit lire X-Forwarded-Proto, sinon route() genere http:// sur une page servie
 * en https (mixed content bloque par le navigateur) et DexPay refuse les callbacks.
 */
class HttpsBehindProxyTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEvent(): Event
    {
        return Event::create([
            'organizer_id' => User::factory()->organizer()->create()->id,
            'title' => 'Evenement Proxy',
            'slug' => Event::slugFor('evenement-proxy-test'),
            'location' => 'Dakar',
            'event_date' => now()->addMonth(),
            'total_tickets' => 100,
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => true,
        ]);
    }

    public function test_urls_are_generated_in_https_when_the_proxy_forwards_the_scheme(): void
    {
        $html = $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get(route('home'))
            ->assertOk()
            ->getContent();

        // Aucune URL applicative ne doit rester en http://.
        $this->assertStringNotContainsString('http://localhost', $html);
        $this->assertStringNotContainsString('href="http://app', $html);
        $this->assertStringContainsString('href="https://', $html);
    }

    public function test_event_pages_do_not_mix_http_and_https(): void
    {
        $event = $this->publishedEvent();

        $html = $this->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get(route('events.show', $event))
            ->assertOk()
            ->getContent();

        preg_match_all('/(?:href|src)="http:\/\/[^"]+"/', $html, $matches);

        $this->assertSame([], $matches[0], 'URLs http:// detectees : '.implode(', ', $matches[0]));
    }

    public function test_local_requests_without_proxy_stay_on_http(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('http://localhost', $html);
    }

    public function test_image_urls_are_relative_and_never_localhost(): void
    {
        $event = $this->publishedEvent();

        // Chemin relatif : URL heritee de la requete courante.
        $event->update(['image_path' => 'events/abc.jpg']);
        $this->assertSame('/storage/events/abc.jpg', $event->fresh()->image_url);

        // URL absolue locale stockee avant le passage en HTTPS.
        $event->update(['image_path' => 'http://localhost:8000/storage/events/abc.jpg']);
        $this->assertSame('/storage/events/abc.jpg', $event->fresh()->image_url);

        // CDN externe : conserve tel quel.
        $event->update(['image_path' => 'https://cdn.example.com/abc.jpg']);
        $this->assertSame('https://cdn.example.com/abc.jpg', $event->fresh()->image_url);

        $event->update(['image_path' => null]);
        $this->assertNull($event->fresh()->image_url);
    }
}
