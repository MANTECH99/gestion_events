@extends('layouts.organizer')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Artistes</h1>
            <p class="mt-1 text-sm text-ink-600">Bios et photos présentées sur les pages de vos événements.</p>
        </div>
        <a href="{{ route('organizer.artists.create') }}" class="btn-primary btn-sm">
            @icon('plus', 'w-4 h-4') Nouvel artiste
        </a>
    </div>

    @if ($artists->isEmpty())
        @include('partials.empty', [
            'icon' => 'star',
            'title' => 'Aucun artiste',
            'message' => 'Créez un premier artiste pour l\'affecter ensuite à vos événements.',
        ])
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Artiste</th>
                            <th>Biographie</th>
                            <th class="text-right">Événements</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($artists as $artist)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if ($artist->image_url)
                                            <img src="{{ $artist->image_url }}" alt="" class="h-10 w-10 shrink-0 rounded-xl object-cover">
                                        @else
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-100 text-xs font-bold text-brand-700">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit($artist->name, 1, '')) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-ink-900">{{ $artist->name }}</p>
                                            @if ($artist->title)
                                                <p class="truncate text-xs text-ink-500">{{ $artist->title }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="max-w-[22rem] text-xs text-ink-600">
                                    {{ $artist->bio ? \Illuminate\Support\Str::limit($artist->bio, 120) : '—' }}
                                </td>
                                <td class="text-right font-semibold text-ink-900">{{ $artist->events_count }}</td>
                                <td class="text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="{{ route('organizer.artists.edit', $artist) }}" class="btn-ghost btn-sm" title="Modifier">
                                            @icon('pencil', 'w-4 h-4')
                                        </a>
                                        <form method="POST" action="{{ route('organizer.artists.destroy', $artist) }}"
                                              onsubmit="return confirm('Supprimer cet artiste ? Ses événements restent en ligne.');">
                                            @csrf @method('DELETE')
                                            <button class="btn-ghost btn-sm text-rose-600" title="Supprimer">
                                                @icon('trash', 'w-4 h-4')
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-3">{{ $artists->links() }}</div>
        </div>
    @endif
@endsection
