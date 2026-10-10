@extends('layouts.organizer')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">Tableau de bord</h1>
            <p class="mt-1 text-sm text-ink-600">
                Ventes confirmées du {{ $from ? $from->translatedFormat('d M Y') : 'début' }}
                au {{ $to ? $to->translatedFormat('d M Y') : "aujourd'hui" }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            {{-- Formulaire dédié aux presets : n'envoie que « period », sinon
                 les champs from/to resteraient collés et l'emporteraient. --}}
            <form method="GET">
                <select name="period" onchange="this.form.submit()" class="input w-auto py-2 text-sm">
                    @if ($preset === 'custom')
                        <option value="custom" selected>Période personnalisée</option>
                    @endif
                    @foreach ([
                        'today' => "Aujourd'hui",
                        'yesterday' => 'Hier',
                        'week' => 'Cette semaine',
                        'month' => 'Ce mois-ci',
                        '7' => '7 derniers jours',
                        '30' => '30 derniers jours',
                        '90' => '90 derniers jours',
                        'year' => 'Cette année',
                        'all' => 'Tout',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($preset === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>

            <form method="GET" x-data="{ open: false }" class="relative">
                <button type="button" @click="open = !open" class="btn-outline btn-sm">
                    @icon('calendar', 'w-4 h-4') Dates personnalisées
                </button>
                <div x-cloak x-show="open" @click.outside="open = false"
                     class="absolute right-0 z-30 mt-2 w-72 rounded-2xl border border-ink-200 bg-white p-4 shadow-xl">
                    <label class="label" for="from">Du</label>
                    <input type="date" id="from" name="from" value="{{ $from?->toDateString() }}" class="input mb-3">
                    <label class="label" for="to">Au</label>
                    <input type="date" id="to" name="to" value="{{ $to?->toDateString() }}" class="input mb-3">
                    <button class="btn-primary w-full justify-center btn-sm">Appliquer</button>
                </div>
            </form>
        </div>
    </div>

    @if ($balanceError)
        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $balanceError }}
        </div>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('partials.stat', [
            'label' => 'Revenus encaissés',
            'value' => money($summary['total_revenue']),
            'icon' => 'currency',
            'tone' => 'brand',
            'hint' => $summary['paid_reservations'].' ticket(s) payé(s)',
            'breakdown' => money($summary['revenue_online'], false).' F en ligne · '.money($summary['revenue_physical'], false).' F en physique',
        ])
        @include('partials.stat', [
            'label' => 'Tickets vendus',
            'value' => number_format($summary['total_tickets'], 0, ',', ' '),
            'icon' => 'ticket',
            'tone' => 'violet',
            'hint' => $summary['reservations'].' réservation(s) au total',
            'breakdown' => $summary['tickets_online'].' en ligne / '.$summary['tickets_physical'].' en physique',
        ])
        @include('partials.stat', [
            'label' => 'Billets valides',
            'value' => number_format($summary['tickets_valid'], 0, ',', ' '),
            'icon' => 'check-circle',
            'tone' => 'emerald',
            'hint' => $summary['tickets_used'].' déjà utilisé(s)',
        ])
        @include('partials.stat', [
            'label' => 'Validations à l\'entrée',
            'value' => number_format($summary['validations'], 0, ',', ' '),
            'icon' => 'scan',
            'tone' => 'orange',
            'hint' => $summary['validations_invalid'].' refus(s) / '.$summary['validations_valid'].' accepté(s)',
        ])
    </div>

    @if ($balance)
        <div class="card mt-4 flex flex-wrap items-center justify-between gap-4 p-5">
            <div class="flex items-center gap-3">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                    @icon('wallet', 'w-5 h-5')
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-500">Solde DexPay</p>
                    <p class="text-xl font-extrabold text-ink-950">{{ money($balance['total'] ?? 0) }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($balance['currencies'] ?? [] as $currency => $amount)
                    <span class="badge bg-ink-100 text-ink-700">{{ $currency }} : {{ money($amount) }}</span>
                @endforeach
                <a href="{{ route('organizer.payouts.index') }}" class="btn-primary btn-sm">
                    @icon('arrow-up-right', 'w-4 h-4') Demander un retrait
                </a>
            </div>
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-ink-900">Évolution des ventes</h2>
                <span class="text-xs text-ink-500">Revenus & tickets</span>
            </div>
            <div class="mt-4 h-72">
                <canvas id="sales-chart"></canvas>
            </div>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold text-ink-900">Top événements</h2>
            @if (count($topEvents) === 0)
                <p class="mt-6 text-center text-sm text-ink-500">Aucune vente sur cette période.</p>
            @else
                <ol class="mt-4 space-y-3">
                    @foreach ($topEvents as $i => $event)
                        <li class="flex items-center gap-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-ink-100 text-xs font-bold text-ink-700">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink-900">{{ $event['title'] }}</p>
                                <p class="text-xs text-ink-500">{{ $event['tickets'] }} ticket(s)</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-ink-900">{{ money_short($event['revenue']) }}</span>
                        </li>
                    @endforeach
                </ol>
                <a href="{{ route('organizer.reports.index') }}" class="btn-outline btn-sm mt-5 w-full justify-center">Voir les rapports</a>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card p-5">
            <h2 class="font-semibold text-ink-900">Revenus par événement</h2>
            <div class="mt-4 h-64">
                <canvas id="revenue-chart"></canvas>
            </div>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold text-ink-900">Tickets par événement</h2>
            <div class="mt-4 h-64">
                <canvas id="tickets-chart"></canvas>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        salesEvolutionChart(
            document.getElementById('sales-chart'),
            @js(collect($evolution)->pluck('label')),
            @js(collect($evolution)->pluck('revenue')),
            @js(collect($evolution)->pluck('tickets')),
        );

        horizontalBarChart(
            document.getElementById('revenue-chart'),
            @js(collect($revenueByEvent)->pluck('title')),
            @js(collect($revenueByEvent)->pluck('revenue')),
        );

        groupedBarChart(
            document.getElementById('tickets-chart'),
            @js(collect($ticketsByEvent)->pluck('title')),
            [
                { label: 'En ligne', data: @js(collect($ticketsByEvent)->pluck('online')), color: '#1f41f0' },
                { label: 'Physiques', data: @js(collect($ticketsByEvent)->pluck('physical')), color: '#f98c1b' },
            ],
        );
    });
</script>
@endpush