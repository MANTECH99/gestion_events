<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Event::query()
            ->published()
            ->upcoming()
            ->with('organizer')
            ->withMin('ticketTypes', 'price');

        if ($request->filled('q')) {
            $query->search($request->string('q')->trim()->value());
        }

        if ($request->filled('type') && array_key_exists($request->string('type')->value(), Event::TYPES)) {
            $query->where('event_type', $request->string('type')->value());
        }

        $events = $query->orderBy('event_date')->paginate(9)->withQueryString();

        return view('home', [
            'events' => $events,
            'types' => Event::TYPES,
            'totalTickets' => Event::query()->published()->upcoming()->count(),
        ]);
    }
}
