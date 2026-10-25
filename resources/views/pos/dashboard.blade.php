@extends('layouts.app', ['title' => 'Point de vente'])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
    <div class="mx-auto max-w-7xl px-4 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' }} sm:px-6 lg:px-8 pb-28 lg:pb-8">

        <div class="mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Point de vente</h1>
            <p class="mt-1 text-sm text-white/60">
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
                'dark' => true,
            ])
            @include('partials.stat', [
                'label' => 'Tickets vendus',
                'value' => number_format($totals['sold'], 0, ',', ' '),
                'icon' => 'ticket',
                'tone' => 'violet',
                'dark' => true,
            ])
            @include('partials.stat', [
                'label' => 'Chiffre d\'affaires',
                'value' => money($totals['revenue']),
                'icon' => 'currency',
                'tone' => 'emerald',
                'dark' => true,
            ])
            @include('partials.stat', [
                'label' => 'Ventes déclarées',
                'value' => number_format($totals['sales_count'], 0, ',', ' '),
                'icon' => 'receipt',
                'tone' => 'orange',
                'dark' => true,
            ])
        </div>

        @if (session('tickets_reference'))
            <div class="mt-5 rounded-2xl border border-emerald-400/30 bg-emerald-500/15 p-4">
                <p class="font-semibold text-emerald-200">Vente enregistrée ✓</p>
                <p class="mt-1 text-sm text-emerald-200/80">
                    Remettez les billets QR à l'acheteur. Réservation
                    <span class="font-mono font-semibold">{{ session('tickets_reference') }}</span>.
                </p>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            {{-- Stock disponible --}}
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                <div class="border-b border-white/10 px-5 py-4">
                    <h2 class="font-semibold text-white">Mon stock</h2>
                </div>

                @if ($allocations->isEmpty())
                    @include('partials.empty', [
                        'icon' => 'coins',
                        'title' => 'Aucun stock attribué',
                        'message' => 'Votre organisateur ne vous a pas encore attribué de tickets physiques.',
                        'dark' => true,
                    ])
                @else
                    <div class="divide-y divide-white/10">
                        @foreach ($allocations as $allocation)
                            <div class="flex items-center gap-4 px-5 py-4">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-white">{{ $allocation->ticketType?->event?->title }}</p>
                                    <p class="text-xs text-white/50">
                                        {{ $allocation->ticketType?->name }} · {{ money($allocation->ticketType?->price) }}
                                    </p>
                                </div>

                                <div class="shrink-0 text-right">
                                    <p class="text-lg font-extrabold {{ $allocation->remaining > 0 ? 'text-orange-400' : 'text-rose-400' }}">
                                        {{ $allocation->remaining }}
                                    </p>
                                    <p class="text-xs text-white/50">sur {{ $allocation->allocated }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Déclarer une vente --}}
            <form method="POST" action="{{ route('pos.sales.store') }}" class="space-y-4 rounded-2xl border border-white/10 bg-white/5 p-6">
                @csrf

                <div>
                    <h2 class="font-bold text-white">Déclarer une vente</h2>
                    <p class="mt-1 text-xs text-white/50">Le paiement est encaissé en espèces par le point de vente.</p>
                </div>

                @if ($allocations->isEmpty())
                    <p class="rounded-xl border border-amber-400/30 bg-amber-500/15 px-4 py-3 text-sm text-amber-200">
                        Aucun stock disponible : contactez votre organisateur.
                    </p>
                @else
                    <div>
                        <label for="stock_allocation_id" class="mb-1.5 block text-sm font-medium text-white/70">Type de ticket</label>
                        <select id="stock_allocation_id" name="stock_allocation_id" required
                                class="input-dark @error('stock_allocation_id') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror">
                            <option value="">— Sélectionner —</option>
                            @foreach ($allocations as $allocation)
                                <option value="{{ $allocation->id }}" @selected((int) old('stock_allocation_id') === $allocation->id)
                                        @disabled($allocation->remaining === 0)>
                                    {{ $allocation->ticketType?->event?->title }} — {{ $allocation->ticketType?->name }}
                                    ({{ $allocation->remaining }} restant(s))
                                </option>
                            @endforeach
                        </select>
                        @error('stock_allocation_id')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="quantity" class="mb-1.5 block text-sm font-medium text-white/70">Quantité vendue</label>
                        <input id="quantity" name="quantity" type="number" min="1" max="100000" required
                               value="{{ old('quantity', 1) }}"
                               class="input-dark @error('quantity') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror">
                        @error('quantity')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="buyer_name" class="mb-1.5 block text-sm font-medium text-white/70">Nom de l'acheteur</label>
                        <input id="buyer_name" name="buyer_name" value="{{ old('buyer_name') }}" required
                               placeholder="Nom du porteur du billet"
                               class="input-dark @error('buyer_name') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror">
                        @error('buyer_name')<p class="mt-1 text-sm text-rose-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="buyer_phone" class="mb-1.5 block text-sm font-medium text-white/70">Téléphone</label>
                        <input id="buyer_phone" name="buyer_phone" value="{{ old('buyer_phone') }}"
                               placeholder="+221 77 000 00 00" class="input-dark">
                    </div>

                    <div>
                        <label for="note" class="mb-1.5 block text-sm font-medium text-white/70">Note (facultatif)</label>
                        <input id="note" name="note" value="{{ old('note') }}" class="input-dark"
                               placeholder="ex : payé en espèces, série 12">
                    </div>

                    <button class="btn-hero-primary w-full justify-center !px-3 text-xs sm:!px-7 sm:text-sm">
                        @icon('check', 'w-4 h-4')
                        <span>Enregistrer la vente</span>
                    </button>
                @endif
            </form>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
            <div class="border-b border-white/10 px-5 py-4">
                <h2 class="font-semibold text-white">Mes ventes récentes</h2>
            </div>

            @if ($sales->isEmpty())
                @include('partials.empty', [
                    'icon' => 'receipt',
                    'title' => 'Aucune vente',
                    'message' => 'Vos ventes déclarées apparaîtront ici.',
                    'dark' => true,
                ])
            @else
                <div class="overflow-x-auto">
                    <table class="table table-dark">
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
                                    <td class="whitespace-nowrap text-xs text-white/60">{{ $sale->created_at->translatedFormat('d/m/Y H:i') }}</td>
                                    <td>
                                        <p class="max-w-[14rem] truncate text-sm text-white">{{ $sale->ticketType?->event?->title }}</p>
                                        <p class="text-xs text-white/50">{{ $sale->ticketType?->name }}</p>
                                    </td>
                                    <td>
                                        <p class="text-sm font-medium text-white">{{ $sale->buyer_name }}</p>
                                        @if ($sale->buyer_phone)
                                            <p class="text-xs text-white/50">{{ $sale->buyer_phone }}</p>
                                        @endif
                                    </td>
                                    <td class="text-right font-semibold text-white">{{ $sale->quantity }}</td>
                                    <td class="text-right font-semibold text-white">{{ money($sale->total_amount) }}</td>
                                    <td class="max-w-[10rem] truncate text-xs text-white/50">{{ $sale->note ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-white/10 px-5 py-3">{{ $sales->links() }}</div>
            @endif
        </div>
    </div>
@endsection
