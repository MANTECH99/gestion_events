@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">
                {{ $artist->exists ? 'Modifier l\'artiste' : 'Nouvel artiste' }}
            </h1>
            <p class="mt-1 text-sm text-ink-600">
                Les champs marqués <span class="text-rose-500">*</span> sont obligatoires.
            </p>
        </div>
        <a href="{{ route('organizer.artists.index') }}" class="btn-ghost btn-sm">
            @icon('arrow-left', 'w-4 h-4') Retour
        </a>
    </div>

    <form method="POST"
          action="{{ $artist->exists ? route('organizer.artists.update', $artist) : route('organizer.artists.store') }}"
          enctype="multipart/form-data"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($artist->exists) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <div class="card space-y-5 p-6">
                <div>
                    <label for="name" class="label">Nom <span class="text-rose-500">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $artist->name) }}" required
                           maxlength="120" placeholder="ex : Wally B. Seck"
                           class="input @error('name') input-error @enderror">
                    @error('name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="title" class="label">Titre / rôle</label>
                    <input id="title" name="title" value="{{ old('title', $artist->title) }}"
                           maxlength="120" placeholder="ex : Chanteur, DJ, groupe"
                           class="input @error('title') input-error @enderror">
                    @error('title')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="bio" class="label">Biographie</label>
                    <textarea id="bio" name="bio" rows="5" maxlength="2000"
                              placeholder="Parcours, style, succès…"
                              class="input @error('bio') input-error @enderror">{{ old('bio', $artist->bio) }}</textarea>
                    <p class="mt-1.5 text-xs text-ink-500">Tronquée à 300 caractères sur la page publique.</p>
                    @error('bio')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card p-6">
                <label for="image" class="label">Photo</label>
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"
                       class="input @error('image') input-error @enderror">
                <p class="mt-1.5 text-xs text-ink-500">JPG, PNG ou WebP, 4 Mo maximum.</p>
                @error('image')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror

                @if ($artist->image_url)
                    <img src="{{ $artist->image_url }}" alt="Photo actuelle"
                         class="mt-4 h-40 w-full rounded-2xl object-cover">
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-3 p-6">
                <button type="submit" class="btn-primary w-full justify-center">
                    @icon('check', 'w-4 h-4')
                    {{ $artist->exists ? 'Enregistrer les modifications' : 'Créer l\'artiste' }}
                </button>

                <a href="{{ route('organizer.artists.index') }}" class="btn-ghost w-full justify-center">Annuler</a>
            </div>
        </div>
    </form>
@endsection
