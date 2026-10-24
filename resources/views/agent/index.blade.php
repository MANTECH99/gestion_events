@extends('layouts.app', ['title' => 'Espace agent'])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
    <div class="mx-auto max-w-7xl px-4 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' }} sm:px-6 lg:px-8 pb-28 lg:pb-8">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Espace agent</h1>
                <p class="mt-1 text-sm text-white/60">
                    Tickets valides{{ $event ? ' pour « '.$event->title.' »' : '' }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if ($events->count() > 1)
                    <form method="GET" class="flex gap-2">
                        <select name="event" onchange="this.form.submit()" class="input-dark py-2 text-sm">
                            <option value="">Tous les événements</option>
                            @foreach ($events as $e)
                                <option value="{{ $e->id }}" @selected($event?->id === $e->id)>
                                    {{ $e->title }}
                                </option>
                            @endforeach
                        </select>
                        <noscript><button class="btn-invert btn-sm !px-3 !py-1.5 text-xs">Filtrer</button></noscript>
                    </form>
                @endif

                <a href="{{ route('agent.scan', $event ? ['event' => $event->id] : []) }}" class="btn-hero-primary btn-sm !px-3 !py-1.5 text-xs">
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
                'dark' => true,
            ])
            @include('partials.stat', [
                'label' => 'Déjà utilisés',
                'value' => $usedCount,
                'icon' => 'check-circle',
                'tone' => 'emerald',
                'dark' => true,
            ])
            @include('partials.stat', [
                'label' => 'Événements',
                'value' => $events->count(),
                'icon' => 'calendar',
                'tone' => 'orange',
                'dark' => true,
            ])
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                <h2 class="font-semibold text-white">Billets en attente d'entrée</h2>
                <span class="text-xs text-white/50">Les billets sont à usage unique.</span>
            </div>

            @if ($tickets->isEmpty())
                @include('partials.empty', [
                    'icon' => 'ticket',
                    'title' => 'Aucun billet valide',
                    'message' => 'Les billets apparaîtront ici après confirmation du paiement.',
                    'dark' => true,
                ])
            @else
                <div class="overflow-x-auto">
                    <table class="table table-dark">
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
                                    <td class="font-mono text-xs font-semibold text-white">{{ $ticket->code }}</td>
                                    <td>
                                        <p class="font-medium text-white">{{ $ticket->holder_name }}</p>
                                        @if ($ticket->holder_email)
                                            <p class="text-xs text-white/50">{{ $ticket->holder_email }}</p>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-white/10 text-white/80">{{ $ticket->ticketType->name }}</span></td>
                                    <td class="font-mono text-xs text-white/60">{{ $ticket->reservation->reference }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('tickets.show', $ticket) }}" class="btn-invert btn-sm !px-3 !py-1.5 text-xs">
                                            @icon('eye', 'w-4 h-4') Voir
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-white/10 px-5 py-3">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
