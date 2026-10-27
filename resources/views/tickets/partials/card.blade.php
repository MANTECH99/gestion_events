@php
    /** Ticket portrait avec QR code — utilisé à l'écran et à l'impression. */
    $isUsed = $ticket->status === 'used';
    $isInvalid = in_array($ticket->status, ['refunded', 'cancelled'], true);
    $tagline = $ticket->event->tags_list ? implode(' • ', $ticket->event->tags_list) : null;
    $typeLabel = $ticket->event->type_label;
    // En-tête : photo de l'artiste rattaché à l'événement (section Artistes),
    // à défaut la couverture de l'événement.
    $artistPhoto = $ticket->event->artist?->image_url ?: $ticket->event->image_url;
    // Pied : photo de profil de l'organisateur, à défaut le logo.
    $organizerAvatar = $ticket->event->organizer?->avatar_url;
@endphp

<div class="print-color relative mx-auto w-full max-w-[24rem] overflow-hidden rounded-2xl bg-[#0c0c0f] text-white shadow-2xl shadow-black/40 print:shadow-none">

    {{-- Bord orange (premier enfant : les décors et encoches passent au-dessus) --}}
    <div class="pointer-events-none absolute inset-0 rounded-2xl border-[1.5px] border-orange-500" aria-hidden="true"></div>

    {{-- En-tête : logo + statut --}}
    <div class="relative px-5 pb-2.5 pt-3.5">
        <div class="absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute inset-0" style="background: radial-gradient(65% 120% at 100% 0%, rgba(249,115,22,.38) 0%, rgba(249,115,22,.08) 55%, transparent 78%);"></div>
            <span class="absolute -right-10 -top-14 h-36 w-36 rounded-full border-[6px] border-orange-500/25"></span>
        </div>

        <div class="relative flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2">
                @if ($organizerAvatar)
                    <img src="{{ $organizerAvatar }}" alt="" aria-hidden="true" class="h-8 w-8 shrink-0 rounded-xl object-cover">
                @else
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-orange-500 text-[#0c0c0f]">
                        @icon('ticket', 'w-[18px] h-[18px]')
                    </span>
                @endif
                @php
                    $orgName = $ticket->event->organizer_display_name;
                    $orgParts = explode(' ', $orgName, 2);
                @endphp
                <p class="truncate text-[19px] font-extrabold leading-none tracking-tight">{{ $orgParts[0] }}@if (isset($orgParts[1]))<span class="text-orange-500"> {{ $orgParts[1] }}</span>@endif</p>
            </div>

            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-white/10 bg-black/55 px-2.5 py-1 text-[11px] font-bold leading-none {{ $isInvalid ? 'text-rose-400' : ($isUsed ? 'text-amber-400' : 'text-green-400') }}">
                @icon($isInvalid ? 'close' : ($isUsed ? 'clock' : 'check-circle'), 'w-3.5 h-3.5')
                {{ $ticket->status_label }}
            </span>
        </div>
    </div>

    {{-- Photo de l'artiste + signature de marque --}}
    <div class="relative h-[132px]">
        <div class="absolute inset-[1.5px] overflow-hidden" aria-hidden="true">
            <div class="absolute inset-0" style="background: radial-gradient(70% 90% at 85% 0%, rgba(249,115,22,.32) 0%, rgba(249,115,22,.07) 45%, transparent 75%);"></div>
            @if ($artistPhoto)
                <img src="{{ $artistPhoto }}" alt="" aria-hidden="true" class="absolute inset-y-0 left-[calc(50%_+_48px)] h-full w-[76%] -translate-x-1/2 object-cover object-[center_30%]">
            @endif
            {{-- Fondu haut sous la barre logo, fondu bas dans le corps du billet, coin gauche assombri pour le slogan. --}}
            <div class="absolute inset-x-0 top-0 h-16" style="background: linear-gradient(180deg, rgba(12,12,15,.7) 0%, rgba(12,12,15,0) 100%);"></div>
            <div class="absolute inset-x-0 bottom-0 h-14" style="background: linear-gradient(0deg, rgba(12,12,15,.92) 0%, rgba(12,12,15,0) 100%);"></div>
            <div class="absolute inset-y-0 left-0 w-[72%]" style="background: linear-gradient(90deg, rgba(12,12,15,.78) 0%, rgba(12,12,15,.30) 55%, rgba(12,12,15,0) 100%);"></div>
        </div>

        {{-- Slogan manuscrit + trait orange (centré verticalement dans le cadre) --}}
        <div class="absolute left-4 top-9 select-none">
            <p class="font-handwritten text-[23px] font-semibold leading-[1.06] text-white"
               style="text-shadow: 0 2px 6px rgba(12,12,15,.65);">
                Le Sénégal<br>vit en grand !
            </p>
            <svg viewBox="0 0 132 12" class="-mt-0.5 h-3 w-[132px]" fill="none" aria-hidden="true">
                <path d="M4 8.8C33 3.4 84 2.2 128 5.6" stroke="#fb923c" stroke-width="3.4" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

    {{-- Type + titre + sous-titre --}}
    <div class="relative px-5 pb-4 pt-3.5">
        @if ($typeLabel)
            <span class="inline-flex rounded-full bg-orange-500 px-2.5 py-1 text-[10px] font-extrabold uppercase leading-none tracking-wider text-white">{{ $typeLabel }}</span>
        @endif
        <h1 class="mt-2 text-[24px] font-extrabold leading-[1.08] tracking-tight text-white sm:text-[28px]">{{ $ticket->event->title }}</h1>
        @if ($tagline)
            <p class="mt-1.5 text-[11px] font-semibold uppercase leading-none tracking-[0.18em] text-white/70">{{ $tagline }}</p>
        @endif
    </div>

    {{-- Infos événement (3 colonnes) --}}
    <div class="grid grid-cols-3 divide-x divide-white/10 border-t border-white/10 px-3 py-2.5">
        <div class="flex items-start gap-1.5 px-2 py-0.5">
            <span class="mt-px shrink-0 text-orange-500">@icon('calendar', 'w-[18px] h-[18px]')</span>
            <span class="text-[11px] font-semibold leading-snug text-white">{{ event_date_fr($ticket->event->event_date, 'l j F Y à H:i') }}</span>
        </div>
        <div class="flex items-start gap-1.5 px-2 py-0.5">
            <span class="mt-px shrink-0 text-orange-500">@icon('map-pin', 'w-[18px] h-[18px]')</span>
            <span class="break-words text-[11px] font-semibold leading-snug text-white">{{ $ticket->event->location }}</span>
        </div>
        <div class="flex items-start gap-1.5 px-2 py-0.5">
            <span class="mt-px shrink-0 text-orange-500">@icon('ticket', 'w-[18px] h-[18px]')</span>
            <div class="min-w-0">
                <p class="text-[9px] font-bold uppercase leading-none tracking-wider text-white/45">Catégorie</p>
                <p class="mt-1 text-[11px] font-bold leading-snug text-white">{{ $ticket->ticketType->name }}</p>
            </div>
        </div>
    </div>

    {{-- QR + encoches de perforation --}}
    <div class="relative border-t border-white/10 px-5 pb-3 pt-3">
        <span class="absolute -left-[13px] -top-[13px] h-[26px] w-[26px] rounded-full border-2 border-orange-500 bg-[#12141c] print:bg-white" aria-hidden="true"></span>
        <span class="absolute -right-[13px] -top-[13px] h-[26px] w-[26px] rounded-full border-2 border-orange-500 bg-[#12141c] print:bg-white" aria-hidden="true"></span>

        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute left-3 top-5 h-8 w-8 opacity-50" style="background-image: radial-gradient(#fdba74 1.5px, transparent 1.5px); background-size: 8px 8px;"></div>
            <div class="absolute bottom-8 right-3 h-8 w-8 opacity-50" style="background-image: radial-gradient(#fdba74 1.5px, transparent 1.5px); background-size: 8px 8px;"></div>
            <span class="absolute -bottom-16 -left-20 h-48 w-48 rounded-full border border-orange-500/25"></span>
            <span class="absolute -bottom-20 -right-24 h-56 w-56 rounded-full border border-orange-500/15"></span>
        </div>

        <div class="relative mx-auto w-fit">
            <div class="rounded-xl bg-white p-2 {{ $isUsed || $isInvalid ? 'opacity-40' : '' }}">
                <div class="h-[150px] w-[150px] [&>svg]:h-full [&>svg]:w-full" role="img" aria-label="QR code du billet">
                    {!! $qr !!}
                </div>
            </div>
        </div>

        <div class="relative mx-auto mt-3 flex w-fit items-center justify-center gap-2">
            <span class="text-orange-500">@icon('ticket', 'w-5 h-5')</span>
            <p class="font-mono text-[15px] font-extrabold leading-none tracking-wide text-white">{{ $ticket->code }}</p>
        </div>

        @if ($isUsed)
            <p class="relative mx-auto mt-3 w-fit rounded-xl bg-white/5 px-3 py-2 text-center text-[11px] font-semibold text-white/70">
                Utilisé le {{ $ticket->used_at?->translatedFormat('d/m/Y à H:i') }}
                @if ($ticket->validator) par {{ $ticket->validator->full_name }} @endif
            </p>
        @elseif ($isInvalid)
            <p class="relative mx-auto mt-3 w-fit rounded-xl bg-rose-500/10 px-3 py-2 text-center text-[11px] font-semibold text-rose-300">
                Ce billet n'est plus valide.
            </p>
        @endif
    </div>

    {{-- Titulaire --}}
    <div class="border-t border-white/10 px-5 py-3">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 shrink-0 text-orange-500">@icon('user', 'w-5 h-5')</span>
            <div class="min-w-0">
                <p class="text-[9px] font-bold uppercase leading-none tracking-[0.14em] text-white/45">Titulaire</p>
                <p class="mt-1 truncate text-[15px] font-extrabold leading-tight text-white">{{ $ticket->holder_name }}</p>
                @if ($ticket->holder_email)
                    <p class="mt-1 truncate text-[11px] leading-tight text-white/55">{{ $ticket->holder_email }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Prix + réservation --}}
    <div class="grid grid-cols-2 divide-x divide-white/10 border-t border-white/10">
        <div class="flex items-start gap-2.5 px-5 py-2.5">
            <span class="mt-0.5 shrink-0 text-orange-500">@icon('currency', 'w-5 h-5')</span>
            <div class="min-w-0">
                <p class="text-[9px] font-bold uppercase leading-none tracking-[0.14em] text-white/45">Prix</p>
                <p class="mt-1 truncate text-[15px] font-extrabold leading-tight text-white">{{ money($ticket->price) }}</p>
            </div>
        </div>
        <div class="flex items-start gap-2.5 px-5 py-2.5">
            <span class="mt-0.5 shrink-0 text-orange-500">@icon('ticket', 'w-5 h-5')</span>
            <div class="min-w-0">
                <p class="text-[9px] font-bold uppercase leading-none tracking-[0.14em] text-white/45">Réservation</p>
                <p class="mt-1 break-all font-mono text-[13px] font-extrabold leading-tight text-white">{{ $ticket->reservation->reference }}</p>
            </div>
        </div>
    </div>

    {{-- Pied : organisateur + année --}}
    <div class="relative border-t border-white/10 px-5 py-3.5">
        <div class="absolute inset-[1.5px] overflow-hidden" aria-hidden="true" style="background: radial-gradient(55% 130% at 50% 155%, rgba(249,115,22,.30) 0%, rgba(249,115,22,.10) 45%, transparent 75%);"></div>
        <div class="relative flex items-center justify-between gap-3">
            <span class="flex min-w-0 items-center gap-2">
                <span class="grid h-4 w-4 shrink-0 place-items-center rounded bg-orange-500 text-[#0c0c0f]">
                    @icon('ticket', 'w-2.5 h-2.5')
                </span>
                <span class="truncate text-[11px] font-semibold text-white/75">Billeterie<span class="text-orange-500"> Express</span></span>
            </span>
            <span class="shrink-0 text-[11px] font-semibold text-white/40">{{ $ticket->event->event_date?->year ?? now()->year }}</span>
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
