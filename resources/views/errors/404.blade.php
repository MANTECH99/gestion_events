@extends('layouts.app', ['title' => '404 — Page introuvable', 'description' => 'La page que vous cherchez n\'existe pas ou a été supprimé.'])

@section('content')
    <div class="mx-auto flex min-h-[60vh] w-full max-w-xl flex-col items-center justify-center px-4 py-16 text-center">
        <p class="text-7xl font-black leading-none tracking-tighter text-brand-600">404</p>

        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-ink-950 sm:text-3xl">
            Page introuvable
        </h1>

        <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-600">
            La page ou l'événement que vous cherchez n'existe pas ou a été supprimé.
        </p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('home') }}" class="btn-primary">
                @icon('home', 'w-4 h-4') Retour à l'accueil
            </a>
            <button type="button" class="btn-outline"
                    onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.assign('{{ route('home') }}'); }">
                @icon('arrow-left', 'w-4 h-4') Page précédente
            </button>
        </div>
    </div>
@endsection