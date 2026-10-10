@extends('layouts.app', ['title' => 'Paiement échoué'])

@section('content')
    <div class="mx-auto max-w-2xl px-4 py-16 sm:px-6 lg:px-8 text-center">
        <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-rose-100 text-rose-600">
            @icon('x-circle', 'w-11 h-11')
        </span>

        <h1 class="mt-6 text-3xl font-extrabold tracking-tight text-ink-950">Paiement non abouti</h1>
        <p class="mx-auto mt-2 max-w-lg text-ink-600">
            Votre paiement n'a pas été validé par DexPay. Aucun montant n'a été débité et
            <strong>aucun billet n'a été généré</strong>.
        </p>

        <div class="card mx-auto mt-8 max-w-md p-6 text-left">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-600">Réservation</dt>
                    <dd class="font-mono font-semibold text-ink-900">{{ $reservation->reference }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-600">Montant</dt>
                    <dd class="font-semibold text-ink-900">{{ money($reservation->total_amount) }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-ink-600">Statut</dt>
                    <dd>
                        <span class="badge bg-{{ $reservation->status_color }}-100 text-{{ $reservation->status_color }}-700">
                            {{ $reservation->status_label }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
            @if ($reservation->isPending())
                <a href="{{ route('payment.show', $reservation->reference) }}" class="btn-primary">
                    @icon('refresh', 'w-5 h-5') Réessayer le paiement
                </a>
            @endif
            <a href="{{ route('booking.create', $reservation->event) }}" class="btn-outline">
                Modifier ma réservation
            </a>
            <a href="{{ route('home') }}" class="btn-ghost">Retour à l'accueil</a>
        </div>

        <p class="mt-8 text-xs text-ink-500">
            Besoin d'aide ? Contactez l'organisateur de l'événement.
        </p>
    </div>
@endsection