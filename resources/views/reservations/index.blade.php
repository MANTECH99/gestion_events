@extends('layouts.app', ['title' => 'Mes réservations'])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
    <div class="mx-auto max-w-5xl px-4 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' }} sm:px-6 lg:px-8 pb-28 lg:pb-8">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Mes réservations</h1>
            <p class="mt-1 text-sm text-white/60">Retrouvez vos billets et leurs QR codes.</p>
        </div>

        @if ($reservations->isEmpty())
            @include('partials.empty', [
                'icon' => 'ticket',
                'title' => 'Aucune réservation',
                'message' => 'Vous n\'avez pas encore réservé de billets. Parcourez les événements disponibles.',
                'action' => route('home'),
                'actionLabel' => 'Voir les événements',
                'dark' => true,
            ])
        @else
            <div class="space-y-4">
                @foreach ($reservations as $reservation)
                    <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                        {{-- Image événement (paysage) --}}
                        <div class="relative h-36 w-full overflow-hidden bg-gradient-to-br from-orange-500 to-orange-700 sm:h-44">
                            @if ($reservation->event->image_url)
                                <img src="{{ $reservation->event->image_url }}" alt="{{ $reservation->event->title }}" class="h-full w-full object-cover">
                            @else
                                <span class="grid h-full w-full place-items-center text-4xl">{{ ticket_type_icon($reservation->event->event_type) }}</span>
                            @endif
                        </div>

                        <div class="px-5 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                @php
                                    $statusColor = match ($reservation->status_color) {
                                        'emerald' => 'bg-orange-400/25 text-orange-300',
                                        'amber' => 'bg-amber-400/25 text-amber-300',
                                        default => 'bg-rose-500/25 text-rose-300',
                                    };
                                @endphp
                                <span class="badge {{ $statusColor }}">{{ $reservation->status_label }}</span>
                                <span class="font-mono text-xs text-white/50">{{ $reservation->reference }}</span>
                            </div>

                            <div class="mt-1.5 flex flex-wrap items-start justify-between gap-2">
                                <h2 class="min-w-0 text-lg font-bold text-white">{{ $reservation->event->title }}</h2>
                                <p class="shrink-0 text-xl font-extrabold text-orange-400">{{ money($reservation->total_amount) }}</p>
                            </div>

                            <dl class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-xs text-white/60">
                                <div class="inline-flex items-center gap-1.5">
                                    @icon('calendar', 'w-3.5 h-3.5') {{ event_date_fr($reservation->event->event_date) }}
                                </div>
                                <div class="inline-flex items-center gap-1.5">
                                    @icon('map-pin', 'w-3.5 h-3.5') {{ $reservation->event->location }}
                                </div>
                                <div class="inline-flex items-center gap-1.5">
                                    @icon('ticket', 'w-3.5 h-3.5') {{ $reservation->quantity }} × {{ $reservation->ticketType?->name }}
                                </div>
                            </dl>
                        </div>

                        <div class="flex justify-center border-t border-white/10 bg-white/5 px-5 py-3">
                            @if ($reservation->isPaid())
                                <a href="{{ route('reservations.show', $reservation->reference) }}" class="btn-hero-primary btn-sm">
                                    @icon('qr-code', 'w-4 h-4') Voir mes billets
                                </a>
                            @elseif ($reservation->isPending())
                                <a href="{{ route('payment.show', $reservation->reference) }}" class="btn-hero-primary btn-sm">
                                    @icon('credit-card', 'w-4 h-4') Finaliser le paiement
                                </a>
                            @else
                                <span class="text-sm text-white/50">Cette réservation n'est plus valide.</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">{{ $reservations->links() }}</div>
        @endif
    </div>
@endsection
