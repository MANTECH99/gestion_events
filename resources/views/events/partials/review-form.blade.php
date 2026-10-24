@if (session('review_success'))
    <div class="mt-4 flex items-start gap-2.5 rounded-xl border border-emerald-400/40 bg-emerald-500/10 px-4 py-3">
        <span class="mt-0.5 shrink-0 text-emerald-400">@icon('check', 'w-4 h-4')</span>
        <p class="text-sm font-semibold text-emerald-300">{{ session('review_success') }}</p>
    </div>
@endif

<form method="POST" action="{{ route('events.reviews.store', $event) }}"
      class="mt-4 rounded-xl border border-white/10 bg-white/[.03] p-4 sm:p-5">
    @csrf
    <p class="text-sm font-bold text-white">Donnez votre avis</p>

    <fieldset class="mt-3">
        <legend class="text-xs font-semibold uppercase tracking-wide text-white/50">
            Votre note <span class="text-orange-400">*</span>
        </legend>
        <div class="mt-2 flex items-center gap-1.5">
            @for ($i = 1; $i <= 5; $i++)
                <input type="radio" id="{{ $reviewId }}-star-{{ $i }}" name="rating" value="{{ $i }}"
                       class="peer sr-only" @checked((int) old('rating') === $i)>
                <label for="{{ $reviewId }}-star-{{ $i }}"
                       class="cursor-pointer rounded p-0.5 text-white/30 transition hover:text-amber-300 peer-checked:text-amber-400 peer-checked:[&_svg]:fill-current peer-focus-visible:ring-2 peer-focus-visible:ring-amber-400"
                       aria-label="{{ $i }} sur 5">
                    @icon('star', 'w-7 h-7')
                </label>
            @endfor
        </div>
        @error('rating')<p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>@enderror
    </fieldset>

    <div class="mt-4">
        <label for="{{ $reviewId }}-author" class="text-xs font-semibold uppercase tracking-wide text-white/50">
            Votre nom <span class="text-orange-400">*</span>
        </label>
        <input type="text" id="{{ $reviewId }}-author" name="author_name" maxlength="60"
               value="{{ old('author_name', auth()->user()?->full_name ?? '') }}"
               placeholder="ex : Aminata Fall"
               @if (! auth()->check()) required @endif
               class="mt-2 w-full rounded-lg border border-white/15 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder:text-white/35 focus:border-orange-400 focus:ring-1 focus:ring-orange-400 @error('author_name') border-rose-500 @enderror">
        <p class="mt-1.5 text-xs text-white/45">
            @if (auth()->check())
                Connecté en tant que <span class="font-semibold text-white/70">{{ auth()->user()->full_name }}</span> — votre nom est utilisé automatiquement.
            @else
                Pas de compte ? Indiquez simplement votre nom.
            @endif
        </p>
        @error('author_name')<p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>@enderror
    </div>

    <div class="mt-4">
        <label for="{{ $reviewId }}-comment" class="text-xs font-semibold uppercase tracking-wide text-white/50">
            Votre commentaire <span class="font-normal normal-case tracking-normal text-white/40">(optionnel)</span>
        </label>
        <textarea id="{{ $reviewId }}-comment" name="comment" rows="3" maxlength="2000"
                  placeholder="Partagez votre expérience…"
                  class="mt-2 w-full resize-y rounded-lg border border-white/15 bg-white/5 px-3.5 py-2.5 text-sm text-white placeholder:text-white/35 focus:border-orange-400 focus:ring-1 focus:ring-orange-400 @error('comment') border-rose-500 @enderror">{{ old('comment') }}</textarea>
        @error('comment')<p class="mt-1.5 text-xs text-rose-400">{{ $message }}</p>@enderror
    </div>

    <button type="submit"
            class="btn-hero-primary mt-4 w-full justify-center whitespace-nowrap px-4 py-3 text-sm font-bold">
        @icon('check', 'w-4 h-4')
        Publier mon avis
    </button>
</form>
