@extends('layouts.admin')

@section('content')
    <div class="mb-5 flex items-center gap-3">
        <a href="{{ route('admin.dashboard') }}" class="btn-ghost btn-sm">
            @icon('arrow-left', 'w-4 h-4') Retour
        </a>
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Tous les utilisateurs</h1>
    </div>

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-3">
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, e-mail, utilisateur…" class="input sm:col-span-2">
        <div class="flex gap-2">
            <select name="role" class="input" onchange="this.form.submit()">
                <option value="">Tous les rôles</option>
                @foreach (\App\Models\User::ROLES as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn-outline">Filtrer</button>
        </div>
    </form>

    @if ($users->isEmpty())
        @include('partials.empty', ['icon' => 'users', 'title' => 'Aucun utilisateur', 'message' => 'Aucun compte ne correspond à ces critères.'])
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Contact</th>
                            <th>Rôle</th>
                            <th>Rattachement</th>
                            <th>Statut</th>
                            <th>Créé le</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-100 text-xs font-bold text-brand-700">
                                            {{ $user->initials }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink-900">{{ $user->full_name }}</p>
                                            <p class="truncate text-xs text-ink-500">{{ $user->username }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="truncate text-xs text-ink-700">{{ $user->email }}</p>
                                    @if ($user->phone)
                                        <p class="text-xs text-ink-500">{{ $user->phone }}</p>
                                    @endif
                                </td>
                                <td><span class="badge bg-ink-100 text-ink-700">{{ $user->role_label }}</span></td>
                                <td class="text-xs text-ink-600">{{ $user->organizer?->full_name ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ $user->is_active ? 'Actif' : 'Désactivé' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap text-xs text-ink-600">{{ $user->created_at->translatedFormat('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-3">{{ $users->links() }}</div>
        </div>
    @endif
@endsection