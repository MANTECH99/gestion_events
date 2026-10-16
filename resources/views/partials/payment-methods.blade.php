<div class="flex flex-wrap items-center gap-x-4 gap-y-2 lg:gap-x-6">
    <span class="flex items-center gap-2 text-sm text-white/60">
        <svg viewBox="0 0 24 24" class="h-6 w-6 shrink-0" role="img" aria-label="Orange Money">
            <rect width="24" height="24" fill="#FF7900"/>
            <text x="12" y="20" text-anchor="middle" font-size="6.8" font-weight="500" fill="#fff"
                  font-family="Helvetica, Arial, sans-serif">orange</text>
        </svg>
        {{ ($withLabels ?? false) ? 'Orange Money' : '' }}
    </span>
    <span class="flex items-center gap-2 text-sm text-white/60">
        <img src="{{ asset('images/logos/wave.png') }}" alt="Wave" class="h-6 w-6 shrink-0 rounded-[6px]">
        {{ ($withLabels ?? false) ? 'Wave' : '' }}
    </span>
    <span class="flex items-center gap-2 text-sm text-white/60">
        <span class="flex h-6 shrink-0 items-center gap-1.5 rounded-[5px] bg-white px-1.5" role="img" aria-label="Cartes Visa et Mastercard">
            <svg viewBox="0 8.1 24 7.7" class="h-3 w-auto" aria-hidden="true">
                <path fill="#1A1F71" d="M9.112 8.262L5.97 15.758H3.92L2.374 9.775c-.094-.368-.175-.503-.461-.658C1.447 8.864.677 8.627 0 8.479l.046-.217h3.3a.904.904 0 01.894.764l.817 4.338 2.018-5.102zm8.033 5.049c.008-1.979-2.736-2.088-2.717-2.972.006-.269.262-.555.822-.628a3.66 3.66 0 011.913.336l.34-1.59a5.207 5.207 0 00-1.814-.333c-1.917 0-3.266 1.02-3.278 2.479-.012 1.079.963 1.68 1.698 2.04.756.367 1.01.603 1.006.931-.005.504-.602.725-1.16.734-.975.015-1.54-.263-1.992-.473l-.351 1.642c.453.208 1.289.39 2.156.398 2.037 0 3.37-1.006 3.377-2.564m5.061 2.447H24l-1.565-7.496h-1.656a.883.883 0 00-.826.55l-2.909 6.946h2.036l.405-1.12h2.488zm-2.163-2.656l1.02-2.815.588 2.815zm-8.16-4.84l-1.603 7.496H8.34l1.605-7.496z"/>
            </svg>
            <svg viewBox="0 0 32 20" class="h-3.5 w-auto" aria-hidden="true">
                <circle cx="12" cy="10" r="8" fill="#EB001B"/>
                <circle cx="20" cy="10" r="8" fill="#F79E1B"/>
                <path d="M16 3.072A8 8 0 0 1 16 16.928A8 8 0 0 1 16 3.072Z" fill="#FF5F00"/>
            </svg>
        </span>
        {{ ($withLabels ?? false) ? 'Visa' : '' }}
    </span>
</div>
