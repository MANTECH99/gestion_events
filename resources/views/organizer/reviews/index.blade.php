@extends('layouts.organizer')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-extrabold tracking-tight text-ink-950">Avis</h1>
        <p class="mt-1 text-sm text-ink-600">Avis déposés par les visiteurs sur vos événements — modération uniquement.</p>
    </div>

    <form method="GET" class="card mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <select name="event_id" class="input">
            <option value="">Tous les événements</option>
            @foreach ($events as $e)
                <option value="{{ $e->id }}" @selected((int) ($filters['event_id'] ?? 0) === $e->id)>{{ $e->title }}</option>
            @endforeach
        </select>

        <select name="rating" class="input">
            <option value="">Toutes les notes</option>
            @for ($n = 5; $n >= 1; $n--)
                <option value="{{ $n }}" @selected((int) ($filters['rating'] ?? 0) === $n)>{{ $n }} étoile(s)</option>
            @endfor
        </select>

        <div class="flex flex-wrap items-center gap-2">
            <button class="btn-primary btn-sm">@icon('filter', 'w-4 h-4') Filtrer</button>
            @if (array_filter($filters))
                <a href="{{ route('organizer.reviews.index') }}" class="btn-ghost btn-sm">Réinitialiser</a>
            @endif
        </div>
    </form>

    @if ($reviews->isEmpty())
        @include('partials.empty', [
            'icon' => 'star',
            'title' => 'Aucun avis',
            'message' => 'Les avis déposés sur vos événements apparaîtront ici.',
        ])
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Événement</th>
                            <th>Auteur</th>
                            <th>Note</th>
                            <th>Commentaire</th>
                            <th>Date</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reviews as $review)
                            <tr>
                                <td>
                                    <p class="max-w-[14rem] truncate text-sm text-ink-800">{{ $review->event?->title ?? '—' }}</p>
                                </td>
                                <td>
                                    <p class="font-medium text-ink-900">{{ $review->author_name }}</p>
                                    @if ($review->user)
                                        <p class="text-xs text-ink-500">{{ $review->user->email }}</p>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-amber-100 text-amber-700">{{ $review->rating }}/5</span>
                                </td>
                                <td class="max-w-[24rem] text-xs text-ink-600">
                                    {{ $review->comment ? \Illuminate\Support\Str::limit($review->comment, 140) : '—' }}
                                </td>
                                <td class="whitespace-nowrap text-xs text-ink-600">
                                    {{ $review->created_at->translatedFormat('d/m/Y H:i') }}
                                </td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('organizer.reviews.destroy', $review) }}"
                                          onsubmit="return confirm('Supprimer définitivement cet avis ?');">
                                        @csrf @method('DELETE')
                                        <button class="btn-ghost btn-sm text-rose-600" title="Supprimer l'avis">
                                            @icon('trash', 'w-4 h-4')
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-ink-100 px-5 py-3">{{ $reviews->links() }}</div>
        </div>
    @endif
@endsection
