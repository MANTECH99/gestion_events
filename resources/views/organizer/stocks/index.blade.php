@extends('layouts.organizer')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Stock de tickets physiques</h1>
        <p class="mt-1 text-sm text-ink-600">
            Attribuez des tickets à vos points de vente. Chaque vente déclarée est décomptée du stock.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            @php
                $byType = $allocations->groupBy(fn ($a) => $a->ticketType?->event?->title ?? 'Événement supprimé');
            @endphp

            @forelse ($byType as $eventTitle => $group)
                <div class="card overflow-hidden">
                    <div class="border-b border-ink-100 px-5 py-4">
                        <h2 class="font-semibold text-ink-900">{{ $eventTitle }}</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Point de vente</th>
                                    <th>Type</th>
                                    <th class="text-right">Attribué</th>
                                    <th class="text-right">Vendu</th>
                                    <th class="text-right">Restant</th>
                                    <th class="text-right">Ajuster</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($group as $allocation)
                                    <tr>
                                        <td class="font-medium text-ink-900">{{ $allocation->user?->full_name ?? '—' }}</td>
                                        <td><span class="badge bg-ink-100 text-ink-700">{{ $allocation->ticketType?->name }}</span></td>
                                        <td class="text-right font-semibold text-ink-900">{{ $allocation->allocated }}</td>
                                        <td class="text-right font-semibold text-emerald-600">{{ $allocation->sold }}</td>
                                        <td class="text-right font-semibold {{ $allocation->remaining > 0 ? 'text-brand-600' : 'text-rose-600' }}">
                                            {{ $allocation->remaining }}
                                        </td>
                                        <td class="text-right">
                                            <form method="POST" action="{{ route('organizer.stocks.update', $allocation) }}"
                                                  class="flex items-center justify-end gap-1.5">
                                                @csrf @method('PUT')
                                                <input name="allocated" type="number" min="{{ $allocation->sold }}" max="1000000"
                                                       value="{{ $allocation->allocated }}"
                                                       class="input w-24 py-1 text-right text-sm">
                                                <button class="btn-outline btn-sm" title="Enregistrer">@icon('check', 'w-4 h-4')</button>
                                            </form>

                                            @if ($allocation->sold === 0)
                                                <form method="POST" action="{{ route('organizer.stocks.destroy', $allocation) }}"
                                                      class="mt-1.5 flex justify-end"
                                                      onsubmit="return confirm('Retirer cette attribution ?');">
                                                    @csrf @method('DELETE')
                                                    <button class="btn-ghost btn-sm text-rose-600">@icon('trash', 'w-4 h-4') Retirer</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                @include('partials.empty', [
                    'icon' => 'coins',
                    'title' => 'Aucune attribution',
                    'message' => 'Attribuez du stock physique à vos points de vente depuis le formulaire ci-contre.',
                ])
            @endforelse
        </div>

        <div>
            <form method="POST" action="{{ route('organizer.stocks.store') }}" class="card sticky top-24 space-y-4 p-6">
                @csrf

                <div>
                    <h2 class="font-bold text-ink-950">Attribuer du stock</h2>
                    <p class="mt-1 text-xs text-ink-500">Une attribution existante pour ce couple sera mise à jour.</p>
                </div>

                <div>
                    <label for="ticket_type_id" class="label">Type de ticket</label>
                    <select id="ticket_type_id" name="ticket_type_id" required class="input @error('ticket_type_id') input-error @enderror">
                        <option value="">— Sélectionner —</option>
                        @foreach ($ticketTypes as $type)
                            <option value="{{ $type->id }}" @selected((int) old('ticket_type_id') === $type->id)>
                                {{ $type->event?->title }} — {{ $type->name }} ({{ money($type->price) }})
                            </option>
                        @endforeach
                    </select>
                    @error('ticket_type_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="user_id" class="label">Point de vente</label>
                    <select id="user_id" name="user_id" required class="input @error('user_id') input-error @enderror">
                        <option value="">— Sélectionner —</option>
                        @foreach ($sellers as $seller)
                            <option value="{{ $seller->id }}" @selected((int) old('user_id') === $seller->id)>
                                {{ $seller->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="allocated" class="label">Quantité attribuée</label>
                    <input id="allocated" name="allocated" type="number" min="0" max="1000000" required
                           value="{{ old('allocated', 50) }}" class="input @error('allocated') input-error @enderror">
                    @error('allocated')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <button class="btn-primary w-full justify-center">@icon('check', 'w-4 h-4') Attribuer</button>

                <p class="text-xs text-ink-500">
                    Le stock ne peut pas être réduit sous la quantité déjà vendue.
                </p>
            </form>
        </div>
    </div>
@endsection
