@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
    'robots' => null,
    'ogType' => null,
    'schema' => [],
])

<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @include('partials.seo', [
        'title' => $title,
        'description' => $description,
        'image' => $image,
        'canonical' => $canonical,
        'robots' => $robots ?? (view()->hasSection('sidebar') ? 'noindex, nofollow' : null),
        'ogType' => $ogType,
        'schema' => $schema,
    ])
    <meta name="theme-color" content="#f97316">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Billeterie Express">
    <link rel="manifest" href="{{ route('pwa.manifest') }}?v={{ config('app.asset_version') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon.svg') }}?v={{ config('app.asset_version') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}?v={{ config('app.asset_version') }}">

    {{-- Splash screens iOS --}}
    @php $sv = config('app.asset_version'); @endphp
    <link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash-iphone-x.png') }}?v={{ $sv }}">
    <link rel="apple-touch-startup-image" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="{{ asset('images/splash-iphone-xr.png') }}?v={{ $sv }}">
    <link rel="apple-touch-startup-image" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash-iphone-xsmax.png') }}?v={{ $sv }}">
    <link rel="apple-touch-startup-image" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash-iphone-12.png') }}?v={{ $sv }}">
    <link rel="apple-touch-startup-image" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash-iphone-12promax.png') }}?v={{ $sv }}">
    <link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="{{ asset('images/splash-iphone-se.png') }}?v={{ $sv }}">
    <link rel="apple-touch-startup-image" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="{{ asset('images/splash-iphone-old.png') }}?v={{ $sv }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
@php $hasSidebar = view()->hasSection('sidebar'); @endphp

<body class="flex min-h-full flex-col bg-ink-50">
    @include('partials.nav', ['hasSidebar' => $hasSidebar])

    <main class="flex-1 {{ request()->routeIs('home', 'events.show', 'booking.create', 'payment.show', 'login', 'register', 'reservations.index', 'reservations.show', 'favoris.*', 'profil.*') ? 'bg-ink-950' : '' }}">
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

    <div id="toast-root" class="pointer-events-none fixed inset-x-0 bottom-28 z-[100] flex flex-col items-center gap-2 px-4 lg:bottom-4"></div>

    @unless ($hasSidebar)
        @include('partials.mobile-nav')
    @endunless

    @stack('scripts')
</body>
</html>