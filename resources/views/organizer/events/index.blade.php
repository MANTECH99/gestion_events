@extends('layouts.organizer')

@section('content')
    <div class="card mb-5 flex flex-wrap items-center gap-3 p-4">
        <form method="GET" class="flex min-w-[16rem] flex-1 flex-wrap items-center gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Rechercher un événement…" class="input flex-1 py-2">
            <select name="status" onchange="this.form.submit()" class="input w-auto py-2 text-sm">
                <option value="">Tous les statuts</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-outline btn-sm">Filtrer</button>
        </form>

        <a href="{{ route('organizer.events.create') }}" class="btn-primary btn-sm">
            @icon('plus', 'w-4 h-4') Nouvel événement
        </a>
    </div>

    @if ($events->isEmpty())
        @include('partials.empty', [
            'icon' => 'calendar',
            'title' => 'Aucun événement',
            'message' => 'Créez votre premier événement pour commencer à vendre des billets.',
            'action' => route('organizer.events.create'),
            'actionLabel' => 'Créer un événement',
        ])
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($events as $event)
                <div class="card overflow-hidden">
                    <div class="relative h-40 bg-gradient-to-br from-brand-600 to-violet-600">
                        @if ($event->image_path)
                            <img src="{{ Storage::url($event->image_path) }}" alt="{{ $event->title }}"
                                 class="h-full w-full object-cover">
                        @else
                            <div class="grid h-full place-items-center text-5xl opacity-70">
                                {{ ticket_type_icon($event->event_type) }}
                            </div>
                        @endif

                        <span class="badge absolute left-3 top-3 bg-white/95 text-ink-800">
                            {{ $statuses[$event->status] ?? $event->status }}
                        </span>
                    </div>

                    <div class="p-5">
                        <h3 class="line-clamp-2 font-bold leading-snug text-ink-950">{{ $event->title }}</h3>

                        <dl class="mt-2 space-y-1 text-xs text-ink-600">
                            <div class="flex items-center gap-1.5">
                                @icon('calendar', 'w-3.5 h-3.5') {{ event_date_fr($event->event_date) }}
                            </div>
                            <div class="flex items-center gap-1.5">
                                @icon('map-pin', 'w-3.5 h-3.5') <span class="truncate">{{ $event->location }}</span>
                            </div>
                        </dl>

                        <div class="mt-3 flex items-center gap-3 text-xs">
                            <span class="badge bg-ink-100 text-ink-700">{{ $event->tickets_count }} ticket(s)</span>
                            <span class="text-ink-500">{{ $event->min_price !== null ? 'dès '.$event->min_price_label : 'Gratuit' }}</span>
                        </div>

                        <div class="mt-4 flex gap-2 border-t border-ink-100 pt-4">
                            <a href="{{ route('organizer.events.ticket-types', $event) }}" class="btn-primary btn-sm flex-1 justify-center">
                                @icon('ticket', 'w-4 h-4') Types
                            </a>
                            <a href="{{ route('organizer.events.edit', $event) }}" class="btn-outline btn-sm" title="Modifier">
                                @icon('pencil', 'w-4 h-4')
                            </a>
                            @if ($event->isPublished())
                                <a href="{{ route('events.show', $event) }}" target="_blank" rel="noopener" class="btn-ghost btn-sm" title="Voir la page publique">
                                    @icon('eye', 'w-4 h-4')
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $events->links() }}</div>
    @endif
@endsection
