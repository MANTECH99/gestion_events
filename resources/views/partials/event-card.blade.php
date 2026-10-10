@props(['event'])

@php
    $image = $event->image_url;
    $sold = $event->tickets_sold;
    $total = max(1, (int) $event->total_tickets);
    $percent = min(100, (int) round($sold / $total * 100));
    $soldOut = $sold >= $total;
@endphp

<article class="group card flex flex-col overflow-hidden transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-ink-900/5">
    <a href="{{ route('events.show', $event) }}" class="relative block aspect-[16/10] overflow-hidden bg-ink-200">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $event->title }}" loading="lazy"
                 class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <div class="grid h-full w-full place-items-center gradient-brand text-6xl">{{ ticket_type_icon($event->event_type) }}</div>
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-ink-950/85 via-ink-950/10 to-transparent"></div>

        <span class="absolute left-3 top-3 badge bg-white/95 text-ink-800 backdrop-blur">
            {{ ticket_type_icon($event->event_type) }} {{ $event->type_label }}
        </span>

        @if ($soldOut)
            <span class="absolute right-3 top-3 badge bg-rose-600 text-white">Complet</span>
        @elseif ($sold > 0)
            <span class="absolute right-3 top-3 badge bg-accent-500 text-white">-{{ 100 - $percent }}% dispo</span>
        @endif

        <div class="absolute inset-x-0 bottom-0 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-white/80">{{ event_date_fr($event->event_date, 'd M Y') }}</p>
            <h3 class="mt-0.5 line-clamp-2 text-lg font-bold leading-tight text-white">{{ $event->title }}</h3>
        </div>
    </a>

    <div class="flex flex-1 flex-col p-4">
        <ul class="space-y-2 text-sm text-ink-600">
            <li class="flex items-start gap-2">
                <span class="mt-0.5 text-ink-400">@icon('calendar', 'w-4 h-4')</span>
                <span>{{ event_date_fr($event->event_date, 'd/m/Y à H:i') }}</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 text-ink-400">@icon('map-pin', 'w-4 h-4')</span>
                <span class="line-clamp-1">{{ $event->location }}</span>
            </li>
            <li class="flex items-start gap-2">
                <span class="mt-0.5 text-ink-400">@icon('user', 'w-4 h-4')</span>
                <span class="line-clamp-1">Organisé par {{ $event->organizer_display_name }}</span>
            </li>
        </ul>

        @if ($sold > 0)
            <div class="mt-3">
                <div class="h-1.5 overflow-hidden rounded-full bg-ink-100">
                    <div class="h-full rounded-full {{ $soldOut ? 'bg-rose-500' : 'gradient-brand' }}" style="width: {{ $percent }}%"></div>
                </div>
                <p class="mt-1 text-xs text-ink-500">{{ $sold }} / {{ $total }} billets vendus</p>
            </div>
        @endif

        <div class="mt-auto flex items-end justify-between gap-3 pt-4">
            <div>
                <p class="text-[11px] uppercase tracking-wide text-ink-400">À partir de</p>
                <p class="text-lg font-extrabold text-ink-950">
                    {{ $event->min_price === null ? '—' : money($event->min_price) }}
                </p>
            </div>
            <a href="{{ route('events.show', $event) }}" class="btn-primary btn-sm">
                Voir @icon('arrow-right', 'w-4 h-4')
            </a>
        </div>
    </div>
</article>