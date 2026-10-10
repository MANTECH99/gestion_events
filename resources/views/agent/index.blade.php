@extends('layouts.app', ['title' => 'Espace agent'])

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">Espace agent</h1>
                <p class="mt-1 text-sm text-ink-600">
                    Tickets valides{{ $event ? ' pour « '.$event->title.' »' : '' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($events->count() > 1)
                    <form method="GET" class="flex gap-2">
                        <select name="event" onchange="this.form.submit()" class="input py-2 text-sm">
                            <option value="">Tous les événements</option>
                            @foreach ($events as $e)
                                <option value="{{ $e->id }}" @selected($event?->id === $e->id)>
                                    {{ $e->title }}
                                </option>
                            @endforeach
                        </select>
                        <noscript><button class="btn-outline btn-sm">Filtrer</button></noscript>
                    </form>
                @endif

                <a href="{{ route('agent.scan', $event ? ['event' => $event->id] : []) }}" class="btn-primary btn-sm">
                    @icon('scan', 'w-4 h-4') Scanner
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            @include('partials.stat', [
                'label' => 'Billets valides',
                'value' => $tickets->total(),
                'icon' => 'ticket',
                'tone' => 'brand',
            ])
            @include('partials.stat', [
                'label' => 'Déjà utilisés',
                'value' => $usedCount,
                'icon' => 'check-circle',
                'tone' => 'emerald',
            ])
            @include('partials.stat', [
                'label' => 'Événements',
                'value' => $events->count(),
                'icon' => 'calendar',
                'tone' => 'orange',
            ])
        </div>

        <div class="card mt-6 overflow-hidden">
            <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                <h2 class="font-semibold text-ink-900">Billets en attente d'entrée</h2>
                <span class="text-xs text-ink-500">Les billets sont à usage unique.</span>
            </div>

            @if ($tickets->isEmpty())
                <div class="p-12 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-ink-100 text-ink-400">
                        @icon('ticket', 'w-6 h-6')
                    </span>
                    <p class="mt-3 font-medium text-ink-800">Aucun billet valide</p>
                    <p class="mt-1 text-sm text-ink-500">Les billets apparaîtront ici après confirmation du paiement.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Titulaire</th>
                                <th>Type</th>
                                <th>Réservation</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tickets as $ticket)
                                <tr>
                                    <td class="font-mono text-xs font-semibold text-ink-900">{{ $ticket->code }}</td>
                                    <td>
                                        <p class="font-medium text-ink-900">{{ $ticket->holder_name }}</p>
                                        @if ($ticket->holder_email)
                                            <p class="text-xs text-ink-500">{{ $ticket->holder_email }}</p>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-ink-100 text-ink-700">{{ $ticket->ticketType->name }}</span></td>
                                    <td class="font-mono text-xs text-ink-600">{{ $ticket->reservation->reference }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="btn-ghost btn-sm">
                                            @icon('eye', 'w-4 h-4') Voir
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-ink-100 px-5 py-3">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection