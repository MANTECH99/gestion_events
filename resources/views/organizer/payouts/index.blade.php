@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Retraits</h1>
            <p class="mt-1 text-sm text-ink-600">Demandez un retrait vers Wave ou Orange Money via DexPay.</p>
        </div>

        @if ($sandbox)
            <span class="badge bg-amber-100 text-amber-800">@icon('alert', 'w-3.5 h-3.5') Environnement sandbox</span>
        @endif
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="card p-5">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-ink-100 text-ink-600">
                    @icon('coins', 'w-5 h-5')
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Retraits</p>
                    <p class="text-2xl font-extrabold text-ink-950">{{ number_format($summary['count']) }}</p>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                    @icon('arrow-up-right', 'w-5 h-5')
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Montant total envoyé</p>
                    <p class="text-2xl font-extrabold text-ink-950">{{ money($summary['total_amount']) }}</p>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-amber-50 text-amber-600">
                    @icon('receipt', 'w-5 h-5')
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Frais total</p>
                    <p class="text-2xl font-extrabold text-ink-950">{{ money($summary['total_fees']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="mb-5 grid gap-4 sm:grid-cols-2">
                <div class="card p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                            @icon('wallet', 'w-5 h-5')
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Solde billetterie express</p>
                            <p class="text-2xl font-extrabold text-ink-950">{{ $balance ? money($balance['total'] ?? 0) : '—' }}</p>
                        </div>
                    </div>

                    @if ($balanceError)
                        <p class="mt-3 text-xs text-amber-700">Solde indisponible : {{ $balanceError }}</p>
                    @endif
                </div>

                <div class="card p-5">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-brand-600">
                            @icon('coins', 'w-5 h-5')
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Solde de l'organisateur</p>
                            <p class="text-2xl font-extrabold text-ink-950">{{ money($organizerRevenue) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="border-b border-ink-100 px-5 py-4">
                    <h2 class="font-semibold text-ink-900">Historique des retraits</h2>
                </div>

                @if ($payouts->isEmpty())
                    @include('partials.empty', [
                        'icon' => 'wallet',
                        'title' => 'Aucun retrait',
                        'message' => 'Vos demandes de retrait apparaîtront ici.',
                    ])
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Date</th>
                                    <th>Opérateur</th>
                                    <th class="text-right">Montant</th>
                                    <th class="text-right">Frais</th>
                                    <th>Destination</th>
                                    <th>Statut</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payouts as $payout)
                                    <tr>
                                        <td>
                                            <p class="font-mono text-xs font-semibold text-ink-900">{{ $payout->reference }}</p>
                                            @if ($payout->provider_tx_id)
                                                <p class="text-xs text-ink-500">Tx : {{ $payout->provider_tx_id }}</p>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap text-xs text-ink-600">{{ $payout->created_at->translatedFormat('d/m/Y H:i') }}</td>
                                        <td>
                                            <p class="text-sm font-medium text-ink-800">{{ \App\Services\DexPay\DexPayAccount::operatorLabel($payout->operator) }}</p>
                                            @if ($payout->recipient_name)
                                                <p class="text-xs text-ink-500">{{ $payout->recipient_name }}</p>
                                            @endif
                                        </td>
                                        <td class="text-right font-semibold text-ink-900">{{ money($payout->amount) }}</td>
                                        <td class="text-right text-ink-700">{{ $payout->fees > 0 ? money($payout->fees) : '—' }}</td>
                                        <td class="text-sm text-ink-800">{{ $payout->destination_phone }}</td>
                                        <td>
                                            <span class="badge bg-{{ $payout->status_color }}-100 text-{{ $payout->status_color }}-700">
                                                {{ $payout->status_label }}
                                            </span>
                                            @if ($payout->failure_reason)
                                                <p class="mt-1 max-w-[12rem] text-xs text-rose-600">{{ $payout->failure_reason }}</p>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            @if (! in_array($payout->status, ['COMPLETED', 'FAILED', 'CANCELLED'], true))
                                                <form method="POST" action="{{ route('organizer.payouts.sync', $payout) }}">
                                                    @csrf
                                                    <button class="btn-ghost btn-sm" title="Actualiser le statut">
                                                        @icon('refresh', 'w-4 h-4')
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-ink-100 px-5 py-3">{{ $payouts->links() }}</div>
                @endif
            </div>
        </div>

        <div>
            <form method="POST" action="{{ route('organizer.payouts.store') }}" class="card sticky top-24 space-y-4 p-6">
                @csrf

                <div>
                    <h2 class="font-bold text-ink-950">Demander un retrait</h2>
                    <p class="mt-1 text-xs text-ink-500">Le montant est envoyé sur votre compte mobile money.</p>
                </div>

                <div>
                    <label for="amount" class="label">Montant (FCFA)</label>
                    <input id="amount" name="amount" type="number" min="250" step="1" required
                           value="{{ old('amount') }}" class="input @error('amount') input-error @enderror">
                    <p class="mt-1.5 text-xs text-ink-500">Minimum 250 FCFA.</p>
                    @error('amount')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="operator" class="label">Opérateur</label>
                    <select id="operator" name="operator" required class="input @error('operator') input-error @enderror">
                        <option value="">— Sélectionner —</option>
                        @forelse ($providers as $provider)
                            <option value="{{ $provider['code'] }}" @selected(old('operator') === $provider['code'])>
                                {{ $provider['name'] }}
                            </option>
                        @empty
                            <option value="" disabled>Aucun opérateur disponible</option>
                        @endforelse
                    </select>
                    @error('operator')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="destination_phone" class="label">Numéro de téléphone</label>
                    <input id="destination_phone" name="destination_phone" required
                           value="{{ old('destination_phone') }}" placeholder="+221 77 000 00 00"
                           class="input @error('destination_phone') input-error @enderror">
                    @error('destination_phone')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="recipient_name" class="label">Nom du bénéficiaire</label>
                    <input id="recipient_name" name="recipient_name" value="{{ old('recipient_name', auth()->user()->full_name) }}"
                           class="input">
                </div>

                <button class="btn-primary w-full justify-center">@icon('arrow-up-right', 'w-4 h-4') Demander le retrait</button>

                @unless ($configured)
                    <p class="text-xs text-amber-700">
                        Les clés DexPay ne sont pas configurées : le retrait ne pourra pas être envoyé.
                    </p>
                @endunless
            </form>
        </div>
    </div>
@endsection
