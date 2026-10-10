@extends('layouts.admin')

@section('content')
    @php
        $statusTones = [
            \App\Models\Event::STATUS_DRAFT => 'bg-ink-100 text-ink-700',
            \App\Models\Event::STATUS_PUBLISHED => 'bg-emerald-100 text-emerald-700',
            \App\Models\Event::STATUS_COMPLETED => 'bg-sky-100 text-sky-700',
            \App\Models\Event::STATUS_CANCELLED => 'bg-rose-100 text-rose-700',
        ];
    @endphp

    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.organizers.index') }}" class="btn-ghost btn-sm">
            @icon('arrow-left', 'w-4 h-4') Retour
        </a>
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Organisateur</h1>

        <form method="GET" class="ml-auto flex items-center gap-2">
            <select name="event_id" class="input max-w-72 py-1.5 text-sm" aria-label="Événement">
                <option value="">Tous les événements</option>
                @foreach ($events as $event)
                    <option value="{{ $event->id }}" @selected($filterEventId === $event->id)>{{ $event->title }}</option>
                @endforeach
            </select>
            <button class="btn-outline btn-sm">Filtrer</button>
        </form>
    </div>

    <div class="card flex flex-wrap items-center gap-4 p-5">
        <span class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-violet-100 text-lg font-bold text-violet-700">
            {{ $organizer->initials }}
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Nom de l'organisateur</p>
            <p class="text-xl font-extrabold tracking-tight text-ink-950">{{ $organizer->full_name }}</p>
            <p class="mt-0.5 truncate text-sm text-ink-600">{{ $organizer->username }} · {{ $organizer->email }}</p>
        </div>
        <span class="badge {{ $organizer->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
            {{ $organizer->is_active ? 'Actif' : 'Désactivé' }}
        </span>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-3">
        @include('partials.stat', [
            'label' => 'Événements',
            'value' => number_format($events->count(), 0, ',', ' '),
            'icon' => 'calendar',
            'tone' => 'emerald',
        ])
        @include('partials.stat', [
            'label' => 'Billets vendus',
            'value' => number_format($summary['total_tickets'], 0, ',', ' '),
            'icon' => 'ticket',
            'tone' => 'violet',
        ])
        @include('partials.stat', [
            'label' => 'Recette',
            'value' => money($summary['total_revenue']),
            'icon' => 'currency',
            'tone' => 'brand',
        ])
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-ink-100 px-5 py-4">
            <h2 class="font-semibold text-ink-900">Événements ({{ $events->count() }})</h2>
        </div>

        @if ($events->isEmpty())
            @include('partials.empty', [
                'icon' => 'calendar',
                'title' => 'Aucun événement',
                'message' => 'Cet organisateur n\'a créé aucun événement.',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Événement</th>
                            <th>Date</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td>
                                    <p class="font-semibold text-ink-900">{{ $event->title }}</p>
                                    <p class="text-xs text-ink-500">{{ $event->location }}</p>
                                </td>
                                <td class="text-sm text-ink-700">{{ event_date_fr($event->event_date, 'd M Y') }}</td>
                                <td>
                                    <span class="badge {{ $statusTones[$event->status] ?? 'bg-ink-100 text-ink-700' }}">
                                        {{ $event->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-ink-100 px-5 py-4">
            <h2 class="font-semibold text-ink-900">Points de vente ({{ $pointsOfSale->count() }})</h2>
        </div>

        @if ($pointsOfSale->isEmpty())
            @include('partials.empty', [
                'icon' => 'store',
                'title' => 'Aucun point de vente',
                'message' => 'Cet organisateur n\'a rattaché aucun point de vente.',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Contact</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pointsOfSale as $pos)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-100 text-xs font-bold text-brand-700">
                                            {{ $pos->initials }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink-900">{{ $pos->full_name }}</p>
                                            <p class="truncate text-xs text-ink-500">{{ $pos->point_of_sale ?: $pos->username }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="truncate text-xs text-ink-700">{{ $pos->email }}</p>
                                    @if ($pos->phone)
                                        <p class="text-xs text-ink-500">{{ $pos->phone }}</p>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $pos->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $pos->is_active ? 'Actif' : 'Désactivé' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-ink-100 px-5 py-4">
            <h2 class="font-semibold text-ink-900">Agents ({{ $agents->count() }})</h2>
        </div>

        @if ($agents->isEmpty())
            @include('partials.empty', [
                'icon' => 'user-check',
                'title' => 'Aucun agent',
                'message' => 'Cet organisateur n\'a aucun agent de contrôle.',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Contact</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agents as $agent)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-violet-100 text-xs font-bold text-violet-700">
                                            {{ $agent->initials }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink-900">{{ $agent->full_name }}</p>
                                            <p class="truncate text-xs text-ink-500">{{ $agent->username }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="truncate text-xs text-ink-700">{{ $agent->email }}</p>
                                    @if ($agent->phone)
                                        <p class="text-xs text-ink-500">{{ $agent->phone }}</p>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $agent->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $agent->is_active ? 'Actif' : 'Désactivé' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection