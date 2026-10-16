<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Contracts\View\View;

class EventController extends Controller
{
    public function show(Event $event): View
    {
        abort_unless($event->status !== Event::STATUS_DRAFT || auth()->user()?->isAdmin() || auth()->user()?->id === $event->organizer_id, 404);

        $event->load('organizer', 'ticketTypes', 'artist', 'images', 'reviews');

        return view('events.show', [
            'event' => $event,
        ]);
    }
}
