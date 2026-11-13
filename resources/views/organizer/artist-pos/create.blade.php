@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Nouveau point de vente artiste</h1>
            <p class="mt-1 text-sm text-ink-600">
                Assignez des billets (par type) à un artiste : il recevra un lien vers la page de l'événement.
            </p>
        </div>
        <a href="{{ route('organizer.artist-pos.index') }}" class="btn-ghost btn-sm">
            @icon('arrow-left', 'w-4 h-4') Retour
        </a>
    </div>

    @if ($artists->isEmpty() || $events->isEmpty())
        <div class="card">
            @include('partials.empty', [
                'icon' => $artists->isEmpty() ? 'star' : 'calendar',
                'title' => $artists->isEmpty() ? 'Aucun artiste' : 'Aucun événement',
                'message' => $artists->isEmpty()
                    ? 'Créez d\'abord un artiste pour lui assigner un point de vente.'
                    : 'Créez d\'abord un événement pour lui assigner un point de vente.',
                'action' => $artists->isEmpty() ? route('organizer.artists.create') : route('organizer.events.create'),
                'actionLabel' => $artists->isEmpty() ? 'Créer un artiste' : 'Créer un événement',
            ])
        </div>
    @else
        <form method="POST" action="{{ route('organizer.artist-pos.store') }}" class="max-w-2xl"
              x-data="{ event: '{{ old('event_id') }}' }">
            @csrf

            <div class="card space-y-5 p-6">
                <div>
                    <label for="artist_id" class="label">Artiste <span class="text-rose-500">*</span></label>
                    <select id="artist_id" name="artist_id" required class="input @error('artist_id') input-error @enderror">
                        <option value="">Choisissez un artiste…</option>
                        @foreach ($artists as $artist)
                            <option value="{{ $artist->id }}" @selected((int) old('artist_id') === $artist->id)>
                                {{ $artist->name }}@if ($artist->title) — {{ $artist->title }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('artist_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="event_id" class="label">Événement <span class="text-rose-500">*</span></label>
                    <select id="event_id" name="event_id" required x-model="event"
                            class="input @error('event_id') input-error @enderror">
                        <option value="">Choisissez un événement…</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" @selected((int) old('event_id') === $event->id)>
                                {{ $event->title }} — {{ event_date_fr($event->event_date, 'd M Y') }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-ink-500">Le lien de l'artiste ouvrira la page publique de cet événement.</p>
                    @error('event_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <p class="label">Billets assignés <span class="text-rose-500">*</span></p>

                    {{-- Un champ par type de billet, pour l'événement choisi
                         (les groupes des autres événements sont désactivés et
                         donc non transmis). --}}
                    @foreach ($events as $groupEvent)
                        <div x-show="event === '{{ $groupEvent->id }}'" x-cloak class="mt-2 space-y-4">
                            @forelse ($groupEvent->ticketTypes as $type)
                                @php $capacity = $capacities[$type->id] ?? null; @endphp
                                <div>
                                    <label for="alloc-{{ $type->id }}" class="label">
                                        {{ $type->name }}
                                        <span class="font-normal text-ink-400">
                                            — {{ money($type->price) }}
                                            · {{ $capacity === null ? 'stock illimité' : $capacity.' disponible(s)' }}
                                        </span>
                                    </label>
                                    <input id="alloc-{{ $type->id }}" name="allocations[{{ $type->id }}]"
                                           type="number" min="0" step="1"
                                           @if ($capacity !== null) max="{{ $capacity }}" @endif
                                           value="{{ old('allocations.'.$type->id, 0) }}"
                                           :disabled="event !== '{{ $groupEvent->id }}'"
                                           class="input @error('allocations.'.$type->id) input-error @enderror">
                                    @error('allocations.'.$type->id)
                                        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @empty
                                <p class="rounded-xl border border-dashed border-ink-200 px-4 py-6 text-center text-sm text-ink-500">
                                    Aucun type de billet actif pour cet événement.
                                </p>
                            @endforelse
                        </div>
                    @endforeach

                    <p class="mt-2 text-xs text-ink-500">
                        Ces billets sont retirés du stock en ligne public : l'artiste est sûr de pouvoir les vendre.
                        Vous pourrez augmenter chaque type à tout moment depuis la fiche.
                    </p>
                    @error('allocations')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card mt-6 flex flex-wrap gap-3 p-6">
                <button type="submit" class="btn-primary">
                    @icon('check', 'w-4 h-4') Créer le point de vente
                </button>
                <a href="{{ route('organizer.artist-pos.index') }}" class="btn-ghost">Annuler</a>
            </div>
        </form>
    @endif
@endsection
