@extends('layouts.app', ['title' => 'Créer un compte'])

@section('content')
    <div class="mx-auto flex min-h-[80vh] max-w-lg items-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full">
            <div class="text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl gradient-brand text-white shadow-lg shadow-brand-600/30">
                    @icon('user-plus', 'w-7 h-7')
                </span>
                <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-ink-950">Créer mon compte</h1>
                <p class="mt-2 text-ink-600">Pour réserver vos billets et suivre vos réservations.</p>
            </div>

            <form method="POST" action="{{ route('register.store') }}" class="card mt-8 space-y-5 p-6 sm:p-8">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="label">Prénom</label>
                        <input id="first_name" name="first_name" value="{{ old('first_name') }}" required autofocus
                               autocomplete="given-name" class="input @error('first_name') input-error @enderror">
                        @error('first_name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="last_name" class="label">Nom</label>
                        <input id="last_name" name="last_name" value="{{ old('last_name') }}" required
                               autocomplete="family-name" class="input @error('last_name') input-error @enderror">
                        @error('last_name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="username" class="label">Nom d'utilisateur</label>
                    <input id="username" name="username" value="{{ old('username') }}" required
                           autocomplete="username" placeholder="ex : aminata_diop"
                           class="input @error('username') input-error @enderror">
                    <p class="mt-1.5 text-xs text-ink-500">Lettres, chiffres, espaces, tirets et soulignés.</p>
                    @error('username')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="email" class="label">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                           autocomplete="email" inputmode="email" placeholder="vous@exemple.sn"
                           class="input @error('email') input-error @enderror">
                    @error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="label">Mot de passe</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="password" name="password" type="password" required autocomplete="new-password"
                               class="input pr-11 @error('password') input-error @enderror"
                               x-bind:type="show ? 'text' : 'password'">
                        <button type="button" @click="show = !show" tabindex="-1"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-ink-400 hover:text-ink-700"
                                x-bind:aria-label="show ? 'Masquer' : 'Afficher'">
                            @icon('eye', 'w-5 h-5')
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-ink-500">8 caractères minimum.</p>
                    @error('password')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="label">Confirmer le mot de passe</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           autocomplete="new-password" class="input @error('password_confirmation') input-error @enderror">
                    @error('password_confirmation')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Créer mon compte @icon('arrow-right', 'w-4 h-4')
                </button>

                <p class="text-center text-sm text-ink-600">
                    Déjà inscrit ?
                    <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Se connecter</a>
                </p>
            </form>

            <p class="mt-6 text-center text-xs text-ink-500">
                Le compte organisateur est créé par un administrateur Billeterie Express.
            </p>
        </div>
    </div>
@endsection