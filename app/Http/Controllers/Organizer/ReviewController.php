<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventReview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $eventOptions = Event::when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->orderByDesc('event_date')
            ->get(['id', 'title']);

        $query = EventReview::query()
            ->with(['event', 'user'])
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('event', fn ($e) => $e->where('organizer_id', $user->id)))
            ->when($request->filled('event_id'), fn ($q) => $q->where('event_id', $request->integer('event_id')))
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', $request->integer('rating')))
            ->orderByDesc('created_at');

        return view('organizer.reviews.index', [
            'reviews' => $query->paginate(20)->withQueryString(),
            'events' => $eventOptions,
            'filters' => $request->only(['event_id', 'rating']),
        ]);
    }

    public function destroy(Request $request, EventReview $review): RedirectResponse
    {
        abort_unless($request->user()->isAdmin() || $review->event?->organizer_id === $request->user()->id, 403);

        $review->delete();

        return back()->with('success', 'Avis supprimé.');
    }
}
