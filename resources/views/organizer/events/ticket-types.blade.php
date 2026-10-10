@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('organizer.events.index') }}" class="text-xs font-medium text-brand-600 hover:underline">← Mes événements</a>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-ink-950">{{ $event->title }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-600">
                <span class="inline-flex items-center gap-1.5">@icon('calendar', 'w-4 h-4') {{ event_date_fr($event->event_date) }}</span>
                <span class="inline-flex items-center gap-1.5">@icon('map-pin', 'w-4 h-4') {{ $event->location }}</span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('organizer.events.edit', $event) }}" class="btn-outline btn-sm">
                @icon('pencil', 'w-4 h-4') Modifier
            </a>
            @if ($event->isPublished())
                <a href="{{ route('events.show', $event) }}" target="_blank" rel="noopener" class="btn-ghost btn-sm">
                    @icon('eye', 'w-4 h-4') Page publique
                </a>
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        @include('partials.stat', [
            'label' => 'Stock en ligne',
            'value' => $event->available_tickets,
            'icon' => 'globe',
            'tone' => 'brand',
            'hint' => 'sur '.$event->total_tickets.' au total',
        ])
        @include('partials.stat', [
            'label' => 'Stock physique attribué',
            'value' => $event->ticketTypes->sum('physical_allocated'),
            'icon' => 'coins',
            'tone' => 'orange',
        ])
        @include('partials.stat', [
            'label' => 'Billets vendus',
            'value' => $event->ticketTypes->sum('sold_total'),
            'icon' => 'ticket',
            'tone' => 'emerald',
            'hint' => $event->ticketTypes->sum('physical_sold').' en physique',
        ])
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="border-b border-ink-100 px-5 py-4">
            <h2 class="font-semibold text-ink-900">Types de tickets</h2>
            <p class="mt-0.5 text-xs text-ink-500">
                Le prix est en XOF (FCFA). La quantité correspond au stock total du type (en ligne + physique) ;
                attribuer un stock physique réduit d'autant le stock en ligne. Laissez vide pour un stock illimité.
            </p>
        </div>

        @if ($event->ticketTypes->isEmpty())
            @include('partials.empty', [
                'icon' => 'ticket',
                'title' => 'Aucun type de ticket',
                'message' => 'Ajoutez au moins un type de ticket (ex : Standard, VIP, Tribune) pour ouvrir la vente.',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th class="text-right">Prix</th>
                            <th class="text-right">Stock en ligne</th>
                            <th class="text-right">Vendu en ligne</th>
                            <th class="text-right">Physique (attribué / vendu)</th>
                            <th class="text-center">Actif</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
<tbody x-data="{ openId: null }">
                                @foreach ($event->ticketTypes as $type)
                                    <tr>
                                <td>
                                    <p class="font-semibold text-ink-900">{{ $type->name }}</p>
                                    @if ($type->description)
                                        <p class="text-xs text-ink-500">{{ $type->description }}</p>
                                    @endif
                                </td>
                                <td class="text-right font-semibold text-ink-900">{{ money($type->price) }}</td>
                                <td class="text-right">
                                    @if ($type->quantity === null)
                                        <span class="badge bg-sky-50 text-sky-700">Illimité</span>
                                    @else
                                        <span class="font-semibold text-ink-900">{{ $type->available_online }}</span>
                                        <span class="text-xs text-ink-500">/ {{ $type->online_quantity }} en ligne</span>
                                    @endif
                                </td>
                                <td class="text-right font-semibold text-emerald-600">{{ $type->sold_online }}</td>
                                <td class="text-right text-xs text-ink-600">
                                    {{ $type->physical_allocated }} / {{ $type->physical_sold }}
                                    <span class="block text-ink-400">restant : {{ $type->physical_remaining }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $type->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-ink-100 text-ink-600' }}">
                                        {{ $type->is_active ? 'Oui' : 'Non' }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button" @click="openId = openId === {{ $type->id }} ? null : {{ $type->id }}" class="btn-ghost btn-sm" title="Modifier">
                                            @icon('pencil', 'w-4 h-4')
                                        </button>
                                        <form method="POST" action="{{ route('organizer.ticket-types.destroy', $type) }}"
                                              onsubmit="return confirm('Supprimer ce type de ticket ?');">
                                            @csrf @method('DELETE')
                                            <button class="btn-ghost btn-sm text-rose-600" title="Supprimer">
                                                @icon('trash', 'w-4 h-4')
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <tr x-cloak x-show="openId === {{ $type->id }}" class="bg-ink-50/70">
                                <td colspan="7">
                                    <form method="POST" action="{{ route('organizer.ticket-types.update', $type) }}" class="grid gap-3 p-3 sm:grid-cols-5">
                                        @csrf @method('PUT')

                                        <div>
                                            <label class="label text-xs" for="name-{{ $type->id }}">Nom</label>
                                            <input id="name-{{ $type->id }}" name="name" value="{{ $type->name }}" required class="input py-1.5 text-sm">
                                        </div>

                                        <div>
                                            <label class="label text-xs" for="price-{{ $type->id }}">Prix (FCFA)</label>
                                            <input id="price-{{ $type->id }}" name="price" type="number" min="0" required
                                                   value="{{ $type->price }}" class="input py-1.5 text-sm">
                                        </div>

                                        <div>
                                            <label class="label text-xs" for="qty-{{ $type->id }}">Stock total</label>
                                            <input id="qty-{{ $type->id }}" name="quantity" type="number" min="0"
                                                   value="{{ $type->quantity }}" placeholder="∞" class="input py-1.5 text-sm">
                                        </div>

                                        <div class="flex items-end">
                                            <label class="flex items-center gap-2 pb-1.5 text-sm text-ink-700">
                                                <input type="checkbox" name="is_active" value="1" class="checkbox" @checked($type->is_active)>
                                                Actif
                                            </label>
                                        </div>

                                        <div class="flex items-end gap-2">
                                            <button class="btn-primary btn-sm flex-1 justify-center">Enregistrer</button>
                                            <button type="button" @click="openId = null" class="btn-ghost btn-sm">Annuler</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Ajout --}}
    <form method="POST" action="{{ route('organizer.events.ticket-types.store', $event) }}" class="card mt-6 p-6">
        @csrf

        <h2 class="font-semibold text-ink-900">Ajouter un type de ticket</h2>

        <div class="mt-4 grid gap-4 sm:grid-cols-4">
            <div>
                <label for="new_name" class="label">Nom</label>
                <input id="new_name" name="name" value="{{ old('name') }}" required maxlength="80"
                       placeholder="ex : VIP" class="input @error('name') input-error @enderror">
                @error('name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="new_price" class="label">Prix (FCFA)</label>
                <input id="new_price" name="price" type="number" min="0" required value="{{ old('price', 5000) }}"
                       class="input @error('price') input-error @enderror">
                @error('price')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="new_quantity" class="label">Stock total</label>
                <input id="new_quantity" name="quantity" type="number" min="1" value="{{ old('quantity', 100) }}"
                       class="input @error('quantity') input-error @enderror">
                <p class="mt-1 text-xs text-ink-500">En ligne + physique.</p>
                @error('quantity')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-end">
                <button class="btn-primary w-full justify-center">@icon('plus', 'w-4 h-4') Ajouter</button>
            </div>
        </div>

        <div class="mt-4">
            <label for="new_description" class="label">Description (facultatif)</label>
            <input id="new_description" name="description" value="{{ old('description') }}" maxlength="255"
                   placeholder="ex : Accès prioritaire, place réservée" class="input">
        </div>
    </form>
@endsection
