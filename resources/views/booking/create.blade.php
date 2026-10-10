@extends('layouts.app', ['title' => 'Réserver — '.$event->title])

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-sm text-ink-500">
            <a href="{{ route('home') }}" class="hover:text-brand-600">Accueil</a>
            @icon('chevron-right', 'w-3.5 h-3.5')
            <a href="{{ route('events.show', $event) }}" class="truncate hover:text-brand-600">{{ $event->title }}</a>
            @icon('chevron-right', 'w-3.5 h-3.5')
            <span class="font-medium text-ink-700">Réservation</span>
        </nav>

        <div class="mt-6 grid gap-8 lg:grid-cols-5">
            <div class="lg:col-span-3">
                <h1 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">Réserver vos billets</h1>
                <p class="mt-1.5 text-sm text-ink-600">
                    Remplissez le formulaire ci-dessous. Vous pourrez choisir votre moyen de paiement à l'étape suivante.
                </p>

                @guest
                    <div class="card mt-5 flex items-start gap-3 border-brand-200 bg-brand-50/60 p-4">
                        <span class="mt-0.5 text-brand-600">@icon('info', 'w-5 h-5')</span>
                        <div class="text-sm">
                            <p class="font-semibold text-brand-900">Vous réservez sans compte</p>
                            <p class="mt-0.5 text-brand-800">
                                C'est possible ! Mais si vous
                                <a href="{{ route('register') }}" class="font-semibold underline">créez un compte</a>,
                                vous pourrez retrouver toutes vos réservations depuis
                                <strong>Mes réservations</strong>.
                            </p>
                        </div>
                    </div>
                @endguest

                <form method="POST" action="{{ route('booking.store', $event) }}" class="card mt-6 space-y-5 p-6">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="customer_name" class="label">Nom complet <span class="text-rose-500">*</span></label>
                            <input id="customer_name" name="customer_name" type="text" required
                                   value="{{ old('customer_name', auth()->user()?->full_name) }}"
                                   class="input" placeholder="Ex : Awa Diop">
                            <p class="hint">Le nom figurant sur le billet.</p>
                        </div>

                        <div>
                            <label for="customer_email" class="label">Adresse email <span class="text-rose-500">*</span></label>
                            <input id="customer_email" name="customer_email" type="email" required
                                   value="{{ old('customer_email', auth()->user()?->email) }}"
                                   class="input" placeholder="awa@example.com">
                        </div>

                        <div>
                            <label for="customer_phone" class="label">Téléphone</label>
                            <input id="customer_phone" name="customer_phone" type="tel"
                                   value="{{ old('customer_phone', auth()->user()?->phone) }}"
                                   class="input" placeholder="+221 77 123 45 67">
                            <p class="hint">Utilisé par DexPay pour confirmer le paiement.</p>
                        </div>
                    </div>

                    <div class="border-t border-ink-100 pt-5">
                        <label for="ticket_type_id" class="label">Type de ticket <span class="text-rose-500">*</span></label>
                        <div class="space-y-2.5">
                            @forelse ($event->ticketTypes->where('is_active', true) as $type)
                                @php $available = $type->available_online; @endphp
                                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border-2 border-ink-200 p-4 transition
                                              has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/60
                                              {{ $available !== null && $available < 1 ? 'cursor-not-allowed opacity-50' : 'hover:border-brand-300' }}">
                                    <span class="flex items-center gap-3">
                                        <input type="radio" name="ticket_type_id" value="{{ $type->id }}"
                                               class="h-5 w-5 shrink-0 border-ink-300 text-brand-600 focus:ring-brand-500"
                                               @checked(old('ticket_type_id', $selectedType) == $type->id)
                                               @disabled($available !== null && $available < 1)>
                                        <span>
                                            <span class="block font-semibold text-ink-900">{{ $type->name }}</span>
                                            @if ($type->description)
                                                <span class="block text-xs text-ink-600">{{ $type->description }}</span>
                                            @endif
                                            <span class="mt-0.5 block text-xs text-ink-500">
                                                @if ($available === null)
                                                    Stock illimité
                                                @elseif ($available > 0)
                                                    {{ $available }} restant(s)
                                                @else
                                                    Épuisé
                                                @endif
                                            </span>
                                        </span>
                                    </span>
                                    <span class="shrink-0 text-base font-extrabold text-brand-700">{{ money($type->price) }}</span>
                                </label>
                            @empty
                                <p class="rounded-xl bg-ink-50 p-4 text-sm text-ink-600">Aucun type de ticket disponible.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="border-t border-ink-100 pt-5">
                        <label for="quantity" class="label">Quantité <span class="text-rose-500">*</span></label>
                        <div x-data="{ quantity: {{ (int) old('quantity', 1) }} }" class="flex items-center gap-2">
                            <button type="button" aria-label="Diminuer la quantité"
                                    @click="quantity = Math.max(1, quantity - 1)"
                                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-ink-200 bg-white text-ink-700 transition hover:border-brand-300 hover:text-brand-600">
                                @icon('minus', 'w-4 h-4')
                            </button>
                            <input id="quantity" name="quantity" type="number" min="1" max="20" required
                                   x-model.number="quantity" class="input w-20 text-center">
                            <button type="button" aria-label="Augmenter la quantité"
                                    @click="quantity = Math.min(20, quantity + 1)"
                                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-ink-200 bg-white text-ink-700 transition hover:border-brand-300 hover:text-brand-600">
                                @icon('plus', 'w-4 h-4')
                            </button>
                        </div>
                        <p class="hint">20 tickets maximum par réservation. Un QR code sera généré par billet.</p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-ink-100 pt-5 sm:flex-row sm:justify-end">
                        <a href="{{ route('events.show', $event) }}" class="btn-outline">Annuler</a>
                        <button type="submit" class="btn-primary">
                            Réserver @icon('arrow-right', 'w-4 h-4')
                        </button>
                    </div>
                </form>
            </div>

            {{-- Résumé --}}
            <aside class="lg:col-span-2">
                <div class="sticky top-24 space-y-4">
                    <div class="card overflow-hidden">
                        @if ($event->image_url)
                            <img src="{{ $event->image_url }}" alt="" class="aspect-[16/9] w-full object-cover">
                        @endif
                        <div class="p-5">
                            <p class="text-xs font-medium uppercase tracking-wide text-ink-500">Votre réservation</p>
                            <h2 class="mt-1 font-bold leading-snug text-ink-950">{{ $event->title }}</h2>

                            <ul class="mt-4 space-y-2.5 text-sm text-ink-600">
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 text-ink-400">@icon('calendar', 'w-4 h-4')</span>
                                    {{ event_date_fr($event->event_date, 'd/m/Y à H:i') }}
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="mt-0.5 text-ink-400">@icon('map-pin', 'w-4 h-4')</span>
                                    {{ $event->location }}
                                </li>
                            </ul>

                            <div class="mt-5 rounded-xl bg-ink-50 p-4 text-xs leading-relaxed text-ink-600">
                                Le ticket n'est généré <strong class="text-ink-800">qu'après confirmation du paiement</strong>.
                                Aucun prélèvement n'est effectué tant que vous n'avez pas validé le paiement sur la page suivante.
                            </div>
                        </div>
                    </div>

                    <div class="card flex items-start gap-3 p-4">
                        <span class="mt-0.5 text-brand-600">@icon('shield', 'w-5 h-5')</span>
                        <p class="text-xs leading-relaxed text-ink-600">
                            Paiement sécurisé par <strong class="text-ink-800">DexPay</strong> — Wave, Orange Money.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection