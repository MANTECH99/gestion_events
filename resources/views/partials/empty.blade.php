@php
    /** @var string $icon */
    /** @var string $title */
    /** @var string|null $message */
    /** @var string|null $action */
    /** @var string|null $actionLabel */
    $icon = $icon ?? 'inbox';
    $title = $title ?? 'Aucune donnée';
    $message = $message ?? null;
    $action = $action ?? null;
    $actionLabel = $actionLabel ?? null;
@endphp

<div class="flex flex-col items-center justify-center px-6 py-16 text-center">
    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-ink-100 text-ink-400">
        @icon($icon, 'w-7 h-7')
    </span>

    <p class="mt-4 text-base font-semibold text-ink-900">{{ $title }}</p>

    @if (! empty($message))
        <p class="mt-1.5 max-w-sm text-sm text-ink-500">{{ $message }}</p>
    @endif

    @if ($action && $actionLabel)
        <a href="{{ $action }}" class="btn-primary btn-sm mt-5">{{ $actionLabel }}</a>
    @endif
</div>