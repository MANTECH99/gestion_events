@if (session('success') || session('error') || session('status'))
    <div class="mx-auto max-w-7xl px-4 {{ request()->routeIs('home', 'events.show', 'booking.create', 'payment.show', 'login', 'register') ? 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' : 'pt-4' }} sm:px-6 lg:px-8 no-print">
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900"
                 x-data="{ show: true }" x-show="show" x-transition>
                <span class="mt-0.5 text-emerald-600">@icon('check-circle', 'w-5 h-5')</span>
                <p class="flex-1 font-medium">{{ session('success') }}</p>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800">@icon('close', 'w-4 h-4')</button>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"
                 x-data="{ show: true }" x-show="show" x-transition>
                <span class="mt-0.5 text-rose-600">@icon('alert', 'w-5 h-5')</span>
                <p class="flex-1 font-medium">{{ session('error') }}</p>
                <button @click="show = false" class="text-rose-600 hover:text-rose-800">@icon('close', 'w-4 h-4')</button>
            </div>
        @endif

        @if (session('status'))
            <div class="flex items-start gap-3 rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                <span class="mt-0.5 text-sky-600">@icon('info', 'w-5 h-5')</span>
                <p class="flex-1 font-medium">{{ session('status') }}</p>
            </div>
        @endif
    </div>
@endif

@if ($errors->any())
    <div class="mx-auto max-w-7xl px-4 {{ request()->routeIs('home', 'events.show', 'booking.create', 'payment.show', 'login', 'register') ? 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' : 'pt-4' }} sm:px-6 lg:px-8 no-print">
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">
            <p class="flex items-center gap-2 font-semibold">@icon('alert', 'w-4 h-4') Merci de corriger les points suivants :</p>
            <ul class="mt-2 list-inside list-disc space-y-1 text-rose-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif