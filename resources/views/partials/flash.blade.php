@php
    $darkTheme = request()->routeIs('home', 'events.show', 'booking.create', 'payment.show', 'login', 'register', 'reservations.index', 'reservations.show', 'favoris.*', 'profil.*');
    $pad = $darkTheme
        ? 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28'
        : 'pt-4';
@endphp

@if (session('success') || session('error') || session('status'))
    <div class="mx-auto max-w-7xl px-4 {{ $pad }} sm:px-6 lg:px-8 no-print">
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm {{ $darkTheme ? 'border-emerald-400/30 bg-emerald-500/15 text-emerald-200' : 'border-emerald-200 bg-emerald-50 text-emerald-900' }}"
                 x-data="{ show: true }" x-show="show" x-transition>
                <span class="mt-0.5 {{ $darkTheme ? 'text-emerald-400' : 'text-emerald-600' }}">@icon('check-circle', 'w-5 h-5')</span>
                <p class="flex-1 font-medium">{{ session('success') }}</p>
                <button @click="show = false" class="{{ $darkTheme ? 'text-emerald-400 hover:text-emerald-200' : 'text-emerald-600 hover:text-emerald-800' }}">@icon('close', 'w-4 h-4')</button>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm {{ $darkTheme ? 'border-rose-400/30 bg-rose-500/15 text-rose-200' : 'border-rose-200 bg-rose-50 text-rose-900' }}"
                 x-data="{ show: true }" x-show="show" x-transition>
                <span class="mt-0.5 {{ $darkTheme ? 'text-rose-400' : 'text-rose-600' }}">@icon('alert', 'w-5 h-5')</span>
                <p class="flex-1 font-medium">{{ session('error') }}</p>
                <button @click="show = false" class="{{ $darkTheme ? 'text-rose-400 hover:text-rose-200' : 'text-rose-600 hover:text-rose-800' }}">@icon('close', 'w-4 h-4')</button>
            </div>
        @endif

        @if (session('status'))
            <div class="flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm {{ $darkTheme ? 'border-sky-400/30 bg-sky-500/15 text-sky-200' : 'border-sky-200 bg-sky-50 text-sky-900' }}">
                <span class="mt-0.5 {{ $darkTheme ? 'text-sky-400' : 'text-sky-600' }}">@icon('info', 'w-5 h-5')</span>
                <p class="flex-1 font-medium">{{ session('status') }}</p>
            </div>
        @endif
    </div>
@endif

@if ($errors->any())
    <div class="mx-auto max-w-7xl px-4 {{ $pad }} sm:px-6 lg:px-8 no-print">
        <div class="rounded-2xl border px-4 py-3 text-sm {{ $darkTheme ? 'border-rose-400/30 bg-rose-500/15 text-rose-200' : 'border-rose-200 bg-rose-50 text-rose-900' }}">
            <p class="flex items-center gap-2 font-semibold">@icon('alert', 'w-4 h-4') Merci de corriger les points suivants :</p>
            <ul class="mt-2 list-inside list-disc space-y-1 {{ $darkTheme ? 'text-rose-200/90' : 'text-rose-800' }}">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
