@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">
                {{ $event->exists ? 'Modifier l\'événement' : 'Nouvel événement' }}
            </h1>
            <p class="mt-1 text-sm text-ink-600">
                Les champs marqués <span class="text-rose-500">*</span> sont obligatoires.
            </p>
        </div>
        <a href="{{ route('organizer.events.index') }}" class="btn-ghost btn-sm">
            @icon('arrow-left', 'w-4 h-4') Retour
        </a>
    </div>

    <form method="POST"
          action="{{ $event->exists ? route('organizer.events.update', $event) : route('organizer.events.store') }}"
          enctype="multipart/form-data"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($event->exists) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <div class="card space-y-5 p-6">
                <div>
                    <label for="title" class="label">Titre de l'événement <span class="text-rose-500">*</span></label>
                    <input id="title" name="title" value="{{ old('title', $event->title) }}" required
                           maxlength="160" placeholder="ex : Festival Teranga 2026"
                           class="input @error('title') input-error @enderror">
                    @error('title')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="description" class="label">Description</label>
                    <textarea id="description" name="description" rows="5" maxlength="5000"
                              placeholder="Présentez l'événement, la programmation, les conditions d'entrée…"
                              class="input @error('description') input-error @enderror">{{ old('description', $event->description) }}</textarea>
                    @error('description')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="event_date" class="label">Date et heure <span class="text-rose-500">*</span></label>
                        <input id="event_date" name="event_date" type="datetime-local" required
                               value="{{ old('event_date', $event->event_date?->format('Y-m-d\TH:i')) }}"
                               class="input @error('event_date') input-error @enderror">
                        @error('event_date')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="location" class="label">Lieu <span class="text-rose-500">*</span></label>
                        <input id="location" name="location" value="{{ old('location', $event->location) }}" required
                               maxlength="180" placeholder="ex : Stade Abdoulaye Wade, Dakar"
                               class="input @error('location') input-error @enderror">
                        @error('location')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="event_type" class="label">Type d'événement <span class="text-rose-500">*</span></label>
                        <select id="event_type" name="event_type" required class="input">
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('event_type', $event->event_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('event_type')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="total_tickets" class="label">Nombre total de tickets <span class="text-rose-500">*</span></label>
                        <input id="total_tickets" name="total_tickets" type="number" min="1" max="1000000" required
                               value="{{ old('total_tickets', $event->total_tickets ?? 100) }}"
                               class="input @error('total_tickets') input-error @enderror">
                        <p class="mt-1.5 text-xs text-ink-500">Stock total mis en vente (en ligne + physique).</p>
                        @error('total_tickets')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="artist_id" class="label">Artiste</label>
                        <select id="artist_id" name="artist_id" class="input @error('artist_id') input-error @enderror">
                            <option value="">Aucun artiste</option>
                            @foreach ($artists as $artist)
                                <option value="{{ $artist->id }}" @selected((int) old('artist_id', $event->artist_id) === $artist->id)>
                                    {{ $artist->name }}{{ $artist->title ? ' — '.$artist->title : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1.5 text-xs text-ink-500">Gérez les artistes depuis le menu « Artistes ».</p>
                        @error('artist_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="duration_minutes" class="label">Durée (minutes)</label>
                        <input id="duration_minutes" name="duration_minutes" type="number" min="1" max="4320"
                               value="{{ old('duration_minutes', $event->duration_minutes) }}"
                               placeholder="ex : 150"
                               class="input @error('duration_minutes') input-error @enderror">
                        <p class="mt-1.5 text-xs text-ink-500">ex : 150 pour 2 h 30.</p>
                        @error('duration_minutes')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="tags" class="label">Tags</label>
                    <input id="tags" name="tags" value="{{ old('tags', $event->tags) }}"
                           maxlength="250" placeholder="ex : afrobeat, plein air, Dakar"
                           class="input @error('tags') input-error @enderror">
                    <p class="mt-1.5 text-xs text-ink-500">Séparés par des virgules — affichés sur la page de l'événement.</p>
                    @error('tags')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card p-6">
                <label for="image" class="label">Image de couverture</label>
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                       class="input @error('image') input-error @enderror">
                <p class="mt-1.5 text-xs text-ink-500">JPG, PNG ou WebP, 4 Mo maximum.</p>
                @error('image')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror

                @if ($event->image_url)
                    <img src="{{ $event->image_url }}" alt="Couverture actuelle"
                         class="mt-4 h-40 w-full rounded-2xl object-cover">
                @endif

                <div class="mt-5 border-t border-ink-100 pt-5">
                    <label for="venue_image" class="label">Photo du lieu</label>
                    <input id="venue_image" name="venue_image" type="file" accept="image/jpeg,image/png,image/webp"
                           class="input @error('venue_image') input-error @enderror">
                    <p class="mt-1.5 text-xs text-ink-500">Façade ou intérieur du lieu, 4 Mo maximum.</p>
                    @error('venue_image')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror

                    @if ($event->venue_image_url)
                        <img src="{{ $event->venue_image_url }}" alt="Lieu actuel"
                             class="mt-4 h-40 w-full rounded-2xl object-cover">
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-5 p-6">
                <div>
                    <label for="status" class="label">Statut <span class="text-rose-500">*</span></label>
                    <select id="status" name="status" required class="input">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $event->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="organizer_display_name" class="label">Nom de l'organisateur affiché</label>
                    <input id="organizer_display_name" name="organizer_display_name"
                           value="{{ old('organizer_display_name', $event->organizer_display_name) }}"
                           maxlength="120" placeholder="ex : Moussa Diop"
                           class="input @error('organizer_display_name') input-error @enderror">
                    <p class="mt-1.5 text-xs text-ink-500">Laissez vide pour utiliser le nom de votre compte.</p>
                    @error('organizer_display_name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="avatar" class="label">Photo de profil</label>
                    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"
                           class="input @error('avatar') input-error @enderror">
                    <p class="mt-1.5 text-xs text-ink-500">Affichée dans le bloc Organisateur de la page événement, 4 Mo maximum.</p>
                    @error('avatar')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror

                    @if ($event->exists && $event->organizer?->avatar_url)
                        <img src="{{ $event->organizer->avatar_url }}" alt="Photo de profil actuelle"
                             class="mt-3 h-24 w-24 rounded-2xl object-cover">
                    @endif
                </div>

                <label class="flex items-start gap-3 rounded-xl bg-ink-50 p-3">
                    <input type="checkbox" name="online_sales_enabled" value="1" class="checkbox mt-0.5"
                           @checked(old('online_sales_enabled', $event->online_sales_enabled ?? true))>
                    <span class="text-sm">
                        <span class="block font-medium text-ink-900">Activer la vente en ligne</span>
                        <span class="block text-xs text-ink-500">Les visiteurs pourront réserver et payer par Wave / Orange Money.</span>
                    </span>
                </label>
            </div>

            <div class="card space-y-3 p-6">
                <button type="submit" class="btn-primary w-full justify-center">
                    @icon('check', 'w-4 h-4')
                    {{ $event->exists ? 'Enregistrer les modifications' : 'Créer l\'événement' }}
                </button>

                <a href="{{ route('organizer.events.index') }}" class="btn-ghost w-full justify-center">Annuler</a>

                @if (! $event->exists)
                    <p class="text-xs text-ink-500">
                        Après création, vous pourrez définir les types de tickets et leurs prix.
                    </p>
                @endif
            </div>
        </div>
    </form>

    @if ($event->exists)
        <div class="card mt-6 p-6">
            <p class="label">Photos du lieu ({{ $gallery->count() }})</p>

            @if ($gallery->isEmpty())
                <p class="mt-2 text-xs text-ink-500">Aucune photo pour l'instant — la galerie apparaît sur la page publique de l'événement.</p>
            @else
                <div class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-4">
                    @foreach ($gallery as $image)
                        <div class="overflow-hidden rounded-xl border border-ink-100">
                            <img src="{{ $image->image_url }}" alt="Photo {{ $loop->iteration }}" class="h-24 w-full object-cover">
                            <div class="flex items-center justify-between gap-1 bg-white px-1.5 py-1">
                                @unless ($loop->first)
                                    <form method="POST" action="{{ route('organizer.events.images.move', [$event, $image]) }}">
                                        @csrf
                                        <input type="hidden" name="direction" value="up">
                                        <button class="btn-ghost btn-sm px-1.5" title="Monter la photo">@icon('chevron-down', 'w-4 h-4 rotate-180')</button>
                                    </form>
                                @else
                                    <span></span>
                                @endunless

                                <div class="flex gap-1">
                                    @unless ($loop->last)
                                        <form method="POST" action="{{ route('organizer.events.images.move', [$event, $image]) }}">
                                            @csrf
                                            <input type="hidden" name="direction" value="down">
                                            <button class="btn-ghost btn-sm px-1.5" title="Descendre la photo">@icon('chevron-down', 'w-4 h-4')</button>
                                        </form>
                                    @endunless

                                    <form method="POST" action="{{ route('organizer.events.images.destroy', [$event, $image]) }}"
                                          onsubmit="return confirm('Supprimer cette photo ?');">
                                        @csrf @method('DELETE')
                                        <button class="btn-ghost btn-sm px-1.5 text-rose-600" title="Supprimer la photo">@icon('trash', 'w-4 h-4')</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('organizer.events.images.store', $event) }}"
                  enctype="multipart/form-data" class="mt-4 border-t border-ink-100 pt-4">
                @csrf
                <input name="images[]" type="file" multiple accept="image/jpeg,image/png,image/webp"
                       class="input @error('images') input-error @enderror">
                <p class="mt-1.5 text-xs text-ink-500">JPG, PNG ou WebP — plusieurs fichiers à la fois, 4 Mo maximum chacun.</p>
                @error('images')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                @error('images.*')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                <button class="btn-outline btn-sm mt-3">@icon('upload', 'w-4 h-4') Ajouter à la galerie</button>
            </form>
        </div>
    @endif
@endsection
