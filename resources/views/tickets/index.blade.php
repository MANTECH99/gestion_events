@extends('layouts.app', ['title' => 'Vos billets'])

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">Vos billets</h1>
                <p class="mt-1 text-sm text-ink-600">
                    Réservation {{ $reservation->reference }} · {{ event_date_fr($reservation->event->event_date, 'd/m/Y à H:i') }}
                </p>
            </div>
        </div>

        @if ($reservation->tickets->isEmpty())
            <div class="card mt-8 p-10 text-center">
                <p class="text-ink-600">Aucun billet n'a encore été généré pour cette réservation.</p>
            </div>
        @else
            <div class="mt-8 flex flex-wrap justify-center gap-8">
                @foreach ($tickets as $ticket)
                    <div class="w-full max-w-[24rem]" x-data="ticketImage">
                        <div x-ref="card">
                            @include('tickets.partials.card', ['ticket' => $ticket, 'qr' => $qr($ticket)])
                        </div>

                        <div class="mt-4 flex gap-2 no-print">
                            <a href="{{ URL::signedRoute('tickets.show', $ticket) }}" class="btn-outline btn-sm flex-1">
                                @icon('eye', 'w-4 h-4') Agrandir
                            </a>
                            <button type="button" @click="capture($refs.card, '{{ $ticket->code }}')" :disabled="busy"
                                    class="btn-primary btn-sm flex-1 disabled:opacity-60">
                                @icon('download', 'w-4 h-4')
                                <span x-show="!busy">Enregistrer l'image</span>
                                <span x-show="busy" x-cloak>Génération…</span>
                            </button>
                        </div>

                        @include('tickets.partials.image-modal')
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection