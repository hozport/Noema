@php
    $logoRouteName = auth()->check() ? 'worlds.index' : 'site.home';
@endphp
<footer class="mt-auto border-t border-base-300 bg-base-100 shrink-0">
    <div class="max-w-[1344px] mx-auto px-4 sm:px-6 py-10">
        <div class="grid grid-cols-1 md:grid-cols-[auto_1fr_auto] gap-8 md:gap-8 items-start">
            <a href="{{ route($logoRouteName) }}" class="font-display text-left shrink-0 justify-self-start">
                <span class="text-base-content/50 text-xs tracking-widest uppercase block">GENEFIS MEDIA's</span>
                <span class="text-xl font-semibold text-base-content tracking-wide block leading-tight">NOEMA</span>
            </a>
            <div class="footer-nav-columns grid grid-cols-1 sm:grid-cols-2 gap-8 sm:gap-10 lg:gap-14 justify-items-start w-full max-w-xl">
                <nav class="w-full" aria-labelledby="footer-column-about">
                    <h2 id="footer-column-about" class="text-xs font-semibold uppercase tracking-wide text-base-content/55 mb-3">
                        {{ __('site.footer.column_about') }}
                    </h2>
                    <ul class="flex flex-col gap-2 text-sm text-base-content/80">
                        <li><a href="{{ route('site.about') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.about') }}</a></li>
                        <li><a href="{{ route('site.documentation') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.documentation') }}</a></li>
                        <li><a href="{{ route('site.roadmap') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.roadmap') }}</a></li>
                    </ul>
                </nav>
                <nav class="w-full" aria-labelledby="footer-column-tools">
                    <h2 id="footer-column-tools" class="text-xs font-semibold uppercase tracking-wide text-base-content/55 mb-3">
                        {{ __('site.footer.column_tools') }}
                    </h2>
                    <ul class="flex flex-col gap-2 text-sm text-base-content/80">
                        <li><a href="{{ route('site.svg-viewer') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.svg_viewer') }}</a></li>
                        <li><a href="{{ route('site.color-tool') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.color_tool') }}</a></li>
                    </ul>
                </nav>
            </div>
            <p class="text-sm text-base-content/55 md:text-right whitespace-nowrap justify-self-start md:justify-self-end shrink-0">
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
