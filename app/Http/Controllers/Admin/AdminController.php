<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\User;
use App\Services\StatsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function __construct(private readonly StatsService $stats) {}

    public function dashboard(Request $request): View
    {
        [$from, $to] = $request->filled('from')
            ? [$request->date('from')?->startOfDay(), $request->date('to')?->endOfDay()]
            : [now()->subDays(29)->startOfDay(), now()->endOfDay()];

        $organizerId = $request->integer('organizer_id');
        $eventId = $request->integer('event_id');

        $filterEvent = $eventId !== 0 ? Event::find($eventId) : null;

        // Un événement d'un autre organisateur est incohérent : on rabat le filtre
        // sur l'organisateur sélectionné.
        if ($filterEvent && $organizerId !== 0 && $filterEvent->organizer_id !== $organizerId) {
            $filterEvent = null;
        }

        $eventIds = match (true) {
            $filterEvent !== null => [$filterEvent->id],
            $organizerId !== 0 => Event::where('organizer_id', $organizerId)->pluck('id')->all(),
            default => null,
        };

        $organizers = User::where('role', User::ROLE_ORGANIZER)
            ->withCount('events')
            ->withSum(
                ['eventReservations as revenue' => fn ($q) => $q->where('payment_status', Reservation::STATUS_PAID)],
                'total_amount'
            )
            ->orderBy('last_name')
            ->get();

        // Pour les compteurs dérivés, on cible l'organisateur du filtre :
        // l'organisateur sélectionné, ou celui de l'événement sélectionné.
        $scopeOrganizerId = $filterEvent?->organizer_id ?? ($organizerId !== 0 ? $organizerId : null);

        return view('admin.dashboard', [
            'summary' => $this->stats->summary($from, $to, $eventIds),
            'evolution' => $this->stats->salesEvolution($from, $to, $eventIds),
            'revenueByEvent' => $this->stats->revenueByEvent($from, $to, $eventIds),
            'counts' => [
                'organizers' => User::where('role', User::ROLE_ORGANIZER)->count(),
                'agents' => User::where('role', User::ROLE_AGENT)
                    ->when($scopeOrganizerId, fn ($q) => $q->where('organizer_id', $scopeOrganizerId))
                    ->count(),
                'sellers' => User::where('role', User::ROLE_USER)
                    ->when($scopeOrganizerId, fn ($q) => $q->where('organizer_id', $scopeOrganizerId))
                    ->count(),
                'events' => $eventIds === null ? Event::count() : Event::whereIn('id', $eventIds)->count(),
                'tickets' => $eventIds === null ? Ticket::count() : Ticket::whereIn('event_id', $eventIds)->count(),
            ],
            'organizers' => $organizers,
            'filterOrganizerId' => $organizerId,
            'filterEventId' => $filterEvent?->id ?? 0,
            'filterEvents' => Event::query()->orderByDesc('event_date')->get(['id', 'organizer_id', 'title']),
            'from' => $from,
            'to' => $to,
        ]);
    }

    /** Gestion des organisateurs. */
    public function organizers(Request $request): View
    {
        return view('admin.organizers.index', [
            'organizers' => User::query()
                ->where('role', User::ROLE_ORGANIZER)
                ->withCount('events')
                ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
                ->orderBy('last_name')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    /** Fiche détaillée d'un organisateur. */
    public function showOrganizer(Request $request, User $organizer): View
    {
        abort_unless($organizer->isOrganizer(), 404);

        $eventId = $request->integer('event_id');

        $filterEvent = $eventId !== 0
            ? $organizer->events()->whereKey($eventId)->first()
            : null;

        $eventIds = $filterEvent ? [$filterEvent->id] : null;

        $events = $organizer->events()
            ->orderByDesc('event_date')
            ->get();

        return view('admin.organizers.show', [
            'organizer' => $organizer,
            'events' => $events,
            'summary' => $this->stats->summary(null, null, $eventIds ?? $events->pluck('id')->all()),
            'filterEventId' => $filterEvent?->id ?? 0,
            'pointsOfSale' => $organizer->pointsOfSale()->orderBy('first_name')->get(),
            'agents' => $organizer->agents()->orderBy('first_name')->get(),
        ]);
    }

    public function storeOrganizer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[\pL\pN _-]+$/u', 'unique:users,username'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [], [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'email',
            'password' => 'mot de passe',
        ]);

        User::create([...$data, 'role' => User::ROLE_ORGANIZER]);

        return back()->with('success', 'Organisateur créé.');
    }

    public function updateOrganizer(Request $request, User $organizer): RedirectResponse
    {
        abort_unless($organizer->isOrganizer(), 404);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[\pL\pN _-]+$/u', 'unique:users,username,'.$organizer->id],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$organizer->id],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'email' => 'email',
        ]);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $data['is_active'] = $request->boolean('is_active');

        $organizer->update($data);

        return back()->with('success', 'Organisateur mis à jour.');
    }

    public function destroyOrganizer(Request $request, User $organizer): RedirectResponse
    {
        abort_unless($organizer->isOrganizer(), 404);

        if ($organizer->events()->exists()) {
            return back()->with('error', 'Cet organisateur possède des événements : désactivez son compte.');
        }

        $organizer->delete();

        return back()->with('success', 'Organisateur supprimé.');
    }

    /** Liste de tous les utilisateurs (vue transverse). */
    public function users(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->with('organizer')
                ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')->value()))
                ->when($request->filled('q'), fn ($q) => $q->search($request->string('q')->trim()->value()))
                ->orderBy('role')
                ->orderBy('last_name')
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only(['role', 'q']),
        ]);
    }

    public function verify(User $target): RedirectResponse
    {
        $target->update(['is_verified' => ! $target->is_verified]);

        return back()->with(
            'success',
            $target->is_verified
                ? "{$target->full_name} est maintenant vérifié."
                : "Vérification retirée à {$target->full_name}."
        );
    }
}
