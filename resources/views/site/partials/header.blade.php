@php
    $logoRouteName = auth()->check() ? 'worlds.index' : 'site.home';
@endphp
<header class="sticky top-0 z-50 border-b border-base-300 bg-base-100/90 backdrop-blur-md">
    <div class="max-w-[1344px] mx-auto px-4 sm:px-6 flex flex-wrap items-center justify-between gap-4 py-4">
        <a href="{{ route($logoRouteName) }}" class="font-display text-left shrink-0">
            <span class="text-base-content/50 text-xs sm:text-sm tracking-widest uppercase block">GENEFIS MEDIA's</span>
            <span class="text-xl sm:text-2xl font-semibold text-base-content tracking-wide block leading-tight">NOEMA</span>
        </a>
        <nav class="flex flex-wrap items-center gap-6 text-sm text-base-content/80" aria-label="{{ __('site.header.aria_nav') }}">
            <a href="{{ route('site.about') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.about') }}</a>
            <a href="{{ route('site.documentation') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.documentation') }}</a>
            <a href="{{ route('site.roadmap') }}" class="hover:text-base-content transition-colors">{{ __('site.nav.roadmap') }}</a>
        </nav>
        <div class="flex items-center gap-2 ml-auto sm:ml-0">
            <label for="site-locale" class="sr-only">{{ __('site.header.locale_label') }}</label>
            <select id="site-locale" name="lang"
                class="select select-bordered select-sm rounded-none bg-base-200 border-base-300 text-base-content min-w-[7.5rem]"
                onchange="if (this.value) { window.location.href = window.location.pathname + '?lang=' + encodeURIComponent(this.value); }">
                @foreach (\App\Support\SiteLocales::SUPPORTED as $code)
                    <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ __('site.locale_names.'.$code) }}</option>
                @endforeach
            </select>
            @auth
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm btn-square text-base-content/70 hover:text-base-content hover:bg-base-200" title="{{ __('site.header.logout') }}" aria-label="{{ __('site.header.logout') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                    </button>
                </form>
            @endauth
        </div>
    </div>
</header>
