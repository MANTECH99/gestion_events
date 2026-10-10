@props(['title' => null, 'description' => null])

<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' — Billeterie Express' : 'Billeterie Express — Réservez vos billets en quelques secondes' }}</title>
    <meta name="description" content="{{ $description ?? 'Billeterie Express : concerts, festivals, événements au Sénégal. Payez en toute sécurité avec Wave ou Orange Money.' }}">
    <meta name="theme-color" content="#1f41f0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Billeterie Express">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ asset('images/icon-512.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
@php $hasSidebar = view()->hasSection('sidebar'); @endphp

<body class="flex min-h-full flex-col bg-ink-50">
    @include('partials.nav', ['hasSidebar' => $hasSidebar])

    <main class="flex-1">
        @include('partials.flash')

        @if ($hasSidebar)
            <div x-data="{ open: false }" @keydown.escape.window="open = false">
                @yield('sidebar')

                <div class="lg:pl-64">
                    <div class="{{ $hasSidebar ? 'w-full' : 'mx-auto max-w-7xl' }} px-4 py-8 sm:px-6 lg:px-8">
                        <button type="button" @click="open = true" aria-controls="sidebar-gestion"
                                class="btn-ghost btn-sm mb-4 lg:hidden"
                                :aria-expanded="open ? 'true' : 'false'">
                            @icon('menu', 'w-4 h-4') Menu
                        </button>

                        @yield('content')
                    </div>
                </div>
            </div>
        @else
            @yield('content')
        @endif
    </main>

    {{-- Sur les interfaces de gestion, le footer suit la colonne fixe. --}}
    @if ($hasSidebar)
        <div class="lg:pl-64">
            @include('partials.footer', ['hasSidebar' => $hasSidebar])
        </div>
    @else
        @include('partials.footer', ['hasSidebar' => $hasSidebar])
    @endif

    <div id="toast-root" class="pointer-events-none fixed inset-x-0 bottom-4 z-[100] flex flex-col items-center gap-2 px-4"></div>

    @stack('scripts')
</body>
</html>