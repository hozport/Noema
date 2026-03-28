@php
    $logoRouteName = auth()->check() ? 'worlds.index' : 'site.home';
@endphp
<footer class="mt-auto border-t border-base-300 bg-base-100 shrink-0">
    <div class="max-w-[1344px] mx-auto px-4 sm:px-6 py-10">
        <div class="grid grid-cols-1 md:grid-cols-[auto_1fr_auto] gap-8 md:gap-6 items-start md:items-center">
            <a href="{{ route($logoRouteName) }}" class="font-display text-left shrink-0 justify-self-start">
                <span class="text-base-content/50 text-xs tracking-widest uppercase block">GENEFIS MEDIA's</span>
                <span class="text-xl font-semibold text-base-content tracking-wide block leading-tight">NOEMA</span>
            </a>
            <nav class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-base-content/80 justify-center md:justify-center" aria-label="{{ __('site.footer.aria_nav') }}">
                <a href="{{ route('site.about') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.about') }}</a>
                <a href="{{ route('site.documentation') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.documentation') }}</a>
                <a href="{{ route('site.roadmap') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.roadmap') }}</a>
            </nav>
            <p class="text-sm text-base-content/55 md:text-right whitespace-nowrap justify-self-start md:justify-self-end">
                &copy; GENEFIS MEDIA, {{ date('Y') }}
            </p>
        </div>
        <div class="mt-8 pt-6 border-t border-base-300/80 flex flex-wrap gap-x-6 gap-y-2 justify-center md:justify-start text-xs text-base-content/45">
            <a href="{{ route('site.privacy') }}" class="hover:text-base-content/70 transition-colors">{{ __('site.legal_links.privacy') }}</a>
            <a href="{{ route('site.consent') }}" class="hover:text-base-content/70 transition-colors">{{ __('site.legal_links.consent') }}</a>
            <a href="{{ route('site.legal') }}" class="hover:text-base-content/70 transition-colors">{{ __('site.legal_links.legal') }}</a>
        </div>
    </div>
</footer>
