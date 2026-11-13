@extends('layouts.app', ['title' => 'Réserver — '.$event->title])

@section('content')
    @php
        $prices = $types->pluck('price', 'id')->all();

        // Disponibilité effective : stock public, plafonné par le solde
        // assigné au point de vente artiste le cas échéant.
        $artistPos = $artistPos ?? null;
        $avail = $types->mapWithKeys(function ($t) use ($artistPos) {
            $a = $t->available_online;
            $posRemaining = $artistPos?->remainingFor($t->id);

            if ($posRemaining !== null) {
                $a = $a === null ? $posRemaining : min($a, $posRemaining);
            }

            return [$t->id => $a];
        })->all();

        $limits = $types->mapWithKeys(fn ($t) => [
            $t->id => $avail[$t->id] === null ? 20 : min(20, (int) $avail[$t->id]),
        ])->all();
        $totalTickets = array_sum($qtys);
        $totalAmount = (int) $types->sum(fn ($t) => ($prices[$t->id] ?? 0) * ($qtys[$t->id] ?? 0));
    @endphp

    <div class="bg-ink-950 pb-14"
         x-data="bookingRecap({
             prices: @js($prices),
             limits: @js($limits),
             selected: @js($qtys),
             operators: @js(collect($operators)->map(fn ($op) => [
                 'value' => $op['code'],
                 'name' => $op['name'],
                 'fee' => $op['fee'] ?? null,
                 'fee_type' => $op['fee_type'] ?? 'percentage',
             ])->values()->all()),
             operator: @js($operators[0]['code'] ?? ''),
             phone: @js(old('customer_phone', '')),
             dexpayFeeEnabled: @js((bool) config('dexpay.client_support_fee')),
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
            <div class="relative mx-auto max-w-7xl px-4 pb-7 {{ $hasFlash ? 'pt-6 sm:pt-8' : 'pt-24 sm:pt-28' }} sm:px-6 lg:px-8">
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
                <form method="POST" action="{{ route('booking.store', $event) }}"
                      @submit="sending = true"
                      x-data="{
                          sending: false,
                          missing: {},
                          check(e) {
                              if (this.sending) { e.preventDefault(); return; }
                              this.missing = {};
                              const f = this.$root;
                              ['customer_name', 'customer_phone'].forEach((n) => {
                                  const el = f.elements[n];
                                  if (!el || !String(el.value).trim()) this.missing[n] = true;
                              });
                              const first = Object.keys(this.missing)[0];
                              if (first) {
                                  e.preventDefault();
                                  this.$nextTick(() => { const el = f.elements[first]; if (el) el.focus(); });
                              }
                          }
                      }">
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
                                $available = $avail[$type->id];
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
                                            @disabled($avail[$type->id] !== null && $avail[$type->id] < 1)
                                            {{-- Désactivé tant que le type est au panier (et s'il est épuisé) :
                                                 la suppression depuis le panier rend l'option réajoutable sans recharger. --}}
                                            :disabled="{{ $avail[$type->id] !== null && $avail[$type->id] < 1 ? 'true' : 'false' }} || isSel({{ $type->id }})">
                                        {{ $type->name }} — {{ money($type->price) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button"
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm font-bold text-white transition enabled:hover:border-orange-400/60 enabled:hover:text-orange-300 disabled:opacity-40"
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

                        <div class="mt-5">
                            <label for="customer_name" class="mb-1.5 block text-sm font-medium text-white/70">
                                Nom complet <span class="text-rose-400">*</span>
                            </label>
                            <input id="customer_name" name="customer_name" type="text" required
                                   value="{{ old('customer_name', auth()->user()?->full_name) }}"
                                   @input="delete missing.customer_name"
                                   :class="missing.customer_name ? 'border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25' : ''"
                                   class="input-dark" placeholder="Ex : Awa Diop">
                            <p x-show="missing.customer_name" x-cloak class="mt-1 text-sm text-rose-400">Ce champ est obligatoire.</p>
                        </div>
                    </section>

                    {{-- Réglez votre commande (paiement DexPay fusionné) --}}
                    <section class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                        <h2 class="flex items-center gap-2.5 text-lg font-bold text-white">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                                @icon('wallet', 'w-5 h-5')
                            </span>
                            Réglez votre commande
                        </h2>
                        <p class="mt-1.5 text-sm text-white/60">
                            Réservation <span class="font-semibold text-white">{{ $reference }}</span>
                        </p>

                        @if (! $configured)
                            {{-- DexPay non configuré : la réservation est créée, la page de
                                 paiement reste le repli pour finaliser (voir PaymentController). --}}
                            <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-400/30 bg-amber-500/10 p-4">
                                <span class="mt-0.5 shrink-0 text-amber-400">@icon('info', 'w-5 h-5')</span>
                                <div class="text-sm">
                                    <p class="font-semibold text-amber-300">Paiement non configuré</p>
                                    <p class="mt-0.5 text-amber-200/80">
                                        Le paiement en ligne sera finalisé à l'étape suivante, sur la page sécurisée DexPay.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 border-t border-white/10 pt-5">
                                <label for="customer_phone" class="mb-1.5 block text-sm font-medium text-white/70">
                                    Téléphone <span class="text-rose-400">*</span>
                                </label>
                                <input id="customer_phone" name="customer_phone" type="tel" required
                                       value="{{ old('customer_phone') }}"
                                       @input="delete missing.customer_phone"
                                       :class="missing.customer_phone ? 'border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25' : ''"
                                       class="input-dark" placeholder="+221 77 123 45 67"
                                       autocomplete="tel">
                                <p x-show="missing.customer_phone" x-cloak class="mt-1 text-sm text-rose-400">Ce champ est obligatoire.</p>
                            </div>
                        @elseif (filled($operators))
                            @if ($sandbox)
                                <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-400/30 bg-amber-500/10 p-4">
                                    <span class="mt-0.5 text-amber-400">@icon('info', 'w-5 h-5')</span>
                                    <div class="text-sm">
                                        <p class="font-semibold text-amber-300">Mode test (sandbox)</p>
                                        <p class="mt-0.5 text-amber-200/80">
                                            Aucun paiement réel n'est effectué. Vous pouvez finaliser la commande sans débit.
                                        </p>
                                    </div>
                                </div>
                            @endif
                            <p class="mt-5 text-sm font-semibold text-white/80">Choisissez votre moyen de paiement</p>
                            <p class="mt-1 text-sm text-white/60">
                                Vous serez redirigé vers la page sécurisée DexPay pour finaliser la transaction.
                            </p>

                            <fieldset class="mt-4">
                                <legend class="mb-2 block text-sm font-medium text-white/70">Moyen de paiement</legend>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($operators as $operator)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/15 bg-white/[.04] p-4
                                                      transition hover:border-orange-400/50
                                                      has-[:checked]:border-orange-400 has-[:checked]:bg-orange-500/10">
                                            <input type="radio" name="operator" value="{{ $operator['code'] }}"
                                                   x-model="operator" class="radio accent-orange-400">
                                            @if ($operator['logo'])
                                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-white p-1.5 ring-1 ring-white/10">
                                                    <img src="{{ $operator['logo'] }}" alt="{{ $operator['name'] }}"
                                                         class="max-h-full max-w-full object-contain" loading="lazy"
                                                         onerror="this.style.display='none'">
                                                </span>
                                            @else
                                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-orange-500/15 text-orange-400">
                                                    @icon('smartphone', 'w-5 h-5')
                                                </span>
                                            @endif
                                            <span class="min-w-0 flex-1">
                                                <span class="block font-semibold text-white">{{ $operator['name'] }}</span>
                                                <span class="block text-xs text-white/50">
                                                    {{ $operator['payment_method'] === 'card' ? 'Carte bancaire' : 'Mobile Money' }}
                                                    · {{ $operator['country'] }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            @error('operator')
                                <p class="mt-3 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300">{{ $message }}</p>
                            @enderror

                            <div class="mt-5 border-t border-white/10 pt-5">
                                <label for="customer_phone" class="mb-1.5 block text-sm font-medium text-white/70">
                                    Numéro de téléphone (mobile money) <span class="text-rose-400">*</span>
                                </label>
                                <input id="customer_phone" name="customer_phone" type="tel" required
                                       x-model="phone"
                                       @input="delete missing.customer_phone"
                                       :class="missing.customer_phone ? 'border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25' : ''"
                                       class="input-dark" placeholder="ex. 774567890"
                                       autocomplete="tel">
                                <p class="mt-1 text-xs text-white/45">
                                    Ce numéro recevra la demande de confirmation de paiement.
                                </p>
                                <p x-show="missing.customer_phone" x-cloak class="mt-1 text-sm text-rose-400">Ce champ est obligatoire.</p>

                                @error('customer_phone')
                                    <p class="mt-1 text-sm text-rose-300">{{ $message }}</p>
                                @enderror
                            </div>
                        @else
                            {{-- Configuré mais aucun opérateur renvoyé par l'API : le
                                 paiement reste à finaliser sur la page dédiée. --}}
                            <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-400/30 bg-amber-500/10 p-4 text-sm text-amber-200/85">
                                <span class="mt-0.5 shrink-0 text-amber-400">@icon('info', 'w-5 h-5')</span>
                                <p>Aucun moyen de paiement n'est disponible pour le moment.
                                    Le paiement sera finalisé à l'étape suivante, sur la page sécurisée DexPay.</p>
                            </div>

                            <div class="mt-5 border-t border-white/10 pt-5">
                                <label for="customer_phone" class="mb-1.5 block text-sm font-medium text-white/70">
                                    Téléphone <span class="text-rose-400">*</span>
                                </label>
                                <input id="customer_phone" name="customer_phone" type="tel" required
                                       value="{{ old('customer_phone') }}"
                                       @input="delete missing.customer_phone"
                                       :class="missing.customer_phone ? 'border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25' : ''"
                                       class="input-dark" placeholder="+221 77 123 45 67"
                                       autocomplete="tel">
                                <p x-show="missing.customer_phone" x-cloak class="mt-1 text-sm text-rose-400">Ce champ est obligatoire.</p>
                            </div>
                        @endif
                    </section>

                    {{-- Référence pré-générée affichée ci-dessus, réutilisée à la création --}}
                    <input type="hidden" name="reference" value="{{ $reference }}">

                    {{-- Actions --}}
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <a href="{{ route('events.show', $event) }}" class="btn-outline justify-center">Annuler</a>
                        <button type="submit" @click="check($event)"
                                class="btn-hero-primary whitespace-nowrap"
                                :disabled="sending || !canPay"
                                :class="!canPay ? 'cursor-not-allowed opacity-50' : ''">
                            <span x-show="!sending" class="inline-flex items-center gap-2">
                                @if (filled($operators))
                                    @icon('wallet', 'w-4 h-4') Payer maintenant <span x-text="amountLabel">{{ money($to_pay) }}</span>
                                @else
                                    Réserver @icon('arrow-right', 'w-4 h-4')
                                @endif
                            </span>
                            <span x-show="sending" x-cloak class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                Ouverture du paiement…
                            </span>
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
                            <p class="mt-0.5 flex min-w-0 items-center gap-1 text-xs text-white/60">
                                @icon('map-pin', 'w-3.5 h-3.5 shrink-0')
                                <span class="min-w-0 truncate">{{ $event->location }}</span>
                            </p>
                        </div>
                    </div>

                    <ul class="mt-4 divide-y divide-white/10">
                        @foreach ($qtys as $typeId => $qty)
                            @php $lineType = $types->firstWhere('id', $typeId); @endphp
                            @if ($lineType)
                                <li class="flex items-center justify-between gap-3 py-2.5 text-sm"
                                    x-show="isSel({{ $typeId }})">
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
                        <span class="text-sm font-semibold text-white/70">Sous-total billets</span>
                        <span class="text-base font-bold text-white"
                              x-text="fmt(grandTotal())">{{ money($totalAmount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-3 pt-2" x-cloak x-show="totalFee > 0">
                        <span class="text-sm text-white/55">
                            Frais DexPay
                            <span class="block text-xs text-white/40">encaissement, prélevés au paiement</span>
                        </span>
                        <span class="text-sm font-semibold text-white"
                              x-text="totalFeeLabel">{{ ($service_fee + $dexpay_fee) > 0 ? money($service_fee + $dexpay_fee) : '' }}</span>
                    </div>

                    <div class="mt-3 flex items-baseline justify-between gap-3 border-t border-white/10 pt-4">
                        <span class="font-semibold text-white">Total à payer</span>
                        <span class="text-2xl font-extrabold text-orange-400"
                              x-text="amountLabel">{{ money($to_pay) }}</span>
                    </div>
                </section>

                {{-- Billets par WhatsApp --}}
                <section class="rounded-2xl border border-orange-500/25 bg-orange-500/10 p-5">
                    <div class="flex items-start gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500 text-ink-950">
                            @icon('send', 'w-5 h-5')
                        </span>
                        <p class="text-sm font-bold leading-snug text-white">
                            Vos billets seront envoyés par WhatsApp juste après le paiement.
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
