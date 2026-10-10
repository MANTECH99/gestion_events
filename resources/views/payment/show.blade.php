@extends('layouts.app', ['title' => 'Paiement — '.$reservation->event->title])

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8"
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

        @if (! $configured)
            <div class="card mx-auto max-w-2xl border-amber-300 bg-amber-50 p-8 text-center">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-amber-100 text-amber-700">
                    @icon('settings', 'w-8 h-8')
                </span>
                <h1 class="mt-5 text-xl font-bold text-amber-900">Paiement non configuré</h1>
                <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-amber-800">
                    Les clés API DexPay sont absentes du fichier <code class="rounded bg-amber-100 px-1">.env</code>.
                    Renseignez <code class="rounded bg-amber-100 px-1">DEXPAY_API_KEY</code> et
                    <code class="rounded bg-amber-100 px-1">DEXPAY_API_SECRET</code>, puis rechargez cette page.
                </p>
                <p class="mt-4 text-xs text-amber-700">Mode courant : <strong>{{ config('dexpay.mode') }}</strong></p>
            </div>
        @else
            <div class="grid gap-8 lg:grid-cols-5">
                {{-- Résumé --}}
                <div class="lg:col-span-3">
                    <div class="flex items-center gap-2 text-sm text-ink-500">
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">1</span>
                        <span class="font-medium text-ink-900">Paiement</span>
                        <span class="h-px flex-1 bg-ink-200"></span>
                        <span class="grid h-6 w-6 place-items-center rounded-full bg-ink-200 text-xs font-bold text-ink-500">2</span>
                        <span>Billets</span>
                    </div>

                    <h1 class="mt-6 text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">
                        Réglez votre commande
                    </h1>
                    <p class="mt-1.5 text-sm text-ink-600">
                        Réservation <span class="font-semibold text-ink-900">{{ $reservation->reference }}</span>
                    </p>

                    @if ($sandbox)
                        <div class="card mt-5 flex items-start gap-3 border-amber-300 bg-amber-50 p-4">
                            <span class="mt-0.5 text-amber-600">@icon('info', 'w-5 h-5')</span>
                            <div class="text-sm">
                                <p class="font-semibold text-amber-900">Mode test (sandbox)</p>
                                <p class="mt-0.5 text-amber-800">
                                    Aucun paiement réel n'est effectué. Vous pouvez finaliser la commande sans débit.
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- Moyens de paiement --}}
                    <section class="card mt-6 p-6">
                        <h2 class="text-base font-bold text-ink-900">Choisissez votre moyen de paiement</h2>
                        <p class="mt-1 text-sm text-ink-600">
                            Vous serez redirigé vers la page sécurisée DexPay pour finaliser la transaction.
                        </p>

                        @if (filled($operators))
                            <fieldset class="mt-5">
                                <legend class="sr-only">Moyen de paiement</legend>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($operators as $operator)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-ink-200 p-4
                                                      transition hover:border-brand-300 hover:bg-brand-50
                                                      has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                            <input type="radio" name="operator" value="{{ $operator['code'] }}"
                                                   x-model="operator" class="radio">
                                            @if ($operator['logo'])
                                                <img src="{{ $operator['logo'] }}" alt="{{ $operator['name'] }}"
                                                     class="h-10 w-10 rounded-lg object-contain" loading="lazy"
                                                     onerror="this.style.display='none'">
                                            @else
                                                <span class="grid h-10 w-10 place-items-center rounded-lg bg-brand-50 text-brand-600">
                                                    @icon('smartphone', 'w-5 h-5')
                                                </span>
                                            @endif
                                            <span class="min-w-0 flex-1">
                                                <span class="block font-semibold text-ink-900">{{ $operator['name'] }}</span>
                                                <span class="block text-xs text-ink-500">
                                                    {{ $operator['payment_method'] === 'card' ? 'Carte bancaire' : 'Mobile Money' }}
                                                    · {{ $operator['country'] }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @else
                            <div class="mt-5 flex items-center gap-3 rounded-xl border border-dashed border-ink-200 p-4 text-sm text-ink-500">
                                @icon('smartphone', 'w-5 h-5')
                                Aucun moyen de paiement n'est disponible pour le moment.
                            </div>
                        @endif

                        <div class="mt-6 border-t border-ink-100 pt-5">
                            <label for="phone" class="label">Numéro de téléphone (mobile money)</label>
                            <input id="phone" type="tel" x-model="phone"
                                   value="{{ $reservation->customer_phone }}"
                                   class="input" placeholder="+221 77 123 45 67">
                            <p class="hint">Ce numéro recevra la demande de confirmation de paiement.</p>

                            <button type="button" @click="pay()" :disabled="!canPay"
                                    class="mt-5 w-full text-base"
                                    :class="canPay ? 'btn-primary' : 'btn-disabled'">
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
                                 class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"></div>

                            <p class="mt-3 text-center text-xs text-ink-500">
                                @icon('shield', 'w-3.5 h-3.5 inline')
                                Transaction chiffrée et traitée par DexPay (DEXCHANGE PAY).
                            </p>
                        </div>
                    </section>
                </div>

                {{-- Récapitulatif --}}
                <aside class="lg:col-span-2">
                    <div class="sticky top-24">
                        <div class="card overflow-hidden">
                            @if ($reservation->event->image_url)
                                <img src="{{ $reservation->event->image_url }}" alt="Affiche de {{ $reservation->event->title }}" class="aspect-[16/9] w-full object-cover">
                            @endif

                            <div class="p-6">
                                <h2 class="text-base font-bold text-ink-900">Récapitulatif</h2>

                                <p class="mt-2 font-semibold leading-snug text-ink-950">{{ $reservation->event->title }}</p>
                                <p class="mt-1 text-sm text-ink-600">
                                    {{ event_date_fr($reservation->event->event_date, 'd/m/Y à H:i') }} · {{ $reservation->event->location }}
                                </p>

                                <dl class="mt-5 space-y-3 border-t border-ink-100 pt-4 text-sm">
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-ink-600">Type de ticket</dt>
                                        <dd class="text-right font-medium text-ink-900">{{ $reservation->ticketType->name }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-ink-600">Prix unitaire</dt>
                                        <dd class="text-right font-medium text-ink-900">{{ money($reservation->unit_price) }}</dd>
                                    </div>
<div class="flex justify-between gap-3">
                                    <dt class="text-ink-600">Quantité</dt>
                                    <dd class="text-right font-medium text-ink-900">× {{ $reservation->quantity }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-ink-600">Sous-total billets</dt>
                                    <dd class="text-right font-medium text-ink-900">{{ money($reservation->total_amount) }}</dd>
                                </div>
                                <div x-show="fee > 0" @class([
                                        'flex justify-between gap-3',
                                        'hidden' => $service_fee <= 0,
                                    ])>
                                    <dt class="text-ink-600">
                                        Frais de service (<span x-text="operatorName"></span>)
                                        <span class="block text-xs text-ink-400">couverture des frais de retrait</span>
                                    </dt>
                                    <dd class="text-right font-medium text-ink-900" x-text="feeLabel"></dd>
                                </div>
                                <div x-show="dexpayFee > 0" @class([
                                        'flex justify-between gap-3',
                                        'hidden' => $dexpay_fee <= 0,
                                    ])>
                                    <dt class="text-ink-600">
                                        Frais DexPay
                                        <span class="block text-xs text-ink-400">encaissement, prélevés au paiement</span>
                                    </dt>
                                    <dd class="text-right font-medium text-ink-900" x-text="dexpayFeeLabel"></dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-ink-600">Titulaire</dt>
                                    <dd class="text-right font-medium text-ink-900">{{ $reservation->customer_name }}</dd>
                                </div>
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-ink-600">Email</dt>
                                        <dd class="text-right text-ink-700">{{ $reservation->customer_email }}</dd>
                                    </div>
                                </dl>

                                <div class="mt-5 flex items-baseline justify-between border-t border-ink-100 pt-4">
                                    <span class="font-semibold text-ink-900">Total à payer</span>
                                    <span class="text-2xl font-extrabold text-brand-700" x-text="amountLabel"></span>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-4 p-4 text-xs leading-relaxed text-ink-600">
                            @icon('info', 'w-4 h-4 inline text-brand-600')
                            Les tickets ne sont générés qu'après confirmation du paiement par DexPay.
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>
@endsection