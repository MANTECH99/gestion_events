@extends('layouts.admin')

@section('content')
    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" class="btn-ghost btn-sm">
            @icon('arrow-left', 'w-4 h-4') Retour
        </a>
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Organisateurs</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <form method="GET" class="card mb-5 p-4">
                <input name="q" value="{{ request('q') }}" placeholder="Rechercher un organisateur…" class="input">
            </form>

            <div class="card overflow-hidden">
                @if ($organizers->isEmpty())
                    @include('partials.empty', [
                        'icon' => 'briefcase',
                        'title' => 'Aucun organisateur',
                        'message' => 'Créez le premier compte organisateur.',
                    ])
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Organisateur</th>
                                    <th>Contact</th>
                                    <th class="text-right">Événements</th>
                                    <th>Statut</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody x-data="{ openId: null }">
                                @foreach ($organizers as $organizer)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-violet-100 text-xs font-bold text-violet-700">
                                                    {{ $organizer->initials }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="truncate font-semibold text-ink-900">{{ $organizer->full_name }}</p>
                                                    <p class="truncate text-xs text-ink-500">{{ $organizer->username }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="truncate text-xs text-ink-700">{{ $organizer->email }}</p>
                                            @if ($organizer->phone)
                                                <p class="text-xs text-ink-500">{{ $organizer->phone }}</p>
                                            @endif
                                        </td>
                                        <td class="text-right font-semibold text-ink-900">{{ $organizer->events_count }}</td>
                                        <td>
                                            <span class="badge {{ $organizer->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                {{ $organizer->is_active ? 'Actif' : 'Désactivé' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex justify-end gap-1.5">
                                                <a href="{{ route('admin.organizers.show', $organizer) }}" class="btn-ghost btn-sm" title="Voir le détail">
                                                    @icon('eye', 'w-4 h-4')
                                                </a>
                                                <button type="button" @click="openId = openId === {{ $organizer->id }} ? null : {{ $organizer->id }}" class="btn-ghost btn-sm" title="Modifier">
                                                    @icon('pencil', 'w-4 h-4')
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr x-cloak x-show="openId === {{ $organizer->id }}" class="bg-ink-50/70">
                                        <td colspan="5">
                                            <form method="POST" action="{{ route('admin.organizers.update', $organizer) }}"
                                                  class="grid gap-3 p-3 sm:grid-cols-3 lg:grid-cols-6">
                                                @csrf @method('PUT')

                                                <div>
                                                    <label class="label text-xs" for="of-{{ $organizer->id }}">Prénom</label>
                                                    <input id="of-{{ $organizer->id }}" name="first_name" value="{{ $organizer->first_name }}" required class="input py-1.5 text-sm">
                                                </div>

                                                <div>
                                                    <label class="label text-xs" for="ol-{{ $organizer->id }}">Nom</label>
                                                    <input id="ol-{{ $organizer->id }}" name="last_name" value="{{ $organizer->last_name }}" required class="input py-1.5 text-sm">
                                                </div>

                                                <div>
                                                    <label class="label text-xs" for="ou-{{ $organizer->id }}">Utilisateur</label>
                                                    <input id="ou-{{ $organizer->id }}" name="username" value="{{ $organizer->username }}" required class="input py-1.5 text-sm">
                                                </div>

                                                <div>
                                                    <label class="label text-xs" for="oe-{{ $organizer->id }}">E-mail</label>
                                                    <input id="oe-{{ $organizer->id }}" name="email" type="email" value="{{ $organizer->email }}" required class="input py-1.5 text-sm">
                                                </div>

                                                <div>
                                                    <label class="label text-xs" for="op-{{ $organizer->id }}">Téléphone</label>
                                                    <input id="op-{{ $organizer->id }}" name="phone" value="{{ $organizer->phone }}" class="input py-1.5 text-sm">
                                                </div>

                                                <div>
                                                    <label class="label text-xs" for="opw-{{ $organizer->id }}">Nouveau mot de passe</label>
                                                    <input id="opw-{{ $organizer->id }}" name="password" type="password" placeholder="Inchangé" class="input py-1.5 text-sm">
                                                    <input type="hidden" name="password_confirmation" value="">
                                                </div>

                                                <div class="flex items-end gap-2 lg:col-span-6">
                                                    <label class="flex items-center gap-2 pb-1.5 text-sm text-ink-700">
                                                        <input type="checkbox" name="is_active" value="1" class="checkbox" @checked($organizer->is_active)>
                                                        Compte actif
                                                    </label>
                                                    <button class="btn-primary btn-sm ml-auto">Enregistrer</button>
                                                    <button type="button" @click="openId = null" class="btn-ghost btn-sm">Annuler</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-ink-100 px-5 py-3">{{ $organizers->links() }}</div>
                @endif
            </div>
        </div>

        <div>
            <form method="POST" action="{{ route('admin.organizers.store') }}" class="card sticky top-24 space-y-4 p-6">
                @csrf

                <div>
                    <h2 class="font-bold text-ink-950">Créer un organisateur</h2>
                    <p class="mt-1 text-xs text-ink-500">Il pourra créer ses événements, points de vente et agents.</p>
                </div>

                <div>
                    <label for="a_first" class="label">Prénom</label>
                    <input id="a_first" name="first_name" value="{{ old('first_name') }}" required class="input @error('first_name') input-error @enderror">
                    @error('first_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="a_last" class="label">Nom</label>
                    <input id="a_last" name="last_name" value="{{ old('last_name') }}" required class="input @error('last_name') input-error @enderror">
                    @error('last_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="a_username" class="label">Nom d'utilisateur</label>
                    <input id="a_username" name="username" value="{{ old('username') }}" required class="input @error('username') input-error @enderror">
                    @error('username')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="a_email" class="label">E-mail</label>
                    <input id="a_email" name="email" type="email" value="{{ old('email') }}" required class="input @error('email') input-error @enderror">
                    @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="a_phone" class="label">Téléphone</label>
                    <input id="a_phone" name="phone" value="{{ old('phone') }}" class="input">
                </div>

                <div>
                    <label for="a_password" class="label">Mot de passe</label>
                    <input id="a_password" name="password" type="password" required class="input @error('password') input-error @enderror">
                    @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="a_password_confirmation" class="label">Confirmation</label>
                    <input id="a_password_confirmation" name="password_confirmation" type="password" required class="input">
                </div>

                <button class="btn-primary w-full justify-center">@icon('user-plus', 'w-4 h-4') Créer l'organisateur</button>
            </form>
        </div>
    </div>
@endsection