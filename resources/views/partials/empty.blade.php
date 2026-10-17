@php
    /** @var string $icon */
    /** @var string $title */
    /** @var string|null $message */
    /** @var string|null $action */
    /** @var string|null $actionLabel */
    /** @var bool $dark */
    $icon = $icon ?? 'inbox';
    $title = $title ?? 'Aucune donnée';
    $message = $message ?? null;
    $action = $action ?? null;
    $actionLabel = $actionLabel ?? null;
    $dark = $dark ?? false;
@endphp

<div class="flex flex-col items-center justify-center px-6 py-16 text-center">
    <span class="grid h-14 w-14 place-items-center rounded-2xl {{ $dark ? 'bg-white/10 text-white/40' : 'bg-ink-100 text-ink-400' }}">
        @icon($icon, 'w-7 h-7')
    </span>

    <p class="mt-4 text-base font-semibold {{ $dark ? 'text-white' : 'text-ink-900' }}">{{ $title }}</p>

    @if (! empty($message))
        <p class="mt-1.5 max-w-sm text-sm {{ $dark ? 'text-white/55' : 'text-ink-500' }}">{{ $message }}</p>
    @endif

    @if ($action && $actionLabel)
        <a href="{{ $action }}" class="{{ $dark ? 'btn-hero-primary' : 'btn-primary' }} btn-sm mt-5">{{ $actionLabel }}</a>
    @endif
</div>
