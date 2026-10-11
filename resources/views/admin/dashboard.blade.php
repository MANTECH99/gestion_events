@extends('layouts.admin')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">Vue d'ensemble</h1>
            <p class="mt-1 text-sm text-ink-600">
                Du {{ $from->translatedFormat('d M Y') }} au {{ $to->translatedFormat('d M Y') }}
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('admin.organizers.index') }}" class="btn-primary btn-sm justify-center">
                    @icon('users', 'w-4 h-4') Organisateurs
                </a>
                <a href="{{ route('admin.users.index') }}" class="btn-outline btn-sm justify-center">
                    @icon('user', 'w-4 h-4') Tous les utilisateurs
                </a>
            </div>
            <form method="GET" class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center"
                  x-data="{
                      organizers: @js($organizers->map(fn ($o) => ['id' => $o->id, 'name' => $o->full_name])->values()),
                      eventsByOrganizer: @js($filterEvents->groupBy('organizer_id')
                          ->map(fn ($set) => $set->map(fn ($e) => ['id' => $e->id, 'title' => $e->title]))),
                      organizerId: @js((string) $filterOrganizerId),
                      eventId: @js((string) $filterEventId),
                      get events() {
                          return this.eventsByOrganizer[this.organizerId] ?? [];
                      },
                  }">
                <select name="organizer_id" x-model="organizerId" @change="eventId = ''"
                        class="input w-full py-1.5 text-sm sm:w-auto sm:max-w-52" aria-label="Organisateur">
                    <option value="">Tous les organisateurs</option>
                    <template x-for="o in organizers" :key="o.id">
                        <option :value="o.id" x-text="o.name"></option>
                    </template>
                </select>
                <select name="event_id" x-model="eventId"
                        class="input w-full py-1.5 text-sm sm:w-auto sm:max-w-60" aria-label="Événement">
                    <option value="">Tous les événements</option>
                    <template x-for="e in events" :key="e.id">
                        <option :value="e.id" x-text="e.title"></option>
                    </template>
                </select>
                <div class="grid grid-cols-1 gap-2 sm:flex sm:items-center">
                    <input type="date" name="from" value="{{ $from->toDateString() }}" class="input min-w-0 py-1.5 text-sm sm:w-auto" aria-label="Du">
                    <input type="date" name="to" value="{{ $to->toDateString() }}" class="input min-w-0 py-1.5 text-sm sm:w-auto" aria-label="Au">
                </div>
                <button class="btn-outline btn-sm w-full justify-center sm:w-auto">Filtrer</button>
            </form>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('partials.stat', [
            'label' => 'Revenus encaissés',
            'value' => money($summary['total_revenue']),
            'icon' => 'currency',
            'tone' => 'brand',
            'hint' => $summary['paid_reservations'].' réservation(s) payée(s)',
        ])
        @include('partials.stat', [
            'label' => 'Tickets vendus',
            'value' => number_format($summary['total_tickets'], 0, ',', ' '),
            'icon' => 'ticket',
            'tone' => 'violet',
        ])
        @include('partials.stat', [
            'label' => 'Validations à l\'entrée',
            'value' => number_format($summary['validations'], 0, ',', ' '),
            'icon' => 'scan',
            'tone' => 'orange',
            'hint' => $summary['tickets_used'].' billets utilisés',
        ])
        @include('partials.stat', [
            'label' => 'Réservations',
            'value' => number_format($summary['reservations'], 0, ',', ' '),
            'icon' => 'list',
            'tone' => 'sky',
        ])
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ([
            ['Organisateurs', $counts['organizers'], 'briefcase', 'brand'],
            ['Agents', $counts['agents'], 'user-check', 'violet'],
            ['Points de vente', $counts['sellers'], 'store', 'orange'],
            ['Événements', $counts['events'], 'calendar', 'emerald'],
            ['Billets émis', $counts['tickets'], 'ticket', 'sky'],
        ] as [$label, $value, $icon, $tone])
            @include('partials.stat', ['label' => $label, 'value' => $value, 'icon' => $icon, 'tone' => $tone])
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <h2 class="font-semibold text-ink-900">Évolution des ventes</h2>
            <div class="mt-4 h-72">
                <canvas id="sales-chart"></canvas>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-ink-100 px-5 py-4">
                <h2 class="font-semibold text-ink-900">Organisateurs</h2>
            </div>

            @if ($organizers->isEmpty())
                @include('partials.empty', [
                    'icon' => 'briefcase',
                    'title' => 'Aucun organisateur',
                    'message' => 'Créez un compte organisateur pour qu\'il puisse publier des événements.',
                    'action' => route('admin.organizers.index'),
                    'actionLabel' => 'Gérer les organisateurs',
                ])
            @else
                <ul class="divide-y divide-ink-100">
                    @foreach ($organizers as $organizer)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-violet-100 text-xs font-bold text-violet-700">
                                {{ $organizer->initials }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-ink-900">{{ $organizer->full_name }}</p>
                                <p class="text-xs text-ink-500">{{ $organizer->events_count }} événement(s)</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-ink-900">{{ money_short($organizer->revenue ?? 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="card mt-6 p-5">
        <h2 class="font-semibold text-ink-900">Revenus par événement</h2>
        <div class="mt-4 h-72">
            <canvas id="revenue-chart"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        salesEvolutionChart(
            document.getElementById('sales-chart'),
            @js(collect($evolution)->pluck('label')),
            @js(collect($evolution)->pluck('revenue')),
            @js(collect($evolution)->pluck('tickets')),
        );

        horizontalBarChart(
            document.getElementById('revenue-chart'),
            @js(collect($revenueByEvent)->pluck('title')),
            @js(collect($revenueByEvent)->pluck('revenue')),
        );
    });
</script>
@endpush