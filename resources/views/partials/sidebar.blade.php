@php
    /**
     * Colonne de navigation des interfaces de gestion.
     * Le layout parent fournit l'état du tiroir mobile (x-data) via la section « sidebar »
     * et le bouton d'ouverture ; le tiroir occupe l'écran sous lg.
     *
     * @var string      $title    Titre du secteur (ex. « Espace organisateur »)
     * @var string|null $subtitle Repere sous le titre
     * @var string      $icon     Icone du titre
     * @var array       $links    [['label' =>, 'route' =>, 'icon' =>, 'pattern' =>], ...]
     * @var array|null  $sections [['title' =>, 'items' => [['label' =>, 'route' =>, 'icon' =>, 'pattern' =>], ...]], ...]
     * @var array|null  $cta      ['label' =>, 'route' =>]
     */
    $links = $links ?? [];
    $sections = $sections ?? null;
    $groups = $sections ?? [['items' => $links]];
    $cta = $cta ?? null;
@endphp

{{-- Colonne pleine hauteur : sous lg le tiroir occupe l'écran, au-dessus elle borde le header. --}}
<div x-cloak x-show="open" x-transition.opacity
     class="fixed inset-0 z-[55] bg-ink-950/50 no-print lg:hidden" @click="open = false"></div>

<aside id="sidebar-gestion" no-print
       class="fixed bottom-0 left-0 top-0 z-[60] w-full overflow-y-auto bg-white transition-transform duration-200
              lg:w-64 lg:translate-x-0 lg:border-r lg:border-ink-200"
       :class="open ? 'max-lg:translate-x-0' : 'max-lg:-translate-x-full'">
    {{-- Bloc titre : même hauteur et même rythme vertical que la barre de header. --}}
    <div class="sticky top-0 z-10 flex h-16 items-center gap-3 border-b border-ink-100 bg-white px-4 lg:px-3">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl gradient-brand text-white shadow shadow-brand-600/20">
            @icon($icon ?? 'chart-bar', 'w-5 h-5')
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-extrabold tracking-tight text-ink-950">{{ $title }}</p>
            @if (! empty($subtitle))
                <p class="text-xs leading-tight text-ink-500">{{ $subtitle }}</p>
            @endif
        </div>
        <button type="button" @click="open = false" aria-label="Fermer le menu"
                class="ml-auto grid h-8 w-8 shrink-0 place-items-center rounded-lg text-ink-500 hover:bg-ink-100 lg:hidden">
            @icon('close', 'w-4 h-4')
        </button>
    </div>

    <div class="px-4 py-5 lg:px-3">
        <nav class="space-y-1" aria-label="{{ $title }}">
            @foreach ($groups as $group)
                <div class="space-y-1">
                    @if (! empty($group['title']))
                        <p class="px-3 pb-1 pt-4 text-[11px] font-bold uppercase tracking-wider text-ink-400 first:pt-0">
                            {{ $group['title'] }}
                        </p>
                    @endif

                    @foreach ($group['items'] as $link)
                        @php
                            $pattern = $link['pattern'] ?? $link['route'];
                            $isActive = request()->routeIs($pattern);
                        @endphp
                        <a href="{{ route($link['route']) }}" @if ($isActive) aria-current="page" @endif
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition
                                  {{ $isActive ? 'bg-ink-950 text-white shadow' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}">
                            @icon($link['icon'], 'w-4 h-4 '.($isActive ? '' : 'text-ink-400'))
                            <span class="truncate">{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        @if ($cta)
            <a href="{{ route($cta['route']) }}" class="btn-primary btn-sm mt-5 w-full justify-center">
                @icon('plus', 'w-4 h-4') {{ $cta['label'] }}
            </a>
        @endif

        <a href="{{ route('home') }}"
           class="mt-5 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink-500 transition hover:bg-ink-100 hover:text-ink-900">
            @icon('arrow-left', 'w-4 h-4') Voir le site public
        </a>
    </div>
</aside>