@extends('layouts.organizer')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Utilisateurs</h1>
        <p class="mt-1 text-sm text-ink-600">
            Créez vos points de vente et nommez vos agents de contrôle.
        </p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <form method="GET" class="card flex flex-wrap items-center gap-2 p-4">
                <input name="q" value="{{ request('q') }}" placeholder="Rechercher…" class="input min-w-[12rem] flex-1 py-2">
                <select name="role" onchange="this.form.submit()" class="input w-auto py-2 text-sm">
                    <option value="">Tous les rôles</option>
                    <option value="user" @selected(request('role') === 'user')>Point de vente</option>
                    <option value="agent" @selected(request('role') === 'agent')>Agent</option>
                </select>
                <button class="btn-outline btn-sm">Filtrer</button>
            </form>

            <div class="card overflow-hidden">
                @if ($users->isEmpty())
                    @include('partials.empty', [
                        'icon' => 'users',
                        'title' => 'Aucun utilisateur',
                        'message' => 'Créez un point de vente pour lui attribuer du stock.',
                    ])
                @else
                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Contact</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $target)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-3">
                                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-100 text-xs font-bold text-brand-700">
                                                    {{ $target->initials }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="truncate font-semibold text-ink-900">{{ $target->full_name }}</p>
                                                    <p class="truncate text-xs text-ink-500">{{ $target->username }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="truncate text-xs text-ink-700">{{ $target->email }}</p>
                                            @if ($target->phone)
                                                <p class="text-xs text-ink-500">{{ $target->phone }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $target->isAgent() ? 'bg-violet-100 text-violet-700' : 'bg-brand-100 text-brand-700' }}">
                                                {{ $target->role_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $target->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                {{ $target->is_active ? 'Actif' : 'Désactivé' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex justify-end gap-1.5">
                                                <form method="POST" action="{{ route('organizer.users.toggle-agent', $target) }}">
                                                    @csrf
                                                    <button class="btn-ghost btn-sm" title="{{ $target->isAgent() ? 'Retirer le rôle agent' : 'Nommer agent' }}">
                                                        @icon($target->isAgent() ? 'user-minus' : 'user-check', 'w-4 h-4')
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('organizer.users.toggle-active', $target) }}">
                                                    @csrf
                                                    <button class="btn-ghost btn-sm" title="{{ $target->is_active ? 'Désactiver' : 'Réactiver' }}">
                                                        @icon($target->is_active ? 'lock' : 'unlock', 'w-4 h-4')
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('organizer.users.destroy', $target) }}"
                                                      onsubmit="return confirm('Supprimer ce compte ?');">
                                                    @csrf @method('DELETE')
                                                    <button class="btn-ghost btn-sm text-rose-600" title="Supprimer">
                                                        @icon('trash', 'w-4 h-4')
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-ink-100 px-5 py-3">{{ $users->links() }}</div>
                @endif
            </div>
        </div>

        <div>
            <form method="POST" action="{{ route('organizer.users.store') }}" class="card sticky top-24 space-y-4 p-6">
                @csrf

                <div>
                    <h2 class="font-bold text-ink-950">Créer un point de vente</h2>
                    <p class="mt-1 text-xs text-ink-500">Le compte pourra vendre du stock qui lui sera attribué.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                    <div>
                        <label for="first_name" class="label">Prénom</label>
                        <input id="first_name" name="first_name" value="{{ old('first_name') }}" required class="input @error('first_name') input-error @enderror">
                        @error('first_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="last_name" class="label">Nom</label>
                        <input id="last_name" name="last_name" value="{{ old('last_name') }}" required class="input @error('last_name') input-error @enderror">
                        @error('last_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="u_username" class="label">Nom d'utilisateur</label>
                        <input id="u_username" name="username" value="{{ old('username') }}" required class="input @error('username') input-error @enderror">
                        @error('username')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="u_email" class="label">E-mail</label>
                        <input id="u_email" name="email" type="email" value="{{ old('email') }}" required class="input @error('email') input-error @enderror">
                        @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="point_of_sale" class="label">Point de vente</label>
                        <input id="point_of_sale" name="point_of_sale" value="{{ old('point_of_sale') }}"
                               placeholder="ex : Boutique Centre-ville" class="input">
                    </div>

                    <div>
                        <label for="u_phone" class="label">Téléphone</label>
                        <input id="u_phone" name="phone" value="{{ old('phone') }}" placeholder="+221 77 000 00 00" class="input">
                    </div>

                    <div>
                        <label for="u_password" class="label">Mot de passe</label>
                        <input id="u_password" name="password" type="password" required class="input @error('password') input-error @enderror">
                        @error('password')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="u_password_confirmation" class="label">Confirmation</label>
                        <input id="u_password_confirmation" name="password_confirmation" type="password" required class="input">
                    </div>
                </div>

                <button class="btn-primary w-full justify-center">@icon('user-plus', 'w-4 h-4') Créer le compte</button>
            </form>
        </div>
    </div>
@endsection
