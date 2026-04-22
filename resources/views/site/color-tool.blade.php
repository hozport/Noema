@extends('site.layout')

@section('title', __('site.nav.color_tool').' — Noema')

@push('head')
    <script>
        window.colorToolI18n = {
            invalid: @json(__('site.pages.color_tool_error_invalid')),
            copyHint: @json(__('site.pages.color_tool_copy_hint')),
            copied: @json(__('site.pages.color_tool_copied')),
        };
    </script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/color-tool.js'])
    @endif
@endpush

@section('content')
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 py-10 sm:py-12">
        <h1 class="font-display text-2xl sm:text-3xl font-semibold text-base-content">{{ __('site.nav.color_tool') }}</h1>
        <p class="mt-2 text-sm text-base-content/65 max-w-2xl">{{ __('site.pages.color_tool_lead') }}</p>

        <div class="mt-8 space-y-10">
            <section aria-labelledby="color-tool-section-parse">
                <h2 id="color-tool-section-parse" class="text-xs font-semibold uppercase tracking-wide text-base-content/55 mb-4">
                    {{ __('site.pages.color_tool_section_parse') }}
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6">
                    <div class="flex flex-col min-h-0">
                        <label for="color-tool-input" class="text-xs font-medium text-base-content/55 uppercase tracking-wide mb-2">
                            {{ __('site.pages.color_tool_input_label') }}
                        </label>
                        <input
                            type="text"
                            id="color-tool-input"
                            class="input input-bordered w-full rounded-none bg-base-200 border-base-300 font-mono text-sm"
                            autocomplete="off"
                            spellcheck="false"
                            value="#4a7dbc"
                            placeholder="{{ __('site.pages.color_tool_placeholder') }}"
                        />
                    </div>
                    <div class="flex flex-col min-h-0">
                        <span class="text-xs font-medium text-base-content/55 uppercase tracking-wide mb-2">
                            {{ __('site.pages.color_tool_preview_label') }}
                        </span>
                        <div
                            id="color-tool-preview"
                            class="min-h-[120px] lg:min-h-[140px] rounded-none border border-base-300 bg-[repeating-linear-gradient(45deg,oklch(var(--bc)/0.08)_0px,oklch(var(--bc)/0.08)_8px,transparent_8px,transparent_16px)] bg-base-200/30"
                            role="img"
                            aria-label="{{ __('site.pages.color_tool_preview_label') }}"
                        ></div>
                    </div>
                </div>
            </section>

            <section aria-labelledby="color-tool-section-pick">
                <h2 id="color-tool-section-pick" class="text-xs font-semibold uppercase tracking-wide text-base-content/55 mb-4">
                    {{ __('site.pages.color_tool_section_pick') }}
                </h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6 lg:grid-flow-dense">
                    <div class="flex flex-col min-h-0 lg:col-start-1 lg:row-start-1">
                        <span class="text-xs font-medium text-base-content/55 uppercase tracking-wide mb-2">
                            {{ __('site.pages.color_tool_codes_label') }}
                        </span>
                        <div
                            id="color-tool-codes"
                            class="min-h-[120px] p-4 rounded-none border border-base-300 bg-base-200/50"
                        ></div>
                    </div>
                    <div class="flex flex-col items-start gap-3 lg:col-start-2 lg:row-start-1">
                        <label for="color-tool-picker" class="text-xs font-medium text-base-content/55 uppercase tracking-wide">
                            {{ __('site.pages.color_tool_picker_label') }}
                        </label>
                        <input
                            type="color"
                            id="color-tool-picker"
                            class="h-28 w-full max-w-[min(100%,280px)] cursor-pointer rounded-none border border-base-300 bg-base-100 p-1"
                            value="#4a7dbc"
                            title="{{ __('site.pages.color_tool_picker_label') }}"
                        />
                    </div>
                </div>
            </section>
        </div>

        <p id="color-tool-error" class="mt-4 text-sm text-error hidden" role="alert"></p>
    </div>
@endsection
