<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EventReviewController extends Controller
{
    /**
     * Publication d'un avis public sur un événement — ouvert à tous,
     * compte ou non (le nom de compte remplace alors le nom saisi).
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'author_name' => ['required', 'string', 'max:60'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'rating.min' => 'La note doit être comprise entre 1 et 5.',
            'rating.max' => 'La note doit être comprise entre 1 et 5.',
        ], [
            'rating' => 'note',
            'author_name' => 'nom',
            'comment' => 'commentaire',
        ]);

        EventReview::create([
            'event_id' => $event->id,
            'user_id' => $request->user()?->id,
            'author_name' => $request->user()?->full_name ?: $data['author_name'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return back()->with('review_success', 'Merci pour votre avis ! Il est publié.');
    }
}
