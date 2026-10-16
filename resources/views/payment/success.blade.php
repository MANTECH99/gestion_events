@extends('layouts.app', ['title' => 'Paiement confirmé'])

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8"
         x-data="paymentWatcher(@js([
                'statusUrl' => route('payment.status', $reservation->reference),
                'ticketsUrl' => URL::signedRoute('tickets.reservation', $reservation->reference),
             ]))">

        @if ($reservation->isPaid())
            <div class="text-center">
                <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-orange-100 text-orange-600">
                    @icon('check-circle', 'w-11 h-11')
                </span>
                <h1 class="mt-6 text-3xl font-extrabold tracking-tight text-ink-950">Paiement confirmé !</h1>
                <p class="mx-auto mt-2 max-w-lg text-ink-600">
                    Merci ! Votre réservation <span class="font-semibold text-ink-900">{{ $reservation->reference }}</span> est payée
                    et vos billets sont prêts.
                </p>
            </div>

            <div class="card mt-8 overflow-hidden">
                <div class="flex flex-col gap-4 border-b border-ink-100 p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-ink-500">Événement</p>
                        <p class="mt-0.5 font-bold text-ink-950">{{ $reservation->event->title }}</p>
                        <p class="mt-1 text-sm text-ink-600">
                            {{ event_date_fr($reservation->event->event_date, 'l j F Y à H:i') }} · {{ $reservation->event->location }}
                        </p>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-xs font-medium uppercase tracking-wide text-ink-500">Montant payé</p>
                        <p class="text-2xl font-extrabold text-orange-600">{{ money($reservation->total_amount) }}</p>
                    </div>
                </div>

                <div class="p-6">
                    <h2 class="font-bold text-ink-900">Vos billets ({{ $reservation->quantity }})</h2>
                    <p class="mt-1 text-sm text-ink-600">
                        Présentez le QR code de chaque billet à l'entrée. Un billet n'est valable qu'une seule fois.
                    </p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach ($reservation->tickets as $ticket)
                            <a href="{{ URL::signedRoute('tickets.show', $ticket) }}"
                               class="group flex items-center justify-between gap-3 rounded-xl border border-ink-200 p-4 transition hover:border-brand-400 hover:bg-brand-50/40">
                                <div class="min-w-0">
                                    <p class="font-mono text-sm font-bold text-ink-900">{{ $ticket->code }}</p>
                                    <p class="mt-0.5 truncate text-xs text-ink-600">{{ $ticket->ticketType->name }}</p>
                                </div>
                                <span class="shrink-0 text-ink-400 transition group-hover:text-brand-600">
                                    @icon('eye', 'w-5 h-5')
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ URL::signedRoute('tickets.reservation', $reservation->reference) }}" class="btn-primary">
                    @icon('ticket', 'w-5 h-5') Voir tous mes billets
                </a>
                <a href="{{ route('events.show', $reservation->event) }}" class="btn-outline">
                    Découvrir l'événement
                </a>
            </div>

            @php
                $otherRefs = collect(session('payment_refs', []))
                    ->reject(fn ($ref) => $ref === $reservation->reference)
                    ->values();
                $otherPending = $otherRefs->isEmpty()
                    ? collect()
                    : \App\Models\Reservation::whereIn('reference', $otherRefs)->get()
                        ->filter(fn ($r) => $r->payment_status === \App\Models\Reservation::STATUS_PENDING)
                        ->values();
            @endphp
            @if ($otherPending->isNotEmpty())
                <div class="card mt-6 border-amber-200 bg-amber-50/70 p-5">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 text-amber-600">@icon('alert', 'w-5 h-5')</span>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-bold text-amber-900">
                                {{ $otherPending->count() > 1 ? 'Autres réservations de votre commande en attente de paiement' : 'Une autre réservation de votre commande en attente de paiement' }}
                            </h3>
                            <p class="mt-1 text-sm text-amber-800">
                                @if ($otherPending->count() > 1)
                                    Vous pouvez régler chacune d'elles via le lien ci-dessous — chaque réservation est indépendante.
                                @else
                                    Vous pouvez la régler via le lien ci-dessous.
                                @endif
                            </p>
                            <ul class="mt-3 space-y-2">
                                @foreach ($otherPending as $pending)
                                    <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-white/70 px-3 py-2 text-sm">
                                        <span class="min-w-0">
                                            <span class="font-mono font-semibold text-ink-900">{{ $pending->reference }}</span>
                                            <span class="text-ink-600"> · {{ $pending->event->title }} · {{ money($pending->total_amount) }}</span>
                                        </span>
                                        <a href="{{ route('payment.show', $pending->reference) }}" class="btn-primary btn-sm">
                                            Payer
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            @guest
                <div class="card mt-8 flex items-start gap-3 border-brand-200 bg-brand-50/60 p-5">
                    <span class="mt-0.5 text-brand-600">@icon('info', 'w-5 h-5')</span>
                    <p class="text-sm text-brand-900">
                        <strong>Astuce :</strong> créez un compte pour retrouver cette réservation à tout moment
                        depuis <strong>Mes réservations</strong>.
                    </p>
                </div>
            @endguest
        @else
            {{-- Webhook pas encore reçu : on interroge le statut en direct --}}
            <div class="text-center">
                <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-amber-100 text-amber-600">
                    <span class="h-9 w-9 animate-spin rounded-full border-[3px] border-amber-200 border-t-amber-600"></span>
                </span>
                <h1 class="mt-6 text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">
                    Vérification de votre paiement…
                </h1>
                <p class="mx-auto mt-2 max-w-lg text-ink-600">
                    Nous confirmons la transaction auprès de DexPay. Cette page se rafraîchira automatiquement
                    dès que le paiement sera validé.
                </p>
                <p class="mt-4 text-sm text-ink-500">Ne fermez pas cette page.</p>
            </div>

            <div class="card mx-auto mt-8 max-w-lg p-6 text-center">
                <p class="text-xs uppercase tracking-wide text-ink-500">Réservation</p>
                <p class="mt-1 font-bold text-ink-900">{{ $reservation->reference }}</p>
                <p class="mt-3 text-3xl font-extrabold text-ink-950">{{ money($reservation->total_amount) }}</p>
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('payment.show', $reservation->reference) }}" class="btn-outline">Retour à la page de paiement</a>
            </div>
        @endif
    </div>
@endsection