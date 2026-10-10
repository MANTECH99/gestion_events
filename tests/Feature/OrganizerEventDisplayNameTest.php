<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizerEventDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizer = User::factory()->organizer()->create([
            'first_name' => 'Moussa',
            'last_name' => 'Diop',
        ]);
    }

    private function store(array $overrides = []): Event
    {
        $this->actingAs($this->organizer)->post(route('organizer.events.store'), [
            'title' => 'Festival Teranga 2026',
            'description' => 'Une édition mémorable.',
            'event_date' => now()->addMonth()->format('Y-m-d H:i'),
            'location' => 'Stade Abdoulaye Wade',
            'total_tickets' => 300,
            'event_type' => 'festival',
            'status' => Event::STATUS_PUBLISHED,
            'online_sales_enabled' => '1',
            ...$overrides,
        ])->assertSessionHasNoErrors();

        return Event::query()->where('slug', 'festival-teranga-2026')->firstOrFail();
    }

    public function test_the_display_name_is_saved_and_shown_publicly(): void
    {
        $event = $this->store(['organizer_display_name' => 'Moussa Diop']);

        $this->assertSame('Moussa Diop', $event->organizer_display_name);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Organisé par Moussa Diop');
    }

    public function test_the_account_name_is_used_when_empty(): void
    {
        $event = $this->store(['organizer_display_name' => '']);

        $event->refresh();
        $this->assertSame('Moussa Diop', $event->organizer_display_name);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Organisé par Moussa Diop');
    }

    public function test_the_field_is_prefilled_when_editing(): void
    {
        $event = $this->store(['organizer_display_name' => 'Moussa Diop']);

        $this->actingAs($this->organizer)
            ->get(route('organizer.events.edit', $event))
            ->assertOk()
            ->assertSee('Moussa Diop', false)
            ->assertSee("Nom de l'organisateur affiché", false);
    }
}
