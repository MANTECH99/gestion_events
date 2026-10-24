@php
    /** @var string $label */
    /** @var string|int $value */
    /** @var string $icon */
    /** @var string|null $tone brand|emerald|orange|rose|violet|sky */
    /** @var string|null $hint */
    /** @var string|null $breakdown */
    /** @var string|null $href */
    /** @var bool $dark */
    $label = $label ?? '';
    $value = $value ?? '—';
    $icon = $icon ?? 'stat';
    $hint = $hint ?? null;
    $breakdown = $breakdown ?? null;
    $href = $href ?? null;
    $dark = $dark ?? false;
@endphp

@php
    $tones = $dark ? [
        'brand' => 'bg-orange-500/20 text-orange-300',
        'emerald' => 'bg-emerald-500/20 text-emerald-300',
        'orange' => 'bg-orange-500/20 text-orange-300',
        'rose' => 'bg-rose-500/20 text-rose-300',
        'violet' => 'bg-violet-500/20 text-violet-300',
        'sky' => 'bg-sky-500/20 text-sky-300',
    ] : [
        'brand' => 'bg-brand-50 text-brand-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'orange' => 'bg-orange-50 text-orange-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'sky' => 'bg-sky-50 text-sky-600',
    ];

    $tone = $tones[$tone ?? 'brand'] ?? $tones['brand'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    class="{{ $dark ? 'rounded-2xl border border-white/10 bg-white/5' : 'card' }} group p-5 {{ $href ? 'transition hover:-translate-y-0.5 hover:shadow-lg' : '' }}">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider {{ $dark ? 'text-white/50' : 'text-ink-500' }}">{{ $label }}</p>
            <p class="mt-2 text-2xl font-extrabold tracking-tight {{ $dark ? 'text-white' : 'text-ink-950' }} sm:text-3xl">{{ $value }}</p>
            @if (! empty($hint) || ! empty($breakdown))
                <p class="mt-1 text-xs leading-snug {{ $dark ? 'text-white/50' : 'text-ink-500' }}">
                    @if (! empty($hint)){{ $hint }}@endif
                    @if (! empty($hint) && ! empty($breakdown))<span class="{{ $dark ? 'text-white/40' : 'text-ink-400' }}">&nbsp;·&nbsp;</span>@endif
                    @if (! empty($breakdown))<span class="{{ $dark ? 'text-white/40' : 'text-ink-400' }}">{{ $breakdown }}</span>@endif
                </p>
            @endif
        </div>

        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $tone }}">
            @icon($icon, 'w-5 h-5')
        </span>
    </div>
</{{ $tag }}>
