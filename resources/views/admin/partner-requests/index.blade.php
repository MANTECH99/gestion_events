@extends('layouts.admin')

@section('content')
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" class="btn-ghost btn-sm">
            @icon('arrow-left', 'w-4 h-4') Retour
        </a>
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Demandes partenaires</h1>
        <span class="badge bg-orange-100 text-orange-700">{{ $counts['tout'] }} au total</span>
    </div>

    <p class="mb-5 text-sm text-ink-500">
        Demandes reçues depuis le formulaire « Rejoignez notre réseau de partenaires » (page /devenir-partenaire).
        Aucun mail n'est envoyé : traitez-les ici.
    </p>

    <form method="GET" class="card mb-5 flex flex-wrap items-center gap-3 p-4">
        <input name="q" value="{{ $q }}" placeholder="Rechercher (nom, email, structure)…" class="input max-w-xs flex-1">

        <div class="flex items-center gap-1.5">
            @foreach ([
                '' => ['label' => 'Toutes ('.$counts['tout'].')', 'class' => 'bg-ink-100 text-ink-700'],
                \App\Models\PartnerRequest::STATUS_NOUVEAU => ['label' => 'Nouvelles ('.$counts['nouveau'].')', 'class' => 'bg-sky-100 text-sky-700'],
                \App\Models\PartnerRequest::STATUS_TRAITE => ['label' => 'Traitées ('.$counts['traite'].')', 'class' => 'bg-emerald-100 text-emerald-700'],
            ] as $value => $filter)
                <a href="{{ route('admin.partner-requests.index', array_filter(['statut' => $value, 'q' => $q])) }}"
                   class="badge transition {{ $filterStatut === $value ? $filter['class'].' ring-2 ring-offset-1 ring-ink-300' : 'bg-ink-50 text-ink-500 hover:bg-ink-100' }}">
                    {{ $filter['label'] }}
                </a>
            @endforeach
        </div>

        <button class="btn-primary btn-sm ml-auto">Filtrer</button>
    </form>

    <div class="card overflow-hidden" x-data="{ voir: null }">
        @if ($requests->isEmpty())
            @include('partials.empty', [
                'icon' => 'handshake',
                'title' => 'Aucune demande',
                'message' => 'Les demandes envoyées depuis la page « Devenir partenaire » apparaîtront ici.',
            ])
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Contact</th>
                            <th>Structure</th>
                            <th>Type d'événement</th>
                            <th>Message</th>
                            <th>Reçue le</th>
                            <th>Statut</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $demande)
                            <tr class="{{ $demande->isTraitee() ? 'opacity-70' : '' }}">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-100 text-xs font-bold text-orange-700">
                                            {{ collect(explode(' ', $demande->nom))->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('') }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink-900">{{ $demande->nom }}</p>
                                            <a href="mailto:{{ $demande->email }}" class="truncate text-xs text-ink-500 hover:text-orange-600">{{ $demande->email }}</a>
                                        </div>
                                    </div>
                                </td>
                                <td class="max-w-[180px] truncate text-ink-700">{{ $demande->structure }}</td>
                                <td><span class="badge bg-ink-100 text-ink-700">{{ $demande->type_evenement }}</span></td>
                                <td class="max-w-[260px]">
                                    @if ($demande->message)
                                        <p class="line-clamp-2 text-xs leading-relaxed text-ink-600" title="{{ $demande->message }}">{{ $demande->message }}</p>
                                    @else
                                        <span class="text-xs italic text-ink-400">—</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap text-xs text-ink-500">
                                    {{ $demande->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    @if ($demande->isTraitee())
                                        <span class="badge bg-emerald-100 text-emerald-700">Traité</span>
                                        @if ($demande->traite_at)
                                            <p class="mt-1 text-[11px] text-ink-400">le {{ $demande->traite_at->format('d/m/Y') }}</p>
                                        @endif
                                    @else
                                        <span class="badge bg-sky-100 text-sky-700">Nouveau</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" class="btn-ghost btn-sm"
                                                title="Voir le détail de la demande"
                                                data-detail="{{ json_encode([
                                                    'id' => $demande->id,
                                                    'nom' => $demande->nom,
                                                    'email' => $demande->email,
                                                    'structure' => $demande->structure,
                                                    'type' => $demande->type_evenement,
                                                    'message' => $demande->message,
                                                    'traite' => $demande->isTraitee(),
                                                    'recue' => $demande->created_at->format('d/m/Y à H:i'),
                                                    'traiteLe' => $demande->traite_at?->format('d/m/Y à H:i'),
                                                    'url' => route('admin.partner-requests.update', $demande),
                                                ]) }}"
                                                @click="voir = JSON.parse($el.dataset.detail)">
                                            @icon('eye', 'w-4 h-4') Voir
                                        </button>

                                        <form method="POST" action="{{ route('admin.partner-requests.update', $demande) }}">
                                            @csrf @method('PATCH')
                                            <button class="{{ $demande->isTraitee() ? 'btn-ghost btn-sm' : 'btn-primary btn-sm' }}"
                                                    title="{{ $demande->isTraitee() ? 'Remettre à traiter' : 'Marquer comme traité' }}">
                                                @if ($demande->isTraitee())
                                                    @icon('clock', 'w-4 h-4') À traiter
                                                @else
                                                    @icon('check', 'w-4 h-4') Traiter
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-3">{{ $requests->links() }}</div>
        @endif

        {{-- Détail complet de la demande (tout le tableau n'est pas lisible en colonnes) --}}
        <div x-cloak x-show="voir" x-transition.opacity
             @keydown.escape.window="voir = null"
             class="fixed inset-0 z-[70] flex items-center justify-center bg-ink-950/70 p-4"
             role="dialog" aria-modal="true" aria-label="Détail de la demande">
            <div class="card modal-panel max-h-[88vh] w-full max-w-lg overflow-y-auto !rounded-3xl p-6 shadow-2xl"
                 x-transition.scale.95 @click.outside="voir = null">
                <template x-if="voir">
                    <div>
                        <div class="flex items-start gap-3">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-orange-100 text-sm font-bold text-orange-700"
                                  x-text="(voir.nom || '').split(' ').filter(Boolean).map(w => w[0]).slice(0, 2).join('').toUpperCase()"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-extrabold tracking-tight text-ink-950" x-text="voir.nom"></h2>
                                    <span class="badge" x-text="voir.traite ? 'Traité' : 'Nouveau'"
                                          :class="voir.traite ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700'"></span>
                                </div>
                                <p class="mt-0.5 text-xs text-ink-500">Demande #<span x-text="voir.id"></span> · reçue le <span x-text="voir.recue"></span></p>
                            </div>
                            <button type="button" @click="voir = null" aria-label="Fermer"
                                    class="btn-ghost btn-sm !px-2.5">@icon('close', 'w-4 h-4')</button>
                        </div>

                        <dl class="mt-5 space-y-4">
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-ink-400">Email professionnel</dt>
                                <dd class="mt-1"><a :href="'mailto:' + voir.email" class="break-all font-semibold text-orange-600 hover:underline" x-text="voir.email"></a></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-ink-400">Structure</dt>
                                <dd class="mt-1 font-semibold text-ink-900" x-text="voir.structure"></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-ink-400">Type d'événement</dt>
                                <dd class="mt-1"><span class="badge bg-ink-100 text-ink-700" x-text="voir.type"></span></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-ink-400">Message</dt>
                                <dd class="mt-1 whitespace-pre-line break-words rounded-2xl border border-ink-100 bg-ink-100 px-4 py-3 text-sm leading-relaxed text-ink-700"
                                    x-text="voir.message || '—'"></dd>
                            </div>
                            <div x-show="voir.traite" class="flex items-center gap-2 text-xs text-ink-500">
                                @icon('check-circle', 'w-4 h-4 text-emerald-500')
                                Traitée le <span x-text="voir.traiteLe"></span>
                            </div>
                        </dl>

                        <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-ink-100 pt-4">
                            <form method="POST" :action="voir.url">
                                @csrf @method('PATCH')
                                <button class="btn-primary btn-sm" x-text="voir.traite ? 'Remettre à traiter' : 'Marquer comme traité'"></button>
                            </form>
                            <button type="button" @click="voir = null" class="btn-ghost btn-sm">Fermer</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
@endsection
