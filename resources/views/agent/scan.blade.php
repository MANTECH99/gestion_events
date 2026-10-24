@extends('layouts.app', ['title' => 'Scanner un billet'])

@section('content')
    @php
        $scannerConfig = [
            'validateUrl' => route('agent.validate'),
            'eventId' => $event?->id,
        ];
        $hasFlash = $errors->any() || session('success') || session('error') || session('status');
    @endphp

    <div class="mx-auto max-w-2xl px-4 {{ $hasFlash ? 'pt-8 sm:pt-10' : 'pt-24 [@supports(-webkit-touch-callout:none)]:pt-36 sm:pt-28' }} sm:px-6 lg:px-8 pb-28 lg:pb-8"
         x-data="ticketScanner(@js($scannerConfig))">

        <div class="flex items-center justify-between gap-2">
            <h1 class="min-w-0 text-2xl font-extrabold tracking-tight text-white">Scanner un billet</h1>
            <a href="{{ route('agent.index') }}" class="shrink-0 btn-invert btn-sm !px-3 !py-1.5 text-xs">
                @icon('list', 'w-4 h-4') Liste des billets
            </a>
        </div>
        <p class="mt-1 text-sm text-white/60">Présentez le QR code du billet à la caméra.</p>

        @if ($events->count() > 1)
            <form method="GET" class="mt-5 flex flex-wrap items-end gap-3 rounded-2xl border border-white/10 bg-white/5 p-4">
                <div class="min-w-[14rem] flex-1">
                    <label for="event" class="mb-1.5 block text-sm font-medium text-white/70">Événements</label>
                    <select id="event" name="event" class="input-dark" onchange="this.form.submit()">
                        <option value="">Tous les événements</option>
                        @foreach ($events as $e)
                            <option value="{{ $e->id }}" @selected($event?->id === $e->id)>{{ $e->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-invert">Filtrer</button>
            </form>
        @endif

        {{-- Résultat --}}
        <div x-cloak x-show="lastResult" class="mt-5">
            <div class="overflow-hidden rounded-3xl border-2"
                 :class="{
                    'border-emerald-400/60 bg-emerald-500/10': lastResult?.ok === true,
                    'border-rose-400/60 bg-rose-500/10': lastResult?.ok === false,
                    'border-white/20 bg-white/5': lastResult?.ok === null,
                 }">
                <div class="flex flex-col items-center gap-3 px-6 py-8 text-center">
                    <span class="grid h-20 w-20 place-items-center rounded-full"
                          :class="lastResult?.ok === true ? 'bg-emerald-500 text-white'
                                : (lastResult?.ok === false ? 'bg-rose-500 text-white' : 'bg-white/10 text-white/50')"
                          x-text="lastResult?.ok === true ? '✓' : (lastResult?.ok === false ? '✕' : '…')"
                          style="font-size: 2.75rem; font-weight: 800;"></span>

                    <h2 class="text-2xl font-extrabold"
                        :class="lastResult?.ok === true ? 'text-emerald-300' : (lastResult?.ok === false ? 'text-rose-300' : 'text-white/60')"
                        x-text="lastResult?.title || 'Vérification…'"></h2>

                    <p class="text-sm text-white/70" x-text="lastResult?.message"></p>

                    <template x-if="lastResult?.ticket">
                        <dl class="mt-2 grid w-full max-w-sm grid-cols-2 gap-x-4 gap-y-2 rounded-2xl bg-white/5 px-4 py-3 text-left text-sm">
                            <dt class="text-white/50">Titulaire</dt>
                            <dd class="font-semibold text-white" x-text="lastResult.ticket.holder"></dd>

                            <dt class="text-white/50">Type</dt>
                            <dd class="font-semibold text-white" x-text="lastResult.ticket.type"></dd>

                            <dt class="text-white/50">Code</dt>
                            <dd class="font-mono text-xs font-semibold text-white" x-text="lastResult.ticket.code"></dd>

                            <template x-if="lastResult.ticket.used_at">
                                <dt class="text-white/50">Utilisé le</dt>
                            </template>
                            <template x-if="lastResult.ticket.used_at">
                                <dd class="font-semibold text-white" x-text="lastResult.ticket.used_at"></dd>
                            </template>
                        </dl>
                    </template>
                </div>
            </div>
        </div>

        {{-- Caméra --}}
        <div class="mt-5 overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4">
            <div id="qr-reader" class="min-h-[18rem] w-full overflow-hidden rounded-2xl bg-ink-950"></div>

            <div x-show="! running" x-cloak class="mt-3 flex flex-wrap gap-2">
                <button type="button" @click="start()" class="btn-invert btn-sm !px-3 !py-1.5 text-xs">
                    @icon('refresh', 'w-4 h-4') Réactiver la caméra
                </button>
                <span class="text-xs text-white/50">Si la caméra reste bloquée, utilisez la saisie manuelle ci-dessous.</span>
            </div>

            <div x-show="error" x-cloak class="mt-3 rounded-xl border border-amber-400/30 bg-amber-500/15 px-4 py-3 text-sm text-amber-200">
                <p x-text="error"></p>
            </div>
        </div>

        {{-- Saisie manuelle --}}
        <form class="mt-5 rounded-2xl border border-white/10 bg-white/5 p-5" @submit.prevent="submitManual()">
            <label for="manual" class="mb-1.5 block text-sm font-medium text-white/70">Saisir le code manuellement</label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <input id="manual" x-model="manual" class="input-dark font-mono" placeholder="BXT-XXXX-XXXX"
                       autocomplete="off" autocapitalize="characters" spellcheck="false">
                <button type="submit" class="btn-hero-primary justify-center" :disabled="busy || ! manual.trim()">
                    @icon('check', 'w-4 h-4') Valider
                </button>
            </div>
            <p class="mt-2 text-xs text-white/50">
                Format du billet : <span class="font-mono">BXT-XXXX-XXXX</span>.
                Le billet ne peut être utilisé qu'une seule fois.
            </p>
        </form>
    </div>
@endsection
