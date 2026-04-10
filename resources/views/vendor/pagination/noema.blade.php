@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        {{-- Мобильная версия: только назад / вперёд --}}
        <div class="flex gap-2 items-center justify-center sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-base-content/40 bg-base-200 border border-base-300 cursor-not-allowed leading-5 rounded-none">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-medium text-base-content bg-base-200 border border-base-300 leading-5 rounded-none hover:bg-base-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 transition-colors">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-medium text-base-content bg-base-200 border border-base-300 leading-5 rounded-none hover:bg-base-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 transition-colors">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-base-content/40 bg-base-200 border border-base-300 cursor-not-allowed leading-5 rounded-none">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        {{-- Десктоп: только номера страниц (без текста «Showing …») --}}
        <div class="hidden sm:flex sm:flex-wrap sm:items-center sm:justify-center sm:gap-0">
            <span class="inline-flex rtl:flex-row-reverse">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                        <span class="inline-flex items-center px-2 py-2 min-h-9 text-sm font-medium text-base-content/35 bg-base-200 border border-base-300 cursor-not-allowed rounded-none leading-none" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-2 py-2 min-h-9 text-sm font-medium text-base-content bg-base-200 border border-base-300 rounded-none leading-none hover:bg-base-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:z-10 transition-colors" aria-label="{{ __('pagination.previous') }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true">
                            <span class="inline-flex items-center px-3 py-2 min-h-9 -ml-px text-sm font-medium text-base-content/60 bg-base-200 border border-base-300 cursor-default leading-none">{{ $element }}</span>
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page">
                                    <span class="inline-flex items-center px-3 py-2 min-h-9 -ml-px text-sm font-medium text-base-content bg-base-300 border border-base-300 cursor-default leading-none">{{ $page }}</span>
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center px-3 py-2 min-h-9 -ml-px text-sm font-medium text-base-content bg-base-200 border border-base-300 leading-none hover:bg-base-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:z-10 transition-colors" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-2 py-2 min-h-9 -ml-px text-sm font-medium text-base-content bg-base-200 border border-base-300 rounded-none leading-none hover:bg-base-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:z-10 transition-colors" aria-label="{{ __('pagination.next') }}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                        <span class="inline-flex items-center px-2 py-2 min-h-9 -ml-px text-sm font-medium text-base-content/35 bg-base-200 border border-base-300 cursor-not-allowed rounded-none leading-none" aria-hidden="true">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
