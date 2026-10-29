@extends('layouts.app', ['title' => 'Mon profil'])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
    <div class="mx-auto max-w-3xl px-4 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' }} sm:px-6 lg:px-8 pb-28 lg:pb-8">
        <div class="mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">Mon profil</h1>
            <p class="mt-1 text-sm text-white/60">Vos informations de compte.</p>
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/5">
            <div class="flex items-center gap-4 border-b border-white/10 p-6">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-orange-500/20 text-lg font-extrabold text-orange-400">
                    {{ strtoupper(substr($user->first_name ?? '', 0, 1) . substr($user->last_name ?? '', 0, 1)) ?: 'U' }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold text-white">{{ $user->full_name }}</p>
                    <p class="truncate text-sm text-white/50">@{{ $user->username }}</p>
                </div>
            </div>

            <dl class="divide-y divide-white/10">
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-white/55">E-mail</dt>
                    <dd class="truncate text-sm font-semibold text-white">{{ $user->email }}</dd>
                </div>
                @if ($user->phone)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <dt class="text-sm font-medium text-white/55">Téléphone</dt>
                        <dd class="text-sm font-semibold text-white">{{ $user->phone }}</dd>
                    </div>
                @endif
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    <dt class="text-sm font-medium text-white/55">Rôle</dt>
                    <dd class="text-sm font-semibold text-white">{{ ucfirst($user->role) }}</dd>
                </div>
            </dl>

            <div class="border-t border-white/10 p-6">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-hero-ghost w-full sm:w-auto">Se déconnecter</button>
                </form>
            </div>
        </div>

        @if ($user->isOrganizer() || $user->isAdmin())
            <div class="mt-6 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                <div class="border-b border-white/10 p-6">
                    <h2 class="text-lg font-bold text-white">Code de retrait</h2>
                    <p class="mt-1 text-sm text-white/60">
                        Ce code (4 à 6 chiffres) est demandé à chaque demande de retrait pour sécuriser vos paiements.
                        @unless ($user->hasPayoutPin())
                            <strong class="font-semibold text-amber-400">Il n'est pas encore défini.</strong>
                        @endunless
                    </p>
                </div>

                <form method="POST" action="{{ route('profil.payout-pin.update') }}" class="space-y-4 p-6">
                    @csrf
                    @method('PUT')

                    @if ($user->hasPayoutPin())
                        <div>
                            <label for="current_pin" class="label">Code actuel</label>
                            <input id="current_pin" name="current_pin" type="password" inputmode="numeric"
                                   autocomplete="off" maxlength="6" required
                                   class="input w-full max-w-xs @error('current_pin') input-error @enderror">
                            @error('current_pin')<p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div>
                        <label for="pin" class="label">{{ $user->hasPayoutPin() ? 'Nouveau code' : 'Code' }}</label>
                        <input id="pin" name="pin" type="password" inputmode="numeric"
                               autocomplete="new-password" maxlength="6" minlength="4" required
                               class="input w-full max-w-xs @error('pin') input-error @enderror">
                        <p class="mt-1.5 text-xs text-white/45">4 à 6 chiffres. Ne le partagez jamais.</p>
                        @error('pin')<p class="mt-1.5 text-sm text-rose-400">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="pin_confirmation" class="label">Confirmer le code</label>
                        <input id="pin_confirmation" name="pin_confirmation" type="password" inputmode="numeric"
                               autocomplete="new-password" maxlength="6" minlength="4" required
                               class="input w-full max-w-xs">
                    </div>

                    <button class="btn-hero-primary btn-sm !px-4 !py-2 text-sm">
                        {{ $user->hasPayoutPin() ? 'Modifier le code' : 'Enregistrer le code' }}
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection
