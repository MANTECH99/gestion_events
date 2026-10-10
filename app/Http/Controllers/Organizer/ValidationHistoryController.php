<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Validation as ValidationModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ValidationHistoryController extends Controller
{
    /** Historique global des validations. */
    public function index(Request $request): View
    {
        return view('organizer.validations.index', [
            'records' => $this->query($request)->latest()->paginate(25)->withQueryString(),
            'filters' => $this->filters($request),
        ]);
    }

    /** Historique filtré par agent. */
    public function byAgent(Request $request): View
    {
        $query = $this->query($request)->latest();
        $agents = $this->agents($request);

        if ($request->filled('agent_id')) {
            $query->where('agent_id', $request->integer('agent_id'));
        }

        return view('organizer.validations.by-agent', [
            'records' => $query->paginate(25)->withQueryString(),
            'agents' => $agents,
            'filters' => $this->filters($request),
        ]);
    }

    private function query(Request $request)
    {
        $user = $request->user();

        return ValidationModel::query()
            ->with(['ticket.ticketType', 'ticket', 'agent', 'event'])
            ->when(! $user->isAdmin(), fn ($q) => $q->whereHas('event', fn ($e) => $e->where('organizer_id', $user->id)))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->trim()->value();
                $q->where(function ($sub) use ($term) {
                    $sub->where('ticket_code', 'like', "%{$term}%")
                        ->orWhereHas('ticket', fn ($t) => $t->where('holder_name', 'like', "%{$term}%"));
                });
            });
    }

    /** @return Collection<int, User> */
    private function agents(Request $request)
    {
        $user = $request->user();

        return User::query()
            ->whereIn('role', User::AGENT_ROLES)
            ->when(! $user->isAdmin(), fn ($q) => $q->where(function ($sub) use ($user) {
                $sub->where('id', $user->id)->orWhere('organizer_id', $user->id);
            }))
            ->orderBy('first_name')
            ->get();
    }

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->only(['status', 'from', 'to', 'q', 'agent_id']);
    }
}
