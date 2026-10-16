@extends('layouts.app', ['title' => 'Connexion'])

@section('content')
    <div class="relative bg-ink-950 pb-16">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute inset-x-0 top-0 h-[440px]" style="background:
                radial-gradient(48% 46% at 72% 30%, rgba(249,115,22,.20), rgba(249,115,22,0) 70%),
                radial-gradient(44% 42% at 28% 70%, rgba(56,160,220,.16), rgba(56,160,220,0) 72%);"></div>
        </div>

        <div class="relative mx-auto max-w-lg px-4 pb-10 pt-36 sm:flex sm:min-h-[80vh] sm:items-center sm:px-6 sm:pt-28 lg:px-8">
            <div class="w-full">
                <div class="text-center">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-orange-500/15 text-orange-400 ring-1 ring-orange-400/30">
                        @icon('user', 'w-7 h-7')
                    </span>
                    <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-white">Connexion</h1>
                    <p class="mt-2 text-white/60">Accédez à votre espace Billeterie Express.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}"
                      class="mt-8 space-y-5 rounded-2xl border border-white/10 bg-white/5 p-6 sm:p-8">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-white/70">Adresse e-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                               autocomplete="email" inputmode="email" placeholder="vous@exemple.sn"
                               class="input-dark @error('email') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror">
                        @error('email')
                            <p class="mt-1.5 text-sm text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-white/70">Mot de passe</label>
                        <div class="relative" x-data="{ show: false }">
                            <input id="password" name="password" type="password" required
                                   autocomplete="current-password" placeholder="••••••••"
                                   class="input-dark pr-11 @error('password') border-rose-400/70 focus:border-rose-400 focus:ring-rose-400/25 @enderror"
                                   x-bind:type="show ? 'text' : 'password'">
                            <button type="button" @click="show = !show" tabindex="-1"
                                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-white/40 hover:text-white/80"
                                    x-bind:aria-label="show ? 'Masquer' : 'Afficher'">
                                @icon('eye', 'w-5 h-5')
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-1.5 text-sm text-rose-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-center gap-2.5 text-sm text-white/70">
                        <input type="checkbox" name="remember" value="1" class="checkbox accent-orange-400"
                               @checked(old('remember'))>
                        Se souvenir de moi
                    </label>

                    <button type="submit" class="btn-primary w-full justify-center">
                        Se connecter @icon('arrow-right', 'w-4 h-4')
                    </button>

                    <p class="text-center text-sm text-white/60">
                        Pas encore de compte ?
                        <a href="{{ route('register') }}" class="font-semibold text-orange-400 hover:text-orange-300">S'inscrire</a>
                    </p>
                </form>

                <div class="mt-6 rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-white/60">
                    <p class="font-semibold text-white">Vous êtes agent de contrôle ?</p>
                    <p class="mt-1">
                        Connectez-vous avec le compte agent fourni par l'organisateur pour scanner les billets.
                        <a href="{{ route('agent.scan') }}" class="font-semibold text-orange-400 hover:text-orange-300">Ouvrir le scanner</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
