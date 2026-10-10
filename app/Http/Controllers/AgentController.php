<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Validation;
use App\Services\TicketValidationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Espace agent de contrôle : liste des tickets à valider + scanner QR code.
 */
class AgentController extends Controller
{
    public function __construct(private readonly TicketValidationService $scanner) {}

    /** Liste des tickets à valider pour un événement. */
    public function index(Request $request): View
    {
        if (! $this->isAgent($request)) {
            return $this->loginRequired($request);
        }

        $agent = $request->user();
        $events = $this->eventsFor($agent);
        $event = $this->resolveEvent($request, $events);

        // Sans événement précis, on se limite aux événements contrôlés par
        // l'agent (sinon les billets d'autres organisateurs fuiteraient).
        $scopeIds = $event ? [$event->id] : $events->pluck('id')->all();

        $tickets = Ticket::query()
            ->whereIn('event_id', $scopeIds)
            ->where('status', Ticket::STATUS_VALID)
            ->with(['event', 'ticketType', 'reservation'])
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('agent.index', [
            'tickets' => $tickets,
            'events' => $events,
            'event' => $event,
            'usedCount' => Ticket::query()
                ->whereIn('event_id', $scopeIds)
                ->where('status', Ticket::STATUS_USED)
                ->count(),
        ]);
    }

    /** Scanner de QR code. */
    public function scan(Request $request): View
    {
        if (! $this->isAgent($request)) {
            return $this->loginRequired($request);
        }

        $agent = $request->user();
        $events = $this->eventsFor($agent);

        return view('agent.scan', [
            'events' => $events,
            'event' => $this->resolveEvent($request, $events),
        ]);
    }

    /** Traitement d'un scan (fetch JSON par le scanner). */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:120'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        $agent = $request->user();

        if (! $agent?->isAgent()) {
            return response()->json(['ok' => false, 'message' => 'Compte agent requis.'], 403);
        }

        // L'événement transmis doit appartenir au périmètre de l'agent.
        $event = $request->filled('event_id')
            ? $this->eventsFor($agent)->firstWhere('id', $request->integer('event_id'))
            : null;

        if ($request->filled('event_id') && ! $event) {
            return response()->json(['ok' => false, 'message' => 'Événement non autorisé.'], 403);
        }

        $result = $this->scanner->scan($agent, $request->string('code')->value(), $event);

        return response()->json([
            'ok' => true,
            'outcome' => $result['outcome'],
            'title' => $result['title'],
            'message' => $result['message'],
            'tone' => $result['tone'],
            'ticket' => $result['ticket'] ? [
                'code' => $result['ticket']->code,
                'holder' => $result['ticket']->holder_name,
                'type' => $result['ticket']->ticketType?->name,
                'event' => $result['ticket']->event?->title,
                'status' => $result['ticket']->status_label,
                'used_at' => $result['ticket']->used_at?->translatedFormat('d/m/Y H:i'),
                'validated_by' => $result['ticket']->validator?->full_name,
            ] : null,
            'redirect' => route('agent.result', $result['validation']->id),
        ]);
    }

    /** Écran de résultat (vert / rouge) après un scan. */
    public function result(Request $request, int $validation): View
    {
        if (! $this->isAgent($request)) {
            return $this->loginRequired($request);
        }

        $record = Validation::with(['ticket.event', 'ticket.ticketType', 'agent'])
            ->findOrFail($validation);

        $valid = $record->isValid();

        return view('agent.result', [
            'record' => $record,
            'ticket' => $record->ticket,
            'valid' => $valid,
            'title' => $valid ? 'Entrée autorisée' : 'Ticket invalide',
            'message' => $record->message,
        ]);
    }

    /** Validation manuelle par saisie du code (sans caméra). */
    public function manual(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:120']]);

        $agent = $request->user();

        if (! $agent?->isAgent()) {
            return response()->json(['ok' => false, 'message' => 'Compte agent requis.'], 403);
        }

        $result = $this->scanner->scan($agent, TicketValidationService::cleanInput($request->string('code')->value()));

        return response()->json([
            'ok' => true,
            'outcome' => $result['outcome'],
            'message' => $result['message'],
            'redirect' => route('agent.result', $result['validation']->id),
        ]);
    }

    /* ------------------------------------------------------------------ */

    private function isAgent(Request $request): bool
    {
        return (bool) $request->user()?->isAgent();
    }

    private function loginRequired(Request $request): View
    {
        return view('agent.login-required', [
            'loggedIn' => (bool) $request->user(),
            'isWrongRole' => $request->user() !== null && ! $request->user()->isAgent(),
        ]);
    }

    /** @return Collection<int, Event> */
    private function eventsFor(User $agent)
    {
        if ($agent->isAdmin()) {
            return Event::query()->orderByDesc('event_date')->get();
        }

        return Event::query()
            ->where('organizer_id', $agent->organizer_id ?? $agent->id)
            ->orderByDesc('event_date')
            ->get();
    }

    /** @param  Collection<int, Event>  $events */
    private function resolveEvent(Request $request, $events): ?Event
    {
        if ($request->filled('event') && $events->contains('id', $request->integer('event'))) {
            return $events->firstWhere('id', $request->integer('event'));
        }

        if ($events->count() === 1) {
            return $events->first();
        }

        return null;
    }
}
