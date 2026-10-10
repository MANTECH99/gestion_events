@extends('layouts.app', ['title' => 'Point de vente'])

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">Point de vente</h1>
            <p class="mt-1 text-sm text-ink-600">
                Déclarez vos ventes de tickets physiques. Chaque vente génère immédiatement les billets QR de l'acheteur.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @include('partials.stat', [
                'label' => 'Stock restant',
                'value' => number_format($totals['remaining'], 0, ',', ' '),
                'icon' => 'coins',
                'tone' => 'brand',
                'hint' => 'sur '.$totals['allocated'].' attribué(s)',
            ])
            @include('partials.stat', [
                'label' => 'Tickets vendus',
                'value' => number_format($totals['sold'], 0, ',', ' '),
                'icon' => 'ticket',
                'tone' => 'violet',
            ])
            @include('partials.stat', [
                'label' => 'Chiffre d\'affaires',
                'value' => money($totals['revenue']),
                'icon' => 'currency',
                'tone' => 'emerald',
            ])
            @include('partials.stat', [
                'label' => 'Ventes déclarées',
                'value' => number_format($totals['sales_count'], 0, ',', ' '),
                'icon' => 'receipt',
                'tone' => 'orange',
            ])
        </div>

        @if (session('tickets_reference'))
            <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="font-semibold text-emerald-900">Vente enregistrée ✓</p>
                <p class="mt-1 text-sm text-emerald-800">
                    Remettez les billets QR à l'acheteur. Réservation
                    <span class="font-mono font-semibold">{{ session('tickets_reference') }}</span>.
                </p>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            {{-- Stock disponible --}}
            <div class="card overflow-hidden">
                <div class="border-b border-ink-100 px-5 py-4">
                    <h2 class="font-semibold text-ink-900">Mon stock</h2>
                </div>

                @if ($allocations->isEmpty())
                    @include('partials.empty', [
                        'icon' => 'coins',
                        'title' => 'Aucun stock attribué',
                        'message' => 'Votre organisateur ne vous a pas encore attribué de tickets physiques.',
                    ])
                @else
                    <div class="divide-y divide-ink-100">
                        @foreach ($allocations as $allocation)
                            <div class="flex items-center gap-4 px-5 py-4">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-ink-900">{{ $allocation->ticketType?->event?->title }}</p>
                                    <p class="text-xs text-ink-500">
                                        {{ $allocation->ticketType?->name }} · {{ money($allocation->ticketType?->price) }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p class="text-lg font-extrabold {{ $allocation->remaining > 0 ? 'text-brand-600' : 'text-rose-600' }}">
                                        {{ $allocation->remaining }}
                                    </p>
                                    <p class="text-xs text-ink-500">sur {{ $allocation->allocated }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Déclarer une vente --}}
            <form method="POST" action="{{ route('pos.sales.store') }}" class="card space-y-4 p-6">
                @csrf

                <div>
                    <h2 class="font-bold text-ink-950">Déclarer une vente</h2>
                    <p class="mt-1 text-xs text-ink-500">Le paiement est encaissé en espèces par le point de vente.</p>
                </div>

                @if ($allocations->isEmpty())
                    <p class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Aucun stock disponible : contactez votre organisateur.
                    </p>
                @else
                    <div>
                        <label for="stock_allocation_id" class="label">Type de ticket</label>
                        <select id="stock_allocation_id" name="stock_allocation_id" required
                                class="input @error('stock_allocation_id') input-error @enderror">
                            <option value="">— Sélectionner —</option>
                            @foreach ($allocations as $allocation)
                                <option value="{{ $allocation->id }}" @selected((int) old('stock_allocation_id') === $allocation->id)
                                        @disabled($allocation->remaining === 0)>
                                    {{ $allocation->ticketType?->event?->title }} — {{ $allocation->ticketType?->name }}
                                    ({{ $allocation->remaining }} restant(s))
                                </option>
                            @endforeach
                        </select>
                        @error('stock_allocation_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="quantity" class="label">Quantité vendue</label>
                        <input id="quantity" name="quantity" type="number" min="1" max="100000" required
                               value="{{ old('quantity', 1) }}"
                               class="input @error('quantity') input-error @enderror">
                        @error('quantity')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="buyer_name" class="label">Nom de l'acheteur</label>
                        <input id="buyer_name" name="buyer_name" value="{{ old('buyer_name') }}" required
                               placeholder="Nom du porteur du billet" class="input @error('buyer_name') input-error @enderror">
                        @error('buyer_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="buyer_phone" class="label">Téléphone</label>
                            <input id="buyer_phone" name="buyer_phone" value="{{ old('buyer_phone') }}"
                                   placeholder="+221 77 000 00 00" class="input">
                        </div>
                        <div>
                            <label for="buyer_email" class="label">E-mail</label>
                            <input id="buyer_email" name="buyer_email" type="email" value="{{ old('buyer_email') }}"
                                   class="input @error('buyer_email') input-error @enderror">
                            @error('buyer_email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="note" class="label">Note (facultatif)</label>
                        <input id="note" name="note" value="{{ old('note') }}" class="input"
                               placeholder="ex : payé en espèces, série 12">
                    </div>

                    <button class="btn-primary w-full justify-center">
                        @icon('check', 'w-4 h-4') Enregistrer la vente et générer les billets
                    </button>
                @endif
            </form>
        </div>

        <div class="card mt-6 overflow-hidden">
            <div class="border-b border-ink-100 px-5 py-4">
                <h2 class="font-semibold text-ink-900">Mes ventes récentes</h2>
            </div>

            @if ($sales->isEmpty())
                @include('partials.empty', [
                    'icon' => 'receipt',
                    'title' => 'Aucune vente',
                    'message' => 'Vos ventes déclarées apparaîtront ici.',
                ])
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Événement / Type</th>
                                <th>Acheteur</th>
                                <th class="text-right">Quantité</th>
                                <th class="text-right">Total</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sales as $sale)
                                <tr>
                                    <td class="whitespace-nowrap text-xs text-ink-600">{{ $sale->created_at->translatedFormat('d/m/Y H:i') }}</td>
                                    <td>
                                        <p class="max-w-[14rem] truncate text-sm text-ink-800">{{ $sale->ticketType?->event?->title }}</p>
                                        <p class="text-xs text-ink-500">{{ $sale->ticketType?->name }}</p>
                                    </td>
                                    <td>
                                        <p class="text-sm font-medium text-ink-900">{{ $sale->buyer_name }}</p>
                                        @if ($sale->buyer_phone)
                                            <p class="text-xs text-ink-500">{{ $sale->buyer_phone }}</p>
                                        @endif
                                    </td>
                                    <td class="text-right font-semibold text-ink-900">{{ $sale->quantity }}</td>
                                    <td class="text-right font-semibold text-ink-900">{{ money($sale->total_amount) }}</td>
                                    <td class="max-w-[10rem] truncate text-xs text-ink-500">{{ $sale->note ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-ink-100 px-5 py-3">{{ $sales->links() }}</div>
            @endif
        </div>
    </div>
@endsection