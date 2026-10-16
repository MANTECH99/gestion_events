@extends('layouts.app', ['title' => 'Paiement — '.$reservation->event->title])

@section('content')
    @php $event = $reservation->event; @endphp

    <div class="bg-ink-950 pb-14"
         x-data="paymentPage(@js([
                'startUrl' => route('payment.start', $reservation->reference),
                'statusUrl' => route('payment.status', $reservation->reference),
                'successUrl' => route('payment.success', $reservation->reference),
                'sandbox' => $sandbox,
                'amount' => $reservation->total_amount,
                'operators' => collect($operators)->map(fn ($op) => [
                    'value' => $op['code'],
                    'name' => $op['name'],
                    'fee' => $op['fee'] ?? null,
                    'fee_type' => $op['fee_type'] ?? 'percentage',
                ])->values()->all(),
                'amountLabel' => money($to_pay),
                'phone' => $reservation->customer_phone ?? '',
                'operator' => $operators[0]['code'] ?? '',
             ]))">

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

            <div class="relative mx-auto max-w-7xl px-4 pb-7 pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:px-6 sm:pt-28 lg:px-8">
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
                                @icon('map-pin', 'w-4 h-4') <span class="truncate">{{ $event->location }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Étapes de la commande --}}
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <ol class="flex items-center gap-2 py-5 text-xs font-semibold sm:gap-4 sm:text-sm">
                <li class="flex shrink-0 items-center gap-2 text-orange-400/80">
                    <span class="grid h-6 w-6 place-items-center rounded-full border border-orange-400/40 bg-orange-500/10 text-[11px] font-extrabold text-orange-400 sm:h-7 sm:w-7 sm:text-xs">1</span>
                    <span class="hidden sm:inline">Panier</span>
                </li>
                <li class="h-px min-w-4 flex-1 bg-orange-400/25" aria-hidden="true"></li>
                <li class="flex shrink-0 items-center gap-2 text-white">
                    <span class="grid h-6 w-6 place-items-center rounded-full bg-orange-400 text-[11px] font-extrabold text-ink-950 sm:h-7 sm:w-7 sm:text-xs">2</span>
                    <span class="hidden sm:inline">Paiement</span>
                </li>
                <li class="h-px min-w-4 flex-1 bg-white/10" aria-hidden="true"></li>
                <li class="flex shrink-0 items-center gap-2 text-white/45">
                    <span class="grid h-6 w-6 place-items-center rounded-full bg-white/10 text-[11px] font-extrabold text-white/55 sm:h-7 sm:w-7 sm:text-xs">3</span>
                    <span class="hidden sm:inline">Confirmation</span>
                </li>
            </ol>
        </div>

        @if (! $configured)
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-amber-400/30 bg-amber-500/10 p-8 text-center">
                    <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-amber-500/15 text-amber-400">
                        @icon('settings', 'w-8 h-8')
                    </span>
                    <h1 class="mt-5 text-xl font-bold text-amber-300">Paiement non configuré</h1>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-amber-200/80">
                        Les clés API DexPay sont absentes du fichier <code class="rounded bg-white/10 px-1 text-amber-200">.env</code>.
                        Renseignez <code class="rounded bg-white/10 px-1 text-amber-200">DEXPAY_API_KEY</code> et
                        <code class="rounded bg-white/10 px-1 text-amber-200">DEXPAY_API_SECRET</code>, puis rechargez cette page.
                    </p>
                    <p class="mt-4 text-xs text-amber-400/80">Mode courant : <strong>{{ config('dexpay.mode') }}</strong></p>
                </div>
            </div>
        @else
            <div class="mx-auto grid max-w-7xl gap-6 px-4 sm:px-6 lg:grid-cols-5 lg:px-8">
                {{-- Colonne principale --}}
                <div class="min-w-0 space-y-6 lg:col-span-3">
                    <div>
                        <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">
                            Réglez votre commande
                        </h1>
                        <p class="mt-1.5 text-sm text-white/60">
                            Réservation <span class="font-semibold text-white">{{ $reservation->reference }}</span>
                        </p>
                    </div>

                    @if ($sandbox)
                        <div class="flex items-start gap-3 rounded-2xl border border-amber-400/30 bg-amber-500/10 p-4">
                            <span class="mt-0.5 text-amber-400">@icon('info', 'w-5 h-5')</span>
                            <div class="text-sm">
                                <p class="font-semibold text-amber-300">Mode test (sandbox)</p>
                                <p class="mt-0.5 text-amber-200/80">
                                    Aucun paiement réel n'est effectué. Vous pouvez finaliser la commande sans débit.
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Moyens de paiement --}}
                    <section class="rounded-2xl border border-white/10 bg-white/5 p-5 sm:p-6">
                        <h2 class="flex items-center gap-2.5 text-lg font-bold text-white">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                                @icon('smartphone', 'w-5 h-5')
                            </span>
                            Choisissez votre moyen de paiement
                        </h2>
                        <p class="mt-1.5 text-sm text-white/60">
                            Vous serez redirigé vers la page sécurisée DexPay pour finaliser la transaction.
                        </p>

                        @if (filled($operators))
                            <fieldset class="mt-5">
                                <legend class="sr-only">Moyen de paiement</legend>
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
                        @else
                            <div class="mt-5 flex items-center gap-3 rounded-xl border border-dashed border-white/15 p-4 text-sm text-white/55">
                                @icon('smartphone', 'w-5 h-5')
                                Aucun moyen de paiement n'est disponible pour le moment.
                            </div>
                        @endif

                        <div class="mt-6 border-t border-white/10 pt-5">
                            <label for="phone" class="mb-1.5 block text-sm font-medium text-white/70">
                                Numéro de téléphone (mobile money)
                            </label>
                            <input id="phone" type="tel" x-model="phone"
                                   value="{{ $reservation->customer_phone }}"
                                   class="input-dark" placeholder="+221 77 123 45 67">
                            <p class="mt-1 text-xs text-white/45">
                                Ce numéro recevra la demande de confirmation de paiement.
                            </p>

                            <button type="button" @click="pay()" :disabled="!canPay"
                                    class="mt-5 w-full text-base"
                                    :class="canPay ? 'btn-primary' : 'cursor-not-allowed bg-white/10 text-white/40'">
                                <template x-if="! loading">
                                    <span class="flex items-center justify-center gap-2">
                                        @icon('wallet', 'w-5 h-5')
                                        Payer <span x-text="amountLabel"></span>
                                    </span>
                                </template>
                                <template x-if="loading">
                                    <span class="flex items-center justify-center gap-2">
                                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                        Ouverture du paiement…
                                    </span>
                                </template>
                            </button>

                            <div x-cloak x-show="error" x-text="error"
                                 class="mt-3 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300"></div>

                            <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-white/45">
                                @icon('shield', 'w-3.5 h-3.5')
                                Transaction chiffrée et traitée par DexPay (DEXCHANGE PAY).
                            </p>
                        </div>
                    </section>
                </div>

                {{-- Récapitulatif --}}
                <aside class="min-w-0 lg:col-span-2">
                    <div class="sticky top-24 space-y-4">
                        <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                            @if ($event->image_url)
                                <img src="{{ $event->image_url }}" alt="Affiche de {{ $event->title }}" class="aspect-[16/9] w-full object-cover">
                            @endif

                            <div class="p-5 sm:p-6">
                                <h2 class="flex items-center gap-2.5 text-base font-bold text-white">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500/15 text-orange-400">
                                        @icon('receipt', 'w-5 h-5')
                                    </span>
                                    Récapitulatif
                                </h2>

                                <p class="mt-3 font-semibold leading-snug text-white">{{ $event->title }}</p>
                                <p class="mt-1 text-sm text-white/60">
                                    {{ event_date_fr($event->event_date, 'd/m/Y à H:i') }} · {{ $event->location }}
                                </p>

                                <dl class="mt-5 space-y-3 border-t border-white/10 pt-4 text-sm">
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-white/55">Type de ticket</dt>
                                        <dd class="text-right font-semibold text-white">{{ $reservation->ticketType->name }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-white/55">Prix unitaire</dt>
                                        <dd class="text-right font-semibold text-white">{{ money($reservation->unit_price) }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-white/55">Quantité</dt>
                                        <dd class="text-right font-semibold text-white">× {{ $reservation->quantity }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-white/55">Sous-total billets</dt>
                                        <dd class="text-right font-semibold text-white">{{ money($reservation->total_amount) }}</dd>
                                    </div>
                                    <div x-cloak x-show="totalFee > 0" class="flex justify-between gap-3">
                                        <dt class="text-white/55">
                                            Frais DexPay
                                            <span class="block text-xs text-white/40">encaissement, prélevés au paiement</span>
                                        </dt>
                                        <dd class="text-right font-semibold text-white"
                                            x-text="totalFeeLabel">{{ ($service_fee + $dexpay_fee) > 0 ? money($service_fee + $dexpay_fee) : '' }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-white/55">Titulaire</dt>
                                        <dd class="text-right font-semibold text-white">{{ $reservation->customer_name }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-white/55">Email</dt>
                                        <dd class="break-all text-right text-white/80">{{ $reservation->customer_email }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-5 flex items-baseline justify-between gap-3 border-t border-white/10 pt-4">
                                    <span class="font-semibold text-white">Total à payer</span>
                                    <span class="text-2xl font-extrabold text-orange-400"
                                          x-text="amountLabel">{{ money($to_pay) }}</span>
                                </div>
                            </div>
                        </section>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 text-xs leading-relaxed text-white/60">
                            @icon('info', 'w-4 h-4 inline text-orange-400')
                            Les tickets ne sont générés qu'après confirmation du paiement par DexPay.
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>
@endsection
