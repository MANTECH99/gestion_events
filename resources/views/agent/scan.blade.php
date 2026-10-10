@extends('layouts.app', ['title' => 'Scanner un billet'])

@section('content')
    @php
        $scannerConfig = [
            'validateUrl' => route('agent.validate'),
            'eventId' => $event?->id,
        ];
    @endphp

    <div class="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:px-8"
         x-data="ticketScanner(@js($scannerConfig))">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Scanner un billet</h1>
                <p class="mt-1 text-sm text-ink-600">Présentez le QR code du billet à la caméra.</p>
            </div>

            <a href="{{ route('agent.index') }}" class="btn-ghost btn-sm">
                @icon('list', 'w-4 h-4') Liste des billets
            </a>
        </div>

        @if ($events->count() > 1)
            <form method="GET" class="card mt-5 flex flex-wrap items-end gap-3 p-4">
                <div class="min-w-[14rem] flex-1">
                    <label for="event" class="label">Événements</label>
                    <select id="event" name="event" class="input" onchange="this.form.submit()">
                        <option value="">Tous les événements</option>
                        @foreach ($events as $e)
                            <option value="{{ $e->id }}" @selected($event?->id === $e->id)>{{ $e->title }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-outline">Filtrer</button>
            </form>
        @endif

        {{-- Résultat --}}
        <div x-cloak x-show="lastResult" class="mt-5">
            <div class="overflow-hidden rounded-3xl border-2"
                 :class="{
                    'border-emerald-500 bg-emerald-50': lastResult?.ok === true,
                    'border-rose-500 bg-rose-50': lastResult?.ok === false,
                    'border-ink-200 bg-white': lastResult?.ok === null,
                 }">
                <div class="flex flex-col items-center gap-3 px-6 py-8 text-center">
                    <span class="grid h-20 w-20 place-items-center rounded-full"
                          :class="lastResult?.ok === true ? 'bg-emerald-500 text-white'
                                : (lastResult?.ok === false ? 'bg-rose-500 text-white' : 'bg-ink-200 text-ink-500')"
                          x-text="lastResult?.ok === true ? '✓' : (lastResult?.ok === false ? '✕' : '…')"
                          style="font-size: 2.75rem; font-weight: 800;"></span>

                    <h2 class="text-2xl font-extrabold"
                        :class="lastResult?.ok === true ? 'text-emerald-700' : (lastResult?.ok === false ? 'text-rose-700' : 'text-ink-600')"
                        x-text="lastResult?.title || 'Vérification…'"></h2>

                    <p class="text-sm text-ink-700" x-text="lastResult?.message"></p>

                    <template x-if="lastResult?.ticket">
                        <dl class="mt-2 grid w-full max-w-sm grid-cols-2 gap-x-4 gap-y-2 rounded-2xl bg-white/70 px-4 py-3 text-left text-sm">
                            <dt class="text-ink-500">Titulaire</dt>
                            <dd class="font-semibold text-ink-900" x-text="lastResult.ticket.holder"></dd>

                            <dt class="text-ink-500">Type</dt>
                            <dd class="font-semibold text-ink-900" x-text="lastResult.ticket.type"></dd>

                            <dt class="text-ink-500">Code</dt>
                            <dd class="font-mono text-xs font-semibold text-ink-900" x-text="lastResult.ticket.code"></dd>

                            <template x-if="lastResult.ticket.used_at">
                                <dt class="text-ink-500">Utilisé le</dt>
                            </template>
                            <template x-if="lastResult.ticket.used_at">
                                <dd class="font-semibold text-ink-900" x-text="lastResult.ticket.used_at"></dd>
                            </template>
                        </dl>
                    </template>
                </div>
            </div>
        </div>

        {{-- Caméra --}}
        <div class="card mt-5 overflow-hidden p-4">
            <div id="qr-reader" class="min-h-[18rem] w-full overflow-hidden rounded-2xl bg-ink-950"></div>

            <div x-show="! running" x-cloak class="mt-3 flex flex-wrap gap-2">
                <button type="button" @click="start()" class="btn-outline btn-sm">
                    @icon('refresh', 'w-4 h-4') Réactiver la caméra
                </button>
                <span class="text-xs text-ink-500">Si la caméra reste bloquée, utilisez la saisie manuelle ci-dessous.</span>
            </div>

            <div x-show="error" x-cloak class="mt-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <p x-text="error"></p>
            </div>
        </div>

        {{-- Saisie manuelle --}}
        <form class="card mt-5 p-5" @submit.prevent="submitManual()">
            <label for="manual" class="label">Saisir le code manuellement</label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <input id="manual" x-model="manual" class="input font-mono" placeholder="BXT-XXXX-XXXX"
                       autocomplete="off" autocapitalize="characters" spellcheck="false">
                <button type="submit" class="btn-primary justify-center" :disabled="busy || ! manual.trim()">
                    @icon('check', 'w-4 h-4') Valider
                </button>
            </div>
            <p class="mt-2 text-xs text-ink-500">
                Format du billet : <span class="font-mono">BXT-XXXX-XXXX</span>.
                Le billet ne peut être utilisé qu'une seule fois.
            </p>
        </form>
    </div>
@endsection