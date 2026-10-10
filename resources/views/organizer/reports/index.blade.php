@extends('layouts.organizer')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Rapports</h1>
        <p class="mt-1 text-sm text-ink-600">Générez le rapport des réservations pour chaque événement.</p>
    </div>

    @if ($events->isEmpty())
        @include('partials.empty', [
            'icon' => 'document',
            'title' => 'Aucun événement',
            'message' => 'Créez un événement pour générer des rapports.',
            'action' => route('organizer.events.create'),
            'actionLabel' => 'Créer un événement',
        ])
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Événement</th>
                            <th>Date</th>
                            <th class="text-right">Réservations</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr>
                                <td>
                                    <p class="font-semibold text-ink-900">{{ $event->title }}</p>
                                    <p class="text-xs text-ink-500">{{ $event->location }}</p>
                                </td>
                                <td class="whitespace-nowrap text-sm text-ink-700">{{ event_date_fr($event->event_date) }}</td>
                                <td class="text-right font-semibold text-ink-900">{{ $event->reservations_count }}</td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="{{ route('organizer.reports.show', $event) }}" class="btn-ghost btn-sm" title="Consulter">
                                            @icon('eye', 'w-4 h-4')
                                        </a>
                                        <a href="{{ route('organizer.reports.pdf', $event) }}" class="btn-outline btn-sm">
                                            @icon('download', 'w-4 h-4') PDF
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
