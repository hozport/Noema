@extends('site.layout')

@section('title', __('site.nav.svg_viewer').' — Noema')

@push('head')
    <script>
        window.svgViewerI18n = {
            notSvg: @json(__('site.pages.svg_viewer_error_not_svg')),
            parseFail: @json(__('site.pages.svg_viewer_error_parse')),
        };
    </script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/svg-viewer.js'])
    @endif
@endpush

@section('content')
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 py-10 sm:py-12">
        <h1 class="font-display text-2xl sm:text-3xl font-semibold text-base-content">{{ __('site.nav.svg_viewer') }}</h1>
        <p class="mt-2 text-sm text-base-content/65 max-w-2xl">{{ __('site.pages.svg_viewer_lead') }}</p>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6 min-h-[min(70vh,720px)]">
            <div class="flex flex-col min-h-0">
                <label for="svg-viewer-input" class="text-xs font-medium text-base-content/55 uppercase tracking-wide mb-2">{{ __('site.pages.svg_viewer_code_label') }}</label>
                <textarea
                    id="svg-viewer-input"
                    class="textarea textarea-bordered w-full flex-1 min-h-[280px] lg:min-h-0 rounded-none bg-base-200 border-base-300 font-mono text-sm leading-relaxed resize-y"
                    spellcheck="false"
                    placeholder="{{ __('site.pages.svg_viewer_placeholder') }}"
                ></textarea>
            </div>
            <div class="flex flex-col min-h-0">
                <span class="text-xs font-medium text-base-content/55 uppercase tracking-wide mb-2">{{ __('site.pages.svg_viewer_preview_label') }}</span>
                <div
                    id="svg-viewer-preview"
                    class="flex-1 min-h-[280px] lg:min-h-0 bg-base-200/80 border border-base-300 rounded-none overflow-auto flex items-center justify-center p-4 md:p-6"
                    role="region"
                    aria-label="{{ __('site.pages.svg_viewer_preview_label') }}"
                ></div>
            </div>
        </div>
        <p id="svg-viewer-error" class="mt-3 text-sm text-error hidden" role="alert"></p>
    </div>

    <style>
        #svg-viewer-preview svg {
            display: block;
            max-width: 100%;
            max-height: min(65vh, 600px);
            height: auto;
        }
    </style>
@endsection
