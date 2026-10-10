@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('organizer.validations.index') }}" class="btn-ghost btn-sm">
            @icon('arrow-left', 'w-4 h-4') Retour
        </a>
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Validations par agent</h1>
    </div>

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
        <select name="agent_id" onchange="this.form.submit()" class="input lg:col-span-2">
            <option value="">Tous les agents</option>
            @foreach ($agents as $agent)
                <option value="{{ $agent->id }}" @selected((int) ($filters['agent_id'] ?? 0) === $agent->id)>
                    {{ $agent->full_name }} ({{ $agent->isAgent() ? $agent->role_label : 'Compte' }})
                </option>
            @endforeach
        </select>

        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Code, nom du porteur…" class="input">

        <select name="status" class="input">
            <option value="">Tous les résultats</option>
            <option value="valid" @selected(($filters['status'] ?? '') === 'valid')>Valides</option>
            <option value="invalid" @selected(($filters['status'] ?? '') === 'invalid')>Invalides</option>
        </select>

        <div class="flex gap-2">
            <button class="btn-primary btn-sm flex-1">Filtrer</button>
            @if (array_filter($filters))
                <a href="{{ route('organizer.validations.by-agent') }}" class="btn-ghost btn-sm">Réinitialiser</a>
            @endif
        </div>
    </form>

    @php
        $perAgent = $records->getCollection()->groupBy(fn ($r) => $r->agent?->full_name ?? 'Agent inconnu');
    @endphp

    @if ($records->isEmpty())
        @include('partials.empty', [
            'icon' => 'users',
            'title' => 'Aucune validation',
            'message' => 'Aucun scan n\'a encore été enregistré par vos agents.',
        ])
    @else
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($perAgent as $agentName => $group)
                @php
                    $valid = $group->filter->isValid()->count();
                    $total = $group->count();
                @endphp
                @include('partials.stat', [
                    'label' => $agentName,
                    'value' => number_format($valid, 0, ',', ' ').' / '.number_format($total, 0, ',', ' '),
                    'icon' => 'user-check',
                    'tone' => $valid === $total ? 'emerald' : 'orange',
                    'hint' => $total - $valid.' scan(s) refusé(s)',
                ])
            @endforeach
        </div>

        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Événement</th>
                            <th>Agent</th>
                            <th>Résultat</th>
                            <th>Ticket</th>
                            <th>Porteur</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                <td class="whitespace-nowrap text-xs text-ink-600">{{ $record->created_at->translatedFormat('d/m/Y H:i') }}</td>
                                <td>
                                    <p class="max-w-[12rem] truncate text-sm text-ink-800">{{ $record->event?->title ?? '—' }}</p>
                                </td>
                                <td class="text-sm font-medium text-ink-900">{{ $record->agent?->full_name ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $record->isValid() ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $record->isValid() ? 'Valide' : 'Invalide' }}
                                    </span>
                                </td>
                                <td class="font-mono text-xs font-semibold text-ink-900">{{ $record->ticket_code ?? '—' }}</td>
                                <td class="text-sm text-ink-800">{{ $record->ticket?->holder_name ?? '—' }}</td>
                                <td class="max-w-xs text-xs text-ink-600">{{ $record->message }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-3">{{ $records->links() }}</div>
        </div>
    @endif
@endsection
