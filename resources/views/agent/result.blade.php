@extends('layouts.app', ['title' => $title])

@section('content')
    <div class="mx-auto max-w-lg px-4 py-8 sm:px-6 lg:px-8">

        <div class="overflow-hidden rounded-3xl border-2 {{ $valid ? 'border-emerald-500' : 'border-rose-500' }} shadow-xl">
            <div class="{{ $valid ? 'bg-emerald-500' : 'bg-rose-500' }} px-6 py-8 text-center text-white">
                <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-white/20"
                      style="font-size: 3rem; font-weight: 800; line-height: 1;">
                    {{ $valid ? '✓' : '✕' }}
                </span>
                <h1 class="mt-4 text-2xl font-extrabold">{{ $title }}</h1>
                @if ($message)
                    <p class="mt-1 text-sm text-white/90">{{ $message }}</p>
                @endif
            </div>

            @if ($ticket)
                <div class="space-y-4 bg-white p-6">
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                        <dt class="text-ink-500">Code</dt>
                        <dd class="text-right font-mono text-xs font-bold text-ink-900">{{ $ticket->code }}</dd>

                        <dt class="text-ink-500">Titulaire</dt>
                        <dd class="text-right font-semibold text-ink-900">{{ $ticket->holder_name }}</dd>

                        <dt class="text-ink-500">Type de billet</dt>
                        <dd class="text-right font-semibold text-ink-900">{{ $ticket->ticketType?->name ?? '—' }}</dd>

                        <dt class="text-ink-500">Événement</dt>
                        <dd class="text-right font-semibold text-ink-900">{{ $ticket->event?->title ?? '—' }}</dd>

                        <dt class="text-ink-500">Statut</dt>
                        <dd class="text-right">
                            <span class="badge bg-{{ $ticket->status_color }}-100 text-{{ $ticket->status_color }}-700">
                                {{ $ticket->status_label }}
                            </span>
                        </dd>

                        @if ($ticket->used_at)
                            <dt class="text-ink-500">Entrée validée le</dt>
                            <dd class="text-right font-semibold text-ink-900">
                                {{ $ticket->used_at->translatedFormat('d/m/Y à H:i') }}
                                @if ($ticket->validator)
                                    <span class="block text-xs font-normal text-ink-500">par {{ $ticket->validator->full_name }}</span>
                                @endif
                            </dd>
                        @endif
                    </dl>

                    <div class="flex flex-col gap-2 pt-2 sm:flex-row">
                        <a href="{{ route('agent.scan') }}" class="btn-primary flex-1 justify-center">
                            @icon('scan', 'w-4 h-4') Scanner un autre billet
                        </a>
                        <a href="{{ route('agent.index') }}" class="btn-outline flex-1 justify-center">
                            Liste des billets
                        </a>
                    </div>
                </div>
            @else
                <div class="bg-white p-6 text-center">
                    <a href="{{ route('agent.scan') }}" class="btn-primary">Retour au scanner</a>
                </div>
            @endif
        </div>
    </div>
@endsection