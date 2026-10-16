<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\Event;
use App\Models\EventImage;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Event::query()
            ->with('organizer')
            ->withCount('tickets')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
            ->orderByDesc('event_date');

        return view('organizer.events.index', [
            'events' => $query->paginate(12)->withQueryString(),
            'statuses' => [
                Event::STATUS_PUBLISHED => 'Publié',
                Event::STATUS_DRAFT => 'Brouillon',
                Event::STATUS_CANCELLED => 'Annulé',
                Event::STATUS_COMPLETED => 'Terminé',
            ],
        ]);
    }

    public function create(): View
    {
        return view('organizer.events.form', [
            'event' => new Event(['total_tickets' => 100, 'event_type' => 'concert', 'status' => Event::STATUS_PUBLISHED]),
            'types' => Event::TYPES,
            'statuses' => $this->statuses(),
            'artists' => $this->artistOptions(),
            'gallery' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $event = DB::transaction(function () use ($data, $request) {
            $organizerId = $request->user()->isAdmin() && $request->filled('organizer_id')
                ? $request->integer('organizer_id')
                : $request->user()->id;

            $event = new Event([
                ...$data,
                'online_sales_enabled' => $request->boolean('online_sales_enabled', true),
            ]);

            $event->organizer()->associate($organizerId);
            $event->slug = $this->uniqueSlug($event->title);
            $event->save();

            return $event;
        });

        if ($request->hasFile('image')) {
            $this->storeImage($request, $event);
        }

        if ($request->hasFile('venue_image')) {
            $this->storeVenueImage($request, $event);
        }

        if ($request->hasFile('avatar')) {
            $this->storeAvatar($request, $event->organizer);
        }

        return redirect()->route('organizer.events.ticket-types', $event)
            ->with('success', "Événement « {$event->title} » créé. Ajoutez ses types de tickets.");
    }

    public function edit(Request $request, Event $event): View
    {
        $this->authorizeEvent($request, $event);

        return view('organizer.events.form', [
            'event' => $event,
            'types' => Event::TYPES,
            'statuses' => $this->statuses(),
            'artists' => $this->artistOptions(),
            'gallery' => $event->images,
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($request, $event);

        $data = $this->validated($request, $event);

        if ($data['title'] !== $event->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], $event->id);
        }

        $event->update($data);

        if ($request->hasFile('image')) {
            $this->storeImage($request, $event);
        }

        if ($request->hasFile('venue_image')) {
            $this->storeVenueImage($request, $event);
        }

        if ($request->hasFile('avatar')) {
            $event->unsetRelation('organizer');
            $this->storeAvatar($request, $event->organizer);
        }

        return redirect()->route('organizer.events.index')->with('success', 'Événement mis à jour.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($request, $event);

        $event->delete();

        return redirect()->route('organizer.events.index')->with('success', 'Événement supprimé.');
    }

    /* ------------------------------------------------------------------ */

    private function validated(Request $request, ?Event $event = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'event_date' => ['required', 'date', 'after:now'],
            'location' => ['required', 'string', 'max:180'],
            'total_tickets' => ['required', 'integer', 'min:1', 'max:1000000'],
            'event_type' => ['required', Rule::in(array_keys(Event::TYPES))],
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'organizer_display_name' => ['nullable', 'string', 'max:120'],
            'organizer_id' => ['nullable', 'integer', 'exists:users,id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'venue_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'artist_id' => ['nullable', 'integer', Rule::exists('artists', 'id')],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:4320'],
            'tags' => ['nullable', 'string', 'max:250'],
            'online_sales_enabled' => ['nullable', 'boolean'],
        ], [], [
            'title' => 'titre',
            'event_date' => 'date',
            'location' => 'lieu',
            'total_tickets' => 'nombre total de tickets',
            'event_type' => 'type d\'événement',
            'status' => 'statut',
            'organizer_display_name' => 'nom de l\'organisateur affiché',
            'image' => 'image de couverture',
            'venue_image' => 'photo du lieu',
            'avatar' => 'photo de profil',
            'artist_id' => 'artiste',
            'duration_minutes' => 'durée (en minutes)',
            'tags' => 'tags',
        ]);
    }

    private function storeImage(Request $request, Event $event): void
    {
        $path = $request->file('image')->store('events', 'public');

        if ($event->image_path) {
            Storage::disk('public')->delete($event->image_path);
        }

        $event->update(['image_path' => $path]);
    }

    private function storeVenueImage(Request $request, Event $event): void
    {
        $path = $request->file('venue_image')->store('events/venue', 'public');

        if ($event->venue_image_path) {
            Storage::disk('public')->delete($event->venue_image_path);
        }

        $event->update(['venue_image_path' => $path]);
    }

    private function storeAvatar(Request $request, ?User $organizer): void
    {
        if (! $organizer) {
            return;
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        if ($organizer->avatar_path) {
            Storage::disk('public')->delete($organizer->avatar_path);
        }

        $organizer->update(['avatar_path' => $path]);
    }

    /** @return \Illuminate\Support\Collection<int, Artist> */
    private function artistOptions()
    {
        return Artist::orderBy('name')->get(['id', 'name', 'title']);
    }

    /* ---- Galerie photos de l'événement ---- */

    public function storeImages(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeEvent($request, $event);

        $request->validate([
            'images' => ['required', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [], [
            'images' => 'photos',
            'images.*' => 'photo',
        ]);

        $position = (int) $event->images()->max('position') + 1;

        foreach ($request->file('images') as $file) {
            EventImage::create([
                'event_id' => $event->id,
                'image_path' => $file->store('events/gallery', 'public'),
                'position' => $position++,
            ]);
        }

        return back()->with('success', count($request->file('images')).' photo(s) ajoutée(s) à la galerie.');
    }

    public function moveImage(Request $request, Event $event, EventImage $image): RedirectResponse
    {
        $this->authorizeEvent($request, $event);
        abort_unless($image->event_id === $event->id, 404);

        $direction = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ], [], ['direction' => 'direction'])['direction'];

        $images = $event->images()->get();
        $index = $images->search(fn (EventImage $item) => $item->id === $image->id);
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $swapIndex < 0 || $swapIndex >= $images->count()) {
            return back();
        }

        $neighbor = $images[$swapIndex];

        DB::transaction(function () use ($image, $neighbor) {
            $imagePosition = $image->position;
            $image->update(['position' => $neighbor->position]);
            $neighbor->update(['position' => $imagePosition]);
        });

        return back()->with('success', 'Ordre des photos mis à jour.');
    }

    public function destroyImage(Request $request, Event $event, EventImage $image): RedirectResponse
    {
        $this->authorizeEvent($request, $event);
        abort_unless($image->event_id === $event->id, 404);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return back()->with('success', 'Photo supprimée de la galerie.');
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Event::slugFor($title);
        $slug = $base;
        $i = 2;

        while (Event::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function authorizeEvent(Request $request, Event $event): void
    {
        abort_unless($request->user()->isAdmin() || $event->organizer_id === $request->user()->id, 403);
    }

    /** @return array<string, string> */
    private function statuses(): array
    {
        return [
            Event::STATUS_PUBLISHED => 'Publié',
            Event::STATUS_DRAFT => 'Brouillon',
            Event::STATUS_CANCELLED => 'Annulé',
            Event::STATUS_COMPLETED => 'Terminé',
        ];
    }
}
