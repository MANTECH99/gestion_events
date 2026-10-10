@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Points de vente</h1>
            <p class="mt-1 text-sm text-ink-600">
                Les comptes chargés des ventes physiques, avec leur activité au comptoir.
            </p>
        </div>

        <a href="{{ route('organizer.users.index') }}" class="btn-primary btn-sm">
            @icon('user-plus', 'w-4 h-4') Nouveau point de vente
        </a>
    </div>

    @if ($pointsOfSale->isEmpty())
        <div class="card">
            @include('partials.empty', [
                'icon' => 'store',
                'title' => 'Aucun point de vente',
                'message' => 'Créez un point de vente puis attribuez-lui du stock pour suivre ses ventes.',
            ])
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($pointsOfSale as $pos)
                <a href="{{ route('organizer.pos.show', $pos) }}"
                   class="card group p-5 transition hover:border-brand-300 hover:shadow-md">
                    <div class="flex items-start gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-100 text-sm font-bold text-brand-700">
                            {{ $pos->initials }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold text-ink-950">{{ $pos->full_name }}</p>
                            <p class="truncate text-xs text-ink-500">
                                {{ $pos->point_of_sale ?: 'Point de vente' }}
                                @if ($pos->phone)
                                    · {{ $pos->phone }}
                                @endif
                            </p>
                        </div>
                        <span class="btn-ghost btn-sm shrink-0 opacity-0 transition group-hover:opacity-100">
                            @icon('eye', 'w-4 h-4')
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-3 border-t border-ink-100 pt-4 text-sm">
                        <div>
                            <p class="text-xs text-ink-500">Billets vendus</p>
                            <p class="mt-0.5 font-bold text-ink-900">{{ number_format($pos->tickets_sold_sum ?? 0, 0, ',', ' ') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500">Recette encaissée</p>
                            <p class="mt-0.5 font-bold text-ink-900">{{ money($pos->revenue_sum ?? 0, false) }} F</p>
                        </div>
                        <div>
                            <p class="text-xs text-ink-500">Opérations</p>
                            <p class="mt-0.5 font-bold text-ink-900">{{ number_format($pos->operations_count ?? 0, 0, ',', ' ') }}</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection