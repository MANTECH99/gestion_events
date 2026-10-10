@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Historique des validations</h1>
            <p class="mt-1 text-sm text-ink-600">Tous les scans effectués par vos agents de contrôle.</p>
        </div>

        <a href="{{ route('organizer.validations.by-agent') }}" class="btn-outline btn-sm">
            @icon('users', 'w-4 h-4') Par agent
        </a>
    </div>

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Code, nom du porteur…" class="input lg:col-span-2">

        <select name="status" class="input">
            <option value="">Tous les résultats</option>
            <option value="valid" @selected(($filters['status'] ?? '') === 'valid')>Valides</option>
            <option value="invalid" @selected(($filters['status'] ?? '') === 'invalid')>Invalides</option>
        </select>

        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input" aria-label="Du">
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input" aria-label="Au">

        <div class="flex flex-wrap items-center gap-2">
            <button class="btn-primary btn-sm">@icon('filter', 'w-4 h-4') Filtrer</button>
            @if (array_filter($filters))
                <a href="{{ route('organizer.validations.index') }}" class="btn-ghost btn-sm">Réinitialiser</a>
            @endif
        </div>
    </form>

    @if ($records->isEmpty())
        @include('partials.empty', [
            'icon' => 'scan',
            'title' => 'Aucune validation',
            'message' => 'Les scans effectués à l\'entrée apparaîtront ici.',
        ])
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Événement</th>
                            <th>Résultat</th>
                            <th>Ticket</th>
                            <th>Porteur</th>
                            <th>Agent</th>
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
                                <td>
                                    <span class="badge {{ $record->isValid() ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $record->isValid() ? 'Valide' : 'Invalide' }}
                                    </span>
                                </td>
                                <td class="font-mono text-xs font-semibold text-ink-900">{{ $record->ticket_code ?? '—' }}</td>
                                <td class="text-sm text-ink-800">{{ $record->ticket?->holder_name ?? '—' }}</td>
                                <td class="text-sm text-ink-800">{{ $record->agent?->full_name ?? '—' }}</td>
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
