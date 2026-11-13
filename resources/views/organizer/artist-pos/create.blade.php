@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Nouveau point de vente artiste</h1>
            <p class="mt-1 text-sm text-ink-600">
                Assignez un nombre de billets à un artiste : il recevra un lien vers la page de l'événement.
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
        <form method="POST" action="{{ route('organizer.artist-pos.store') }}" class="max-w-2xl">
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
                    <select id="event_id" name="event_id" required class="input @error('event_id') input-error @enderror">
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
                    <label for="ticket_limit" class="label">Nombre de billets assignés <span class="text-rose-500">*</span></label>
                    <input id="ticket_limit" name="ticket_limit" type="number" min="1" step="1" required
                           value="{{ old('ticket_limit', 1) }}"
                           placeholder="ex : 50"
                           class="input @error('ticket_limit') input-error @enderror">
                    <p class="mt-1.5 text-xs text-ink-500">Vous pourrez l'augmenter à tout moment depuis la fiche du point de vente.</p>
                    @error('ticket_limit')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
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
