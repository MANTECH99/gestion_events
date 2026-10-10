@extends('layouts.app', ['title' => 'Connexion agent requise'])

@section('content')
    <div class="mx-auto flex min-h-[80vh] max-w-md items-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="w-full text-center">
            <span class="mx-auto grid h-20 w-20 place-items-center rounded-3xl gradient-brand text-white shadow-xl shadow-brand-600/30">
                @icon('scan', 'w-10 h-10')
            </span>

            <h1 class="mt-6 text-2xl font-extrabold tracking-tight text-ink-950">Espace agent</h1>

            @if ($isWrongRole)
                <p class="mx-auto mt-3 max-w-sm text-ink-600">
                    Votre compte n'a pas les droits de contrôle des billets.
                    Seul un <strong>compte agent</strong>, créé par l'organisateur, peut valider des entrées.
                </p>

                <div class="card mt-6 p-4 text-left text-sm">
                    <p class="font-semibold text-ink-900">Votre rôle actuel : {{ auth()->user()->role_label }}</p>
                    <p class="mt-1 text-ink-600">
                        Demandez à votre organisateur de passer votre compte en rôle « agent »
                        depuis son espace utilisateurs.
                    </p>
                </div>

                <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-center">
                    <a href="{{ route('reservations.index') }}" class="btn-outline">Mes réservations</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn-ghost w-full">Se déconnecter</button>
                    </form>
                </div>
            @elseif ($loggedIn)
                <p class="mx-auto mt-3 max-w-sm text-ink-600">
                    Votre compte n'est pas encore associé à un organisateur.
                    Demandez à un administrateur de vous créer un compte agent.
                </p>
                <a href="{{ route('reservations.index') }}" class="btn-primary mt-6">Retour à mes réservations</a>
            @else
                <p class="mx-auto mt-3 max-w-sm text-ink-600">
                    Connectez-vous avec le <strong>compte agent</strong> fourni par l'organisateur
                    pour scanner et valider les billets à l'entrée.
                </p>

                <a href="{{ route('login') }}" class="btn-primary mt-6 w-full justify-center">
                    @icon('user', 'w-4 h-4') Se connecter
                </a>

                <p class="mt-4 text-xs text-ink-500">
                    Vous souhaitez réserver des billets ?
                    <a href="{{ route('home') }}" class="font-semibold text-brand-600 hover:text-brand-700">Parcourir les événements</a>
                </p>
            @endif
        </div>
    </div>
@endsection