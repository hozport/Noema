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
            <div class="dropdown dropdown-end dropdown-bottom">
                <div tabindex="0" role="button" class="inline-flex items-center gap-1 cursor-pointer text-base-content/80 hover:text-base-content transition-colors select-none" aria-haspopup="menu" aria-expanded="false">
                    {{ __('site.nav.tools') }}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-70" aria-hidden="true">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </div>
                {{-- Не использовать class "menu" вместе с dropdown-content: у .menu задан display:flex и ломается скрытие панели в DaisyUI 5 --}}
                <ul tabindex="-1" role="menu" class="dropdown-content bg-base-100 border border-base-300 rounded-none z-[60] w-52 p-2 shadow-lg mt-1 list-none m-0">
                    <li role="none">
                        <a href="{{ route('site.svg-viewer') }}" role="menuitem" class="block px-3 py-2 text-sm rounded-none hover:bg-base-200">{{ __('site.nav.svg_viewer') }}</a>
                    </li>
                </ul>
            </div>
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
                <a href="{{ route('account.profile') }}" class="btn btn-ghost btn-sm btn-square text-base-content/70 hover:text-base-content hover:bg-base-200 shrink-0" title="Профиль" aria-label="Профиль">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </a>
                <a href="{{ route('account.team') }}" class="btn btn-ghost btn-sm btn-square text-base-content/70 hover:text-base-content hover:bg-base-200 shrink-0" title="Команда" aria-label="Команда">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </a>
                <a href="{{ route('account.settings') }}" class="btn btn-ghost btn-sm btn-square text-base-content/70 hover:text-base-content hover:bg-base-200 shrink-0" title="Настройки Noema" aria-label="Настройки Noema">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </a>
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
