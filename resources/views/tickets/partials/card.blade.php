@php
    /** Ticket portrait avec QR code — utilisé à l'écran et à l'impression. */
    $isUsed = $ticket->status === 'used';
    $isInvalid = in_array($ticket->status, ['refunded', 'cancelled'], true);
@endphp

<div class="print-color relative mx-auto w-full max-w-[24rem] overflow-hidden rounded-3xl bg-white shadow-2xl shadow-ink-950/20 ring-1 ring-ink-200 print:shadow-none print:ring-0">

    {{-- En-tête --}}
    <div class="gradient-brand px-6 py-6 text-white">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/70">Billeterie Express</p>
                <p class="mt-1 text-xs text-white/80">Billet officiel</p>
            </div>
            <span class="badge {{ $isUsed ? 'bg-ink-900/80 text-white' : ($isInvalid ? 'bg-rose-600 text-white' : 'bg-white/95 text-emerald-700') }}">
                {{ $ticket->status_label }}
            </span>
        </div>

        <h1 class="mt-4 text-xl font-extrabold leading-tight">{{ $ticket->event->title }}</h1>
    </div>

    {{-- Bandeau event --}}
    <div class="space-y-2.5 border-b border-dashed border-ink-200 bg-ink-50/70 px-6 py-4 text-sm">
        <div class="flex items-start gap-2.5">
            <span class="mt-0.5 shrink-0 text-ink-400">@icon('calendar', 'w-4 h-4')</span>
            <span class="font-medium text-ink-900">{{ event_date_fr($ticket->event->event_date, 'l j F Y à H:i') }}</span>
        </div>
        <div class="flex items-start gap-2.5">
            <span class="mt-0.5 shrink-0 text-ink-400">@icon('map-pin', 'w-4 h-4')</span>
            <span class="text-ink-800">{{ $ticket->event->location }}</span>
        </div>
        <div class="flex items-start gap-2.5">
            <span class="mt-0.5 shrink-0 text-ink-400">@icon('ticket', 'w-4 h-4')</span>
            <span class="text-ink-800">{{ $ticket->ticketType->name }}</span>
        </div>
    </div>

    {{-- QR --}}
    <div class="px-6 py-6">
        <div class="mx-auto w-fit rounded-2xl border-2 {{ $isUsed || $isInvalid ? 'border-ink-200 opacity-40' : 'border-ink-900' }} bg-white p-3">
            <div class="h-56 w-56 [&>svg]:h-full [&>svg]:w-full" role="img" aria-label="QR code du billet">
                {!! $qr !!}
            </div>
        </div>

        <p class="mt-3 text-center font-mono text-sm font-bold tracking-wider text-ink-900">{{ $ticket->code }}</p>

        @if ($isUsed)
            <p class="mt-3 rounded-xl bg-ink-100 px-3 py-2 text-center text-xs font-semibold text-ink-600">
                Utilisé le {{ $ticket->used_at?->translatedFormat('d/m/Y à H:i') }}
                @if ($ticket->validator) par {{ $ticket->validator->full_name }} @endif
            </p>
        @elseif ($isInvalid)
            <p class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-center text-xs font-semibold text-rose-700">
                Ce billet n'est plus valide.
            </p>
        @endif
    </div>

    {{-- Titulaire --}}
    <div class="space-y-3 border-t border-dashed border-ink-200 px-6 py-5">
<div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-ink-500">Titulaire</p>
            <p class="mt-0.5 text-base font-bold text-ink-950">{{ $ticket->holder_name }}</p>
            @if ($ticket->holder_email)
                <p class="text-xs text-ink-500">{{ $ticket->holder_email }}</p>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-ink-500">Prix</p>
                <p class="mt-0.5 font-semibold text-ink-900">{{ money($ticket->price) }}</p>
            </div>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-ink-500">Réservation</p>
                <p class="mt-0.5 font-mono text-xs font-semibold text-ink-900">{{ $ticket->reservation->reference }}</p>
            </div>
        </div>
    </div>

    {{-- Pied --}}
    <div class="flex items-center justify-between bg-ink-950 px-6 py-3 text-[10px] text-white/60">
        <span>{{ $ticket->event->organizer_display_name }}</span>
        <span class="font-mono">{{ now()->year }}</span>
    </div>

    @if ($isUsed || $isInvalid)
        <div class="absolute inset-0 grid place-items-center">
            <span class="-rotate-12 rounded-2xl border-4 border-rose-600 px-6 py-2 text-2xl font-extrabold uppercase tracking-widest text-rose-600 opacity-80">
                {{ $isUsed ? 'Utilisé' : 'Invalide' }}
            </span>
        </div>
    @endif
</div>