@extends('layouts.app', [
    'title' => $event->title,
    'description' => $event->title.' — '.event_date_fr($event->event_date).' à '.$event->location,
    'image' => $event->image_url,
    'ogType' => 'article',
    'schema' => [\App\Support\Seo::event($event)],
])

@section('content')
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
        <nav class="flex items-center gap-2 text-sm text-ink-500">
            <a href="{{ route('home') }}" class="hover:text-brand-600">Accueil</a>
            @icon('chevron-right', 'w-3.5 h-3.5')
            <span class="truncate font-medium text-ink-700">{{ $event->title }}</span>
        </nav>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-3">
            {{-- Couverture --}}
            <section class="lg:col-span-2">
                <div class="relative h-[22rem] overflow-hidden rounded-[1.75rem] bg-ink-900 shadow-2xl shadow-ink-950/20 sm:h-[30rem] lg:h-full lg:min-h-[24rem]">
                    @if ($event->image_url)
                        <img src="{{ $event->image_url }}" alt="{{ $event->title }}" class="absolute inset-0 h-full w-full object-cover">
                    @else
                        <div class="absolute inset-0 grid place-items-center gradient-brand text-8xl">
                            {{ ticket_type_icon($event->event_type) }}
                        </div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/40 to-transparent"></div>

                    <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge bg-white/95 text-ink-800">{{ ticket_type_icon($event->event_type) }} {{ $event->type_label }}</span>
                            <span class="badge bg-{{ $event->status === 'published' ? 'emerald' : 'amber' }}-500 text-white">
                                {{ $event->status_label }}
                            </span>
                        </div>

                        <h1 class="mt-4 max-w-3xl text-3xl font-extrabold leading-tight tracking-tight text-white sm:text-4xl">
                            {{ $event->title }}
                        </h1>

                        <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-white/85">
                            <span class="flex items-center gap-2">@icon('calendar', 'w-4 h-4') {{ event_date_fr($event->event_date, 'l j F Y à H:i') }}</span>
                            <span class="flex items-center gap-2">@icon('map-pin', 'w-4 h-4') {{ $event->location }}</span>
                            <span class="flex items-center gap-2">@icon('user', 'w-4 h-4') Organisé par {{ $event->organizer_display_name }}</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Réservation --}}
            <aside>
                <div class="space-y-4">
                    <div class="card overflow-hidden">
                        <div class="gradient-brand px-6 py-5 text-white">
                            <p class="text-xs uppercase tracking-wide text-white/75">Billets à partir de</p>
                            <p class="text-3xl font-extrabold">{{ $event->min_price === null ? '—' : money($event->min_price) }}</p>
                        </div>

                        <div class="p-6">
                            <div class="mb-4 flex items-center justify-between text-sm">
                                <span class="text-ink-600">Billets vendus</span>
                                <span class="font-semibold text-ink-900">{{ $event->tickets_sold }} / {{ $event->total_tickets }}</span>
                            </div>

                            @php
                                $percent = min(100, (int) round($event->tickets_sold / max(1, $event->total_tickets) * 100));
                            @endphp
                            <div class="h-2 overflow-hidden rounded-full bg-ink-100">
                                <div class="h-full rounded-full gradient-brand" style="width: {{ $percent }}%"></div>
                            </div>

                            <div class="mt-5 space-y-2.5 text-sm">
                                @foreach ([
                                    ['Vérification instantanée du billet', 'check-circle'],
                                    ['Paiement Wave ou Orange Money', 'smartphone'],
                                    ['Réservation avec ou sans compte', 'user'],
                                ] as [$text, $icon])
                                    <p class="flex items-center gap-2 text-ink-700">
                                        <span class="text-emerald-600">@icon($icon, 'w-4 h-4')</span>{{ $text }}
                                    </p>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                @if ($event->isOnSale())
                                    <a href="{{ route('booking.create', $event) }}" class="btn-primary w-full">
                                        @icon('ticket', 'w-5 h-5') Réserver
                                    </a>
                                    <p class="hint text-center">Vous pourrez payer juste après.</p>
                                @elseif ($event->isSoldOut())
                                    <button disabled class="btn-disabled w-full">Billets épuisés</button>
                                    <p class="hint text-center">Cet événement affiche complet.</p>
                                @elseif (! $event->online_sales_enabled)
                                    <button disabled class="btn-disabled w-full">Vente en boutique uniquement</button>
                                    <p class="hint text-center">Les billets sont vendus uniquement en point de vente.</p>
                                @else
                                    <button disabled class="btn-disabled w-full">Événement passé</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="card p-5 text-center text-xs text-ink-500">
                        @icon('shield', 'w-5 h-5 mx-auto mb-2')
                        Paiement traité par <strong class="text-ink-700">DexPay (DEXCHANGE PAY)</strong>.
                        Aucun ticket n'est généré avant confirmation du paiement.
                    </div>
                </div>
            </aside>
        </div>

        {{-- Détails --}}
        <div class="mt-10 space-y-8">
            <section class="card p-6">
                <h2 class="text-lg font-bold text-ink-900">À propos de l'événement</h2>
                <div class="mt-3 space-y-3 text-sm leading-relaxed text-ink-700">
                    @if ($event->description)
                        {!! nl2br(e($event->description)) !!}
                    @else
                        <p class="italic text-ink-500">Aucune description n'a été fournie pour cet événement.</p>
                    @endif
                </div>
            </section>

            <div class="grid gap-8 lg:grid-cols-2">
                <section class="card overflow-hidden">
                    <div class="border-b border-ink-100 px-6 py-4">
                        <h2 class="text-lg font-bold text-ink-900">Types de tickets</h2>
                    </div>

                    @if ($event->ticketTypes->isEmpty())
                        <p class="px-6 py-8 text-center text-sm text-ink-500">Aucun type de ticket disponible pour le moment.</p>
                    @else
                        <ul class="divide-y divide-ink-100">
                            @foreach ($event->ticketTypes as $type)
                                <li class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-semibold text-ink-900">{{ $type->name }}</p>
                                        @if ($type->description)
                                            <p class="mt-0.5 text-sm text-ink-600">{{ $type->description }}</p>
                                        @endif
                                        <p class="mt-1 text-xs text-ink-500">
                                            @if ($type->available_online === null)
                                                Stock illimité
                                            @else
                                                {{ $type->available_online }} ticket(s) disponible(s) en ligne
                                            @endif
                                        </p>
                                    </div>
                                    <p class="shrink-0 text-xl font-extrabold text-brand-700">{{ money($type->price) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="card p-6">
                    <h2 class="text-lg font-bold text-ink-900">Informations pratiques</h2>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['Date', event_date_fr($event->event_date, 'l j F Y à H:i'), 'calendar'],
                            ['Lieu', $event->location, 'map-pin'],
                            ['Type', $event->type_label, 'ticket'],
                            ['Capacité totale', number_format($event->total_tickets, 0, ',', ' ').' personnes', 'users'],
                        ] as [$label, $value, $icon])
                            <div class="flex items-start gap-3 rounded-xl bg-ink-50 p-4">
                                <span class="mt-0.5 text-brand-600">@icon($icon, 'w-5 h-5')</span>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-ink-900">{{ $value }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                </section>
            </div>
        </div>
    </div>
@endsection
