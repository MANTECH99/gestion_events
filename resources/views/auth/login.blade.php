@extends('layouts.app', ['title' => 'Connexion'])

@section('content')
    <div class="mx-auto flex min-h-[80vh] max-w-lg items-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full">
            <div class="text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl gradient-brand text-white shadow-lg shadow-brand-600/30">
                    @icon('user', 'w-7 h-7')
                </span>
                <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-ink-950">Connexion</h1>
                <p class="mt-2 text-ink-600">Accédez à votre espace Billeterie Express.</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="card mt-8 space-y-5 p-6 sm:p-8">
                @csrf

                <div>
                    <label for="email" class="label">Adresse e-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           autocomplete="email" inputmode="email" placeholder="vous@exemple.sn"
                           class="input @error('email') input-error @enderror">
                    @error('email')
                        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="label">Mot de passe</label>
                    <div class="relative" x-data="{ show: false }">
                        <input id="password" name="password" type="password" required
                               autocomplete="current-password" placeholder="••••••••"
                               class="input pr-11 @error('password') input-error @enderror"
                               x-bind:type="show ? 'text' : 'password'">
                        <button type="button" @click="show = !show" tabindex="-1"
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-ink-400 hover:text-ink-700"
                                x-bind:aria-label="show ? 'Masquer' : 'Afficher'">
                            @icon('eye', 'w-5 h-5')
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2.5 text-sm text-ink-700">
                    <input type="checkbox" name="remember" value="1" class="checkbox"
                           @checked(old('remember'))>
                    Se souvenir de moi
                </label>

                <button type="submit" class="btn-primary w-full justify-center">
                    Se connecter @icon('arrow-right', 'w-4 h-4')
                </button>

                <p class="text-center text-sm text-ink-600">
                    Pas encore de compte ?
                    <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">S'inscrire</a>
                </p>
            </form>

            <div class="mt-6 rounded-2xl border border-ink-200 bg-white/60 p-4 text-sm text-ink-600">
                <p class="font-semibold text-ink-800">Vous êtes agent de contrôle ?</p>
                <p class="mt-1">
                    Connectez-vous avec le compte agent fourni par l'organisateur pour scanner les billets.
                    <a href="{{ route('agent.scan') }}" class="font-semibold text-brand-600 hover:text-brand-700">Ouvrir le scanner</a>
                </p>
            </div>
        </div>
    </div>
@endsection