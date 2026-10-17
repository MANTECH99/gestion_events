@php
    /** Ticket portrait avec QR code — utilisé à l'écran et à l'impression. */
    $isUsed = $ticket->status === 'used';
    $isInvalid = in_array($ticket->status, ['refunded', 'cancelled'], true);
    $tagline = $ticket->event->tags_list
        ? implode(' • ', $ticket->event->tags_list)
        : $ticket->event->type_label;
@endphp

<div class="print-color relative mx-auto w-full max-w-[24rem] overflow-hidden rounded-3xl bg-white shadow-2xl shadow-ink-950/20 ring-1 ring-ink-200 print:shadow-none print:ring-0">

    {{-- En-tête orange --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 via-orange-600 to-orange-700 px-6 pb-5 pt-4 text-white">
        @if ($ticket->event->image_url)
            <img src="{{ $ticket->event->image_url }}" alt="" aria-hidden="true"
                 class="absolute inset-y-0 right-0 h-full w-1/2 object-cover opacity-40"
                 style="-webkit-mask-image: linear-gradient(to left, #000 10%, transparent 90%); mask-image: linear-gradient(to left, #000 10%, transparent 90%);">
        @endif
        <div class="absolute inset-0" aria-hidden="true" style="background: linear-gradient(135deg, rgba(249,115,22,.25) 0%, rgba(234,88,12,.55) 100%);"></div>

        <div class="relative">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-white text-orange-600">
                        @icon('zap', 'w-5 h-5')
                    </span>
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] leading-none">Billeterie Express</p>
                        <p class="mt-1 text-xs text-white/85 leading-none">Billet officiel</p>
                    </div>
                </div>

                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/70 bg-white/95 px-3 py-1.5 text-xs font-bold {{ $isUsed ? 'text-ink-700' : ($isInvalid ? 'text-rose-600' : 'text-orange-600') }}">
                    @icon($isInvalid ? 'close' : 'check-circle', 'w-4 h-4')
                    {{ $ticket->status_label }}
                </span>
            </div>

            <h1 class="mt-3 text-2xl font-extrabold leading-[1.1] drop-shadow-sm">{{ $ticket->event->title }}</h1>
            <p class="mt-2 text-sm font-medium text-white/90">{{ $tagline }}</p>
        </div>
    </div>

    {{-- Infos événement --}}
    <div class="space-y-2 px-6 py-4">
        <div class="flex items-center gap-3">
            <span class="grid h-6 w-6 shrink-0 place-items-center text-orange-500">@icon('calendar', 'w-5 h-5')</span>
            <span class="text-sm font-semibold text-ink-800">{{ event_date_fr($ticket->event->event_date, 'l j F Y à H:i') }}</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="grid h-6 w-6 shrink-0 place-items-center text-orange-500">@icon('map-pin', 'w-5 h-5')</span>
            <span class="text-sm font-semibold text-ink-800">{{ $ticket->event->location }}</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="grid h-6 w-6 shrink-0 place-items-center text-orange-500">@icon('ticket', 'w-5 h-5')</span>
            <span class="text-sm font-semibold text-ink-800">{{ $ticket->ticketType->name }}</span>
        </div>

        <div class="border-t border-ink-100"></div>
    </div>

    {{-- QR --}}
    <div class="relative px-6 pb-4">
        <div class="pointer-events-none absolute left-4 top-4 h-10 w-10 opacity-60" aria-hidden="true"
             style="background-image: radial-gradient(#fdba74 1.5px, transparent 1.5px); background-size: 8px 8px;"></div>
        <div class="pointer-events-none absolute bottom-6 right-6 h-10 w-10 opacity-60" aria-hidden="true"
             style="background-image: radial-gradient(#fdba74 1.5px, transparent 1.5px); background-size: 8px 8px;"></div>

        <div class="relative mx-auto w-fit">
            <div class="rounded-2xl border-2 {{ $isUsed || $isInvalid ? 'border-ink-200 opacity-40' : 'border-orange-400' }} bg-white p-3">
                <div class="relative">
                    <div class="h-52 w-52 [&>svg]:h-full [&>svg]:w-full" role="img" aria-label="QR code du billet">
                        {!! $qr !!}
                    </div>
                    {{-- Coins --}}
                    <span class="absolute -left-2 -top-2 h-8 w-8 rounded-tl-xl border-l-[5px] border-t-[5px] border-orange-500" aria-hidden="true"></span>
                    <span class="absolute -right-2 -top-2 h-8 w-8 rounded-tr-xl border-r-[5px] border-t-[5px] border-orange-500" aria-hidden="true"></span>
                    <span class="absolute -bottom-2 -left-2 h-8 w-8 rounded-bl-xl border-b-[5px] border-l-[5px] border-orange-500" aria-hidden="true"></span>
                    <span class="absolute -bottom-2 -right-2 h-8 w-8 rounded-br-xl border-b-[5px] border-r-[5px] border-orange-500" aria-hidden="true"></span>
                </div>
            </div>
        </div>

        <p class="relative mx-auto mt-3 w-fit rounded-full bg-orange-100 px-6 py-1.5 text-center font-mono text-sm font-extrabold tracking-wider text-orange-600">
            {{ $ticket->code }}
        </p>

        @if ($isUsed)
            <p class="relative mt-3 rounded-xl bg-ink-100 px-3 py-2 text-center text-xs font-semibold text-ink-600">
                Utilisé le {{ $ticket->used_at?->translatedFormat('d/m/Y à H:i') }}
                @if ($ticket->validator) par {{ $ticket->validator->full_name }} @endif
            </p>
        @elseif ($isInvalid)
            <p class="relative mt-3 rounded-xl bg-rose-50 px-3 py-2 text-center text-xs font-semibold text-rose-700">
                Ce billet n'est plus valide.
            </p>
        @endif
    </div>

    {{-- Titulaire + montants --}}
    <div class="border-t border-ink-100 px-6 py-4">
        <div class="flex items-start gap-3">
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-orange-500 text-white">@icon('user', 'w-4 h-4')</span>
            <div class="min-w-0">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-orange-500">Titulaire</p>
                <p class="mt-0.5 truncate text-base font-extrabold text-ink-950">{{ $ticket->holder_name }}</p>
                @if ($ticket->holder_email)
                    <p class="mt-1 flex items-center gap-1.5 text-xs text-ink-500">
                        <span class="grid h-4 w-4 shrink-0 place-items-center rounded-[4px] bg-orange-500 text-white">@icon('mail', 'w-3 h-3')</span>
                        <span class="truncate">{{ $ticket->holder_email }}</span>
                    </p>
                @endif
            </div>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-4 border-t border-ink-100 pt-3">
            <div class="flex items-start gap-2.5">
                <span class="text-orange-500">@icon('tag', 'w-5 h-5')</span>
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-orange-500">Prix</p>
                    <p class="mt-0.5 truncate text-base font-extrabold text-ink-950">{{ money($ticket->price) }}</p>
                </div>
            </div>
            <div class="flex items-start gap-2.5 border-l border-ink-100 pl-4">
                <span class="text-orange-500">@icon('shield', 'w-5 h-5')</span>
                <div class="min-w-0">
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-orange-500">Réservation</p>
                    <p class="mt-0.5 font-mono text-xs font-extrabold text-ink-950 break-all">{{ $ticket->reservation->reference }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Pied orange --}}
    <div class="bg-gradient-to-r from-orange-600 to-orange-500 px-6 py-3">
        <div class="flex items-center justify-between text-xs font-bold text-white">
            <span>{{ $ticket->event->organizer_display_name }}</span>
            <span>{{ $ticket->event->event_date?->year ?? now()->year }}</span>
        </div>
    </div>

    @if ($isUsed || $isInvalid)
        <div class="absolute inset-0 grid place-items-center">
            <span class="-rotate-12 rounded-2xl border-4 border-rose-600 bg-white/70 px-6 py-2 text-2xl font-extrabold uppercase tracking-widest text-rose-600">
                {{ $isUsed ? 'Utilisé' : 'Invalide' }}
            </span>
        </div>
    @endif
</div>
