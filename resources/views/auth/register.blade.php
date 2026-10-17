@extends('layouts.app', ['title' => 'Créer un compte'])

@section('content')
    @php $hasFlash = $errors->any() || session('success') || session('error') || session('status'); @endphp
    <div class="relative bg-ink-950 pb-16">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute inset-x-0 top-0 h-[440px]" style="background:
                radial-gradient(48% 46% at 72% 30%, rgba(249,115,22,.20), rgba(249,115,22,0) 70%),
                radial-gradient(44% 42% at 28% 70%, rgba(56,160,220,.16), rgba(56,160,220,0) 72%);"></div>
        </div>

        <div class="relative mx-auto max-w-lg px-4 pb-10 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-36 sm:pt-28' }} sm:flex sm:min-h-[80vh] sm:items-center sm:px-6 lg:px-8">
            <div class="w-full">
                <div class="text-center">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-orange-500/15 text-orange-400 ring-1 ring-orange-400/30">
                        @icon('user-plus', 'w-7 h-7')
                    </span>
                    <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-white">Créer mon compte</h1>
                    <p class="mt-2 text-white/60">Pour réserver vos billets et suivre vos réservations.</p>
                </div>

                <form method="POST" action="{{ route('register.store') }}"
                      class="mt-8 space-y-5 rounded-2xl border border-white/10 bg-white/5 p-6 sm:p-8">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="first_name" class="mb-1.5 block text-sm font-medium text-white/70">Prénom</label>
                            <input id="first_name" name="first_name" value="{{ old('first_name') }}" required autofocus
                                   autocomplete="given-name" class="input-dark @error('first_name') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror">
                            @error('first_name')<p class="mt-1.5 text-sm text-rose-300">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="last_name" class="mb-1.5 block text-sm font-medium text-white/70">Nom</label>
                            <input id="last_name" name="last_name" value="{{ old('last_name') }}" required
                                   autocomplete="family-name" class="input-dark @error('last_name') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror">
                            @error('last_name')<p class="mt-1.5 text-sm text-rose-300">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="username" class="mb-1.5 block text-sm font-medium text-white/70">Nom d'utilisateur</label>
                        <input id="username" name="username" value="{{ old('username') }}" required
                               autocomplete="username" placeholder="ex : aminata_diop"
                               class="input-dark @error('username') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror">
                        <p class="mt-1.5 text-xs text-white/45">Lettres, chiffres, espaces, tirets et soulignés.</p>
                        @error('username')<p class="mt-1.5 text-sm text-rose-300">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-white/70">Adresse e-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required
                               autocomplete="email" inputmode="email" placeholder="vous@exemple.sn"
                               class="input-dark @error('email') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror">
                        @error('email')<p class="mt-1.5 text-sm text-rose-300">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-white/70">Mot de passe</label>
                        <div class="relative" x-data="{ show: false }">
                            <input id="password" name="password" type="password" required autocomplete="new-password"
                                   class="input-dark pr-11 @error('password') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror"
                                   x-bind:type="show ? 'text' : 'password'">
                            <button type="button" @click="show = !show" tabindex="-1"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-white/40 hover:text-white/80"
                                    x-bind:aria-label="show ? 'Masquer' : 'Afficher'">
                                @icon('eye', 'w-5 h-5')
                            </button>
                        </div>
                        <p class="mt-1.5 text-xs text-white/45">8 caractères minimum.</p>
                        @error('password')<p class="mt-1.5 text-sm text-rose-300">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-white/70">Confirmer le mot de passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               autocomplete="new-password"
                               class="input-dark @error('password_confirmation') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror">
                        @error('password_confirmation')<p class="mt-1.5 text-sm text-rose-300">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn-primary w-full justify-center">
                        Créer mon compte @icon('arrow-right', 'w-4 h-4')
                    </button>

                    <p class="text-center text-sm text-white/60">
                        Déjà inscrit ?
                        <a href="{{ route('login') }}" class="font-semibold text-orange-400 hover:text-orange-300">Se connecter</a>
                    </p>
                </form>

                <p class="mt-6 text-center text-xs text-white/45">
                    Le compte organisateur est créé par un administrateur Billeterie Express.
                </p>
            </div>
        </div>
    </div>
@endsection
