@extends('layouts.app', ['title' => 'Réserver — '.$event->title])

@section('content')
    @php
        $prices = $types->pluck('price', 'id')->all();
        $limits = $types->mapWithKeys(fn ($t) => [
            $t->id => $t->available_online === null ? 20 : min(20, (int) $t->available_online),
        ])->all();
        $totalTickets = array_sum($qtys);
        $totalAmount = (int) $types->sum(fn ($t) => ($prices[$t->id] ?? 0) * ($qtys[$t->id] ?? 0));
    @endphp

    <div class="bg-ink-950 pb-14"
         x-data="bookingRecap({
             prices: @js($prices),
             limits: @js($limits),
             selected: @js($qtys),
         })">

        {{-- Bandeau événement --}}
        <section class="relative overflow-hidden border-b border-white/10">
            <div class="absolute inset-0" aria-hidden="true">
                @if ($event->image_url)
                    <img src="{{ $event->image_url }}" alt="" class="absolute inset-0 h-full w-full object-cover object-center">
                    <div class="absolute inset-0" style="background: linear-gradient(180deg, rgba(10,14,23,.88) 0%, rgba(10,14,23,.82) 55%, #0a0e17 100%);"></div>
                @else
                    <div class="absolute inset-0" style="background:
                        radial-gradient(48% 46% at 72% 30%, rgba(249,115,22,.28), rgba(249,115,22,0) 70%),
                        radial-gradient(44% 42% at 28% 70%, rgba(56,160,220,.22), rgba(56,160,220,0) 72%),
                        #0a0e17;"></div>
                @endif
            </div>

            @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
            <div class="relative mx-auto max-w-7xl px-4 pb-7 {{ $hasFlash ? 'pt-6 sm:pt-8' : 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' }} sm:px-6 lg:px-8">
                <a href="{{ route('events.show', $event) }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-white/70 transition hover:text-white">
                    <span class="grid h-8 w-8 place-items-center rounded-full border border-white/20 bg-white/10">
                        @icon('arrow-left', 'w-4 h-4')
                    </span>
                    Retour à l'événement
                </a>

                <div class="mt-4 flex items-center gap-4">
                    @if ($event->image_url)
                        <img src="{{ $event->image_url }}" alt="Affiche de {{ $event->title }}"
                             class="h-16 w-16 shrink-0 rounded-xl object-cover ring-1 ring-white/20 sm:h-20 sm:w-20">
                    @else
                        <span class="grid h-16 w-16 shrink-0 place-items-center rounded-xl gradient-brand text-2xl ring-1 ring-white/20 sm:h-20 sm:w-20">
                            {{ ticket_type_icon($event->event_type) }}
                        </span>
                    @endif

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h1 class="min-w-0 truncate text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                                {{ $event->title }}
                            </h1>
                            <span class="chip shrink-0 bg-orange-500/15 text-orange-300 ring-orange-400/30">
                                {{ $event->type_label }}
                            </span>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-white/70">
                            <span class="inline-flex items-center gap-1.5">
                                @icon('calendar', 'w-4 h-4') {{ event_date_fr($event->event_date, 'j F Y') }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                @icon('clock', 'w-4 h-4') {{ $event->event_date->format('H\hi') }}
                            </span>
                            <span class="inline-flex min-w-0 items-center gap-1.5">
                                @icon('map-pin', 'w-4 h-4') <span class="min-w-0 truncate">{{ $event->location }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Étapes de la commande --}}
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <ol class="flex items-center gap-2 py-5 text-xs font-semibold sm:gap-4 sm:text-sm">
                <li class="flex shrink-0 items-center gap-2 text-orange-400">
                    <span class="grid h-6 w-6 place-items-center rounded-full bg-orange-400 text-[11px] font-extrabold text-ink-950 sm:h-7 sm:w-7 sm:text-xs">1</span>
                    <span class="hidden sm:inline">Panier</span>
                </li>
                <li class="h-px min-w-4 flex-1 bg-white/10" aria-hidden="true"></li>
                <li class="flex shrink-0 items-center gap-2 text-white/45">
                    <span class="grid h-6 w-6 place-items-center rounded-full bg-white/10 text-[11px] font-extrabold text-white/55 sm:h-7 sm:w-7 sm:text-xs">2</span>
                    <span class="hidden sm:inline">Paiement</span>
                </li>
                <li class="h-px min-w-4 flex-1 bg-white/10" aria-hidden="true"></li>
                <li class="flex shrink-0 items-center gap-2 text-white/45">
                    <span class="grid h-6 w-6 place-items-center rounded-full bg-white/10 text-[11px] font-extrabold text-white/55 sm:h-7 sm:w-7 sm:text-xs">3</span>
                    <span class="hidden sm:inline">Confirmation</span>
                </li>
            </ol>
        </div>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-5 lg:px-8">
            {{-- Colonne principale --}}
            <div class="min-w-0 space-y-6 lg:col-span-3">
                <form method="POST" action="{{ route('booking.store', $event) }}">
                    @csrf

                    {{-- Récapitulatif des billets --}}
                    <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                        <h2 class="flex items-center gap-2.5 text-lg font-bold text-white">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                                @icon('ticket', 'w-5 h-5')
                            </span>
                            Récapitulatif des billets
                        </h2>

                        @if ($types->isNotEmpty())
                            <div class="mt-5 hidden grid-cols-[minmax(0,1fr)_7rem_9rem_7rem_2.25rem] items-center gap-4 pb-3 text-xs font-semibold uppercase tracking-wide text-white/45 sm:grid">
                                <span>Type de billet</span>
                                <span>Prix unitaire</span>
                                <span class="text-center">Quantité</span>
                                <span class="text-right">Sous-total</span>
                                <span class="sr-only"></span>
                            </div>
                        @endif

                        @forelse ($types as $type)
                            @php
                                $available = $type->available_online;
                                $limit = $limits[$type->id];
                                $qty = $qtys[$type->id] ?? null;
                                $isSelected = $qty !== null;
                            @endphp
                            <div x-show="isSel({{ $type->id }})" @unless($isSelected) x-cloak @endunless
                                 class="border-t border-white/10 py-4 sm:grid sm:grid-cols-[minmax(0,1fr)_7rem_9rem_7rem_2.25rem] sm:items-center sm:gap-4">

                                {{-- Type + suppression (mobile) --}}
                                <div class="flex items-start justify-between gap-3 sm:contents">
                                    <div class="flex min-w-0 items-start gap-2.5">
                                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-orange-400 ring-2 ring-orange-400/30"></span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-white">{{ $type->name }}</p>
                                            @if ($type->description)
                                                <p class="mt-0.5 text-xs leading-relaxed text-white/55">{{ $type->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button" @click="remove({{ $type->id }})"
                                            aria-label="Supprimer {{ $type->name }}"
                                            class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white/40 transition hover:bg-rose-500/10 hover:text-rose-400 sm:hidden">
                                        @icon('trash', 'w-4 h-4')
                                    </button>
                                </div>

                                {{-- Prix unitaire · quantité · sous-total · suppression (desktop) --}}
                                <div class="mt-3 flex items-center justify-between gap-3 sm:mt-0 sm:contents">
                                    <span class="flex flex-col text-sm text-white/70 sm:block">
                                        <span class="text-xs text-white/40 sm:hidden">Prix unitaire</span>
                                        <span>{{ money($type->price) }}</span>
                                    </span>

                                    <div class="flex items-center gap-1.5 sm:justify-self-center">
                                        <button type="button" aria-label="Diminuer la quantité pour {{ $type->name }}"
                                                @click="dec({{ $type->id }})"
                                                :disabled="!isSel({{ $type->id }}) || qtyOf({{ $type->id }}) <= 1"
                                                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-white/15 bg-white/5 text-white/80 transition enabled:hover:border-orange-400/60 enabled:hover:text-orange-300 disabled:cursor-not-allowed disabled:opacity-40">
                                            @icon('minus', 'w-3.5 h-3.5')
                                        </button>

                                        <input id="qty-{{ $type->id }}" type="number" name="qty[{{ $type->id }}]"
                                               x-model.number="sel[{{ $type->id }}]"
                                               :disabled="!isSel({{ $type->id }})"
                                               @disabled(! $isSelected)
                                               min="1" max="{{ $limit ?: 20 }}"
                                               value="{{ old('qty.'.$type->id, $qty ?? 1) }}"
                                               aria-label="Quantité — {{ $type->name }}"
                                               class="h-8 w-12 rounded-lg border border-white/15 bg-white/5 text-center text-sm font-semibold text-white focus:border-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400/25">

                                        <button type="button" aria-label="Augmenter la quantité pour {{ $type->name }}"
                                                @click="inc({{ $type->id }})"
                                                :disabled="!isSel({{ $type->id }}) || qtyOf({{ $type->id }}) >= {{ $limit }}"
                                                class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border border-white/15 bg-white/5 text-white/80 transition enabled:hover:border-orange-400/60 enabled:hover:text-orange-300 disabled:cursor-not-allowed disabled:opacity-40">
                                            @icon('plus', 'w-3.5 h-3.5')
                                        </button>
                                    </div>

                                    <span class="flex flex-col items-end text-right sm:block sm:justify-self-end">
                                        <span class="text-xs text-white/40 sm:hidden">Sous-total</span>
                                        <span class="text-sm font-semibold text-white"
                                              x-text="fmt(lineTotal({{ $type->id }}))">{{ money($type->price * ($qty ?? 0)) }}</span>
                                    </span>

                                    <button type="button" @click="remove({{ $type->id }})"
                                            aria-label="Supprimer {{ $type->name }}"
                                            class="hidden h-8 w-8 place-items-center rounded-lg text-white/40 transition hover:bg-rose-500/10 hover:text-rose-400 sm:grid">
                                        @icon('trash', 'w-4 h-4')
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="mt-5 rounded-xl border border-dashed border-white/15 px-4 py-8 text-center text-sm text-white/60">
                                Aucun type de ticket disponible pour le moment.
                            </p>
                        @endforelse

                        <div class="mt-4 flex items-center gap-2" x-show="Object.keys(sel).length < {{ $types->count() }}">
                            <select x-model="toAdd" class="input-dark min-w-0 flex-1" aria-label="Type de billet à ajouter">
                                <option value="">Choisir un type de billet…</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}"
                                            @disabled(($type->available_online !== null && $type->available_online < 1) || isset($qtys[$type->id]))>
                                        {{ $type->name }} — {{ money($type->price) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button"
                                    class="shrink-0 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white transition enabled:hover:border-orange-400/60 enabled:hover:text-orange-300 disabled:opacity-40"
                                    @click="add(Number(toAdd))" :disabled="!toAdd">
                                @icon('plus', 'w-4 h-4') Ajouter
                            </button>
                        </div>

                        <p x-show="total() === 0" x-cloak
                           class="mt-3 rounded-xl border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-300">
                            Aucun billet sélectionné — choisissez un type ci-dessus.
                        </p>

                        <div class="mt-4 flex items-end justify-between gap-4 border-t border-white/10 pt-4">
                            <div>
                                <p class="text-sm font-semibold text-white/70">Total</p>
                                <p class="mt-0.5 text-xs text-white/40">
                                    <span x-text="total()">{{ $totalTickets }}</span> billet(s)
                                </p>
                            </div>
                            <p class="text-2xl font-extrabold text-orange-400"
                               x-text="fmt(grandTotal())">{{ money($totalAmount) }}</p>
                        </div>
                    </section>

                    {{-- Informations de l'acheteur --}}
                    <section class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                        <h2 class="flex items-center gap-2.5 text-lg font-bold text-white">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                                @icon('user', 'w-5 h-5')
                            </span>
                            Informations de l'acheteur
                        </h2>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="customer_name" class="mb-1.5 block text-sm font-medium text-white/70">
                                    Nom complet <span class="text-rose-400">*</span>
                                </label>
                                <input id="customer_name" name="customer_name" type="text" required
                                       value="{{ old('customer_name', auth()->user()?->full_name) }}"
                                       class="input-dark" placeholder="Ex : Awa Diop">
                            </div>

                            <div>
                                <label for="customer_email" class="mb-1.5 block text-sm font-medium text-white/70">
                                    Email <span class="text-rose-400">*</span>
                                </label>
                                <input id="customer_email" name="customer_email" type="email" required
                                       value="{{ old('customer_email', auth()->user()?->email) }}"
                                       class="input-dark" placeholder="awa@example.com">
                            </div>

                            <div>
                                <label for="customer_phone" class="mb-1.5 block text-sm font-medium text-white/70">
                                    Téléphone <span class="text-rose-400">*</span>
                                </label>
                                <input id="customer_phone" name="customer_phone" type="tel" required
                                       value="{{ old('customer_phone', auth()->user()?->phone) }}"
                                       class="input-dark" placeholder="+221 77 123 45 67">
                            </div>

                            <div>
                                <label for="customer_city" class="mb-1.5 block text-sm font-medium text-white/70">
                                    Ville <span class="text-rose-400">*</span>
                                </label>
                                <input id="customer_city" name="customer_city" type="text" required
                                       value="{{ old('customer_city') }}"
                                       class="input-dark" placeholder="Dakar">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="customer_address" class="mb-1.5 block text-sm font-medium text-white/70">
                                    Adresse <span class="text-white/40">(optionnel)</span>
                                </label>
                                <input id="customer_address" name="customer_address" type="text"
                                       value="{{ old('customer_address') }}"
                                       class="input-dark" placeholder="Ex : Sacré-Cœur, Dakar">
                            </div>
                        </div>
                    </section>

                    {{-- Actions --}}
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ route('events.show', $event) }}" class="btn-outline justify-center">Annuler</a>
                        <button type="submit" class="btn-primary"
                                :disabled="total() === 0"
                                :class="total() === 0 ? 'cursor-not-allowed opacity-50' : ''">
                            Réserver @icon('arrow-right', 'w-4 h-4')
                        </button>
                    </div>
                </form>
            </div>

            {{-- Colonne droite --}}
            <aside class="min-w-0 space-y-6 lg:col-span-2">
                {{-- Votre commande --}}
                <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2.5 text-base font-bold text-white">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                                @icon('receipt', 'w-5 h-5')
                            </span>
                            Votre commande
                        </h2>
                        <a href="{{ route('events.show', $event) }}#billets"
                           class="inline-flex items-center gap-1.5 text-sm font-semibold text-orange-400 transition hover:text-orange-300">
                            Modifier @icon('pencil', 'w-3.5 h-3.5')
                        </a>
                    </div>

                    <div class="mt-4 flex items-center gap-3 rounded-xl border border-white/10 bg-white/[.04] p-3">
                        @if ($event->image_url)
                            <img src="{{ $event->image_url }}" alt=""
                                 class="h-14 w-14 shrink-0 rounded-lg object-cover ring-1 ring-white/15">
                        @else
                            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-lg gradient-brand text-xl">
                                {{ ticket_type_icon($event->event_type) }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <p class="flex min-w-0 items-center gap-2">
                                <span class="min-w-0 truncate text-sm font-bold text-white">{{ $event->title }}</span>
                                <span class="chip shrink-0 bg-orange-500/15 text-orange-300 ring-orange-400/30">{{ $event->type_label }}</span>
                            </p>
                            <p class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-white/60">
                                <span class="inline-flex items-center gap-1">
                                    @icon('calendar', 'w-3.5 h-3.5') {{ event_date_fr($event->event_date, 'j F Y') }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    @icon('clock', 'w-3.5 h-3.5') {{ $event->event_date->format('H\hi') }}
                                </span>
                            </p>
                            <p class="mt-0.5 truncate text-xs text-white/60">
                                @icon('map-pin', 'w-3.5 h-3.5') {{ $event->location }}
                            </p>
                        </div>
                    </div>

                    <ul class="mt-4 divide-y divide-white/10">
                        @foreach ($qtys as $typeId => $qty)
                            @php $lineType = $types->firstWhere('id', $typeId); @endphp
                            @if ($lineType)
                                <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                                    <span class="min-w-0 text-white/80">
                                        {{ $lineType->name }}
                                        <span class="text-white/45" x-text="'(x' + qtyOf({{ $typeId }}) + ')'">(x{{ $qty }})</span>
                                    </span>
                                    <span class="shrink-0 font-semibold text-white"
                                          x-text="fmt(lineTotal({{ $typeId }}))">{{ money($lineType->price * $qty) }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>

                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-white/10 pt-4">
                        <span class="text-sm font-semibold text-white/70">Total</span>
                        <span class="text-xl font-extrabold text-orange-400"
                              x-text="fmt(grandTotal())">{{ money($totalAmount) }}</span>
                    </div>
                </section>

                {{-- Billets par email --}}
                <section class="rounded-2xl border border-orange-500/25 bg-orange-500/10 p-5">
                    <div class="flex items-start gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500 text-ink-950">
                            @icon('mail', 'w-5 h-5')
                        </span>
                        <p class="text-sm font-bold leading-snug text-white">
                            Vos billets seront envoyés par email juste après le paiement.
                        </p>
                    </div>
                    <ul class="mt-4 space-y-2.5 text-sm text-white/70">
                        <li class="flex items-center gap-2.5">
                            <span class="text-orange-400">@icon('qr-code', 'w-4 h-4')</span> E-billet avec QR code
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="text-orange-400">@icon('smartphone', 'w-4 h-4')</span> Accessible sur mobile
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="text-orange-400">@icon('check-circle', 'w-4 h-4')</span> Pas de frais cachés
                        </li>
                    </ul>
                </section>

                {{-- Paiement sécurisé --}}
                <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                    <h2 class="flex items-center gap-2.5 text-base font-bold text-white">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                            @icon('shield', 'w-5 h-5')
                        </span>
                        Paiement sécurisé
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/60">
                        Vos informations sont protégées par notre système de cryptage SSL.
                    </p>
                    <div class="mt-4">
                        @include('partials.payment-methods')
                    </div>
                </section>

                {{-- Aide --}}
                <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                    <h2 class="flex items-center gap-2.5 text-base font-bold text-white">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                            @icon('phone', 'w-5 h-5')
                        </span>
                        Besoin d'aide ?
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/60">
                        Notre service client est disponible 24h/24 et 7j/7.
                    </p>
                </section>
            </aside>
        </div>
    </div>
@endsection
