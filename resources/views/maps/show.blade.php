<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.flash-toast-critical-css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $map->title }} — Карты — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    {{-- Подписи на карте: три сеттинга (шрифты под будущую глобальную настройку мира). --}}
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600|exo-2:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/maps.js'])
    @endif
    <style>
        /* Холст на всю ширину; высота блока карты выталкивает футер за первый экран */
        .map-page-main {
            width: 100%;
            max-width: none;
            padding-left: 0;
            padding-right: 0;
        }
        .map-title-row {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        #map-stage-mount {
            width: 100%;
            height: 100%;
            min-height: 0;
            background: oklch(var(--b2) / 0.35);
        }
        /* Konva: курсор с внешнего div не всегда доходит до canvas — наследование + JS applyMapCursor */
        #map-stage-mount .konvajs-content,
        #map-stage-mount .konvajs-content canvas {
            cursor: inherit;
        }
        .map-layout {
            display: flex;
            width: 100%;
            flex: 1;
            min-height: calc(100dvh - 10rem);
            border-top: 1px solid oklch(var(--b3));
            border-bottom: 1px solid oklch(var(--b3));
        }
        .map-sidebar-icons {
            width: 4rem;
            flex-shrink: 0;
            border-right: 1px solid oklch(var(--b3));
            background: oklch(var(--b1));
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 0.35rem;
        }
        .map-sidebar-panel {
            flex-shrink: 0;
            width: 0;
            min-width: 0;
            max-width: 0;
            overflow: hidden;
            opacity: 0;
            padding: 0;
            border-right: 0 solid transparent;
            background: oklch(var(--b1));
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            transition: width 0.2s ease, min-width 0.2s ease, max-width 0.2s ease, opacity 0.2s ease, padding 0.2s ease, border-color 0.2s ease;
        }
        .map-sidebar-panel.map-sidebar-panel--open {
            width: 15rem;
            min-width: 15rem;
            max-width: 15rem;
            opacity: 1;
            padding: 1rem;
            overflow-y: auto;
            border-right: 1px solid oklch(var(--b3));
        }
        .map-canvas-wrap {
            flex: 1;
            min-width: 0;
            min-height: 0;
            position: relative;
            overflow: hidden;
        }
        .map-sprite-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.35rem;
        }
        .map-hint-row {
            padding: 0.75rem 1.5rem 1.25rem;
        }
        .map-tool-stub {
            justify-content: flex-start;
            text-align: left;
            opacity: 0.65;
            cursor: not-allowed;
        }
        .map-draw-tools {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }
        .map-draw-tools .map-draw-tool-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 768px) {
            .map-layout {
                flex-direction: column;
                min-height: min(70vh, calc(100dvh - 8rem));
            }
            .map-sidebar-panel.map-sidebar-panel--open {
                width: 100%;
                min-width: 100%;
                max-width: none;
                border-right: none;
                border-bottom: 1px solid oklch(var(--b3));
            }
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col" data-world-setting="{{ $world->setting?->value ?? 'fantasy' }}">
    @include('site.partials.header')

    <main class="map-page-main flex-1 flex flex-col min-h-0">
        <x-noema-page-head class="map-title-row py-4 shrink-0">
            <x-slot name="title">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $map->title }}</h1>
            </x-slot>
            <x-slot name="below">
                <p class="text-base-content/60 max-w-2xl text-sm">{{ $world->name }} — <span class="tabular-nums">{{ $map->width }}×{{ $map->height }} px</span></p>
            </x-slot>
            <x-slot name="center">
                <button type="button" id="map-undo-last-action" class="btn btn-outline btn-sm rounded-none gap-1.5 min-h-9" disabled title="Отменить последнее действие (Ctrl+Z)" aria-label="Отменить последнее действие">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 7v6h6"/>
                        <path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/>
                    </svg>
                    <span>Отменить</span>
                </button>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('worlds.maps.index', $world) }}" class="btn btn-ghost btn-square shrink-0" title="К списку карт" aria-label="К списку карт">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <button type="button" class="btn btn-ghost btn-square shrink-0" title="Настройки карты" aria-label="Настройки карты" onclick="document.getElementById('mapSettingsModal').showModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </button>
                @include('partials.activity-log-button', ['world' => $world, 'map' => $map])
            </x-slot>
        </x-noema-page-head>

        @if (session('success'))
            <p class="text-success mb-2 px-6 shrink-0" role="alert" data-auto-dismiss>{{ session('success') }}</p>
        @endif

        <script type="application/json" id="map-page-meta">@json($mapPageMeta)</script>

        <div class="map-layout">
            <aside class="map-sidebar-icons" aria-label="Инструменты и категории объектов">
                <button type="button" class="btn btn-ghost btn-square rounded-none w-12 h-12 p-0 border border-base-300" data-map-tool="landscape" title="Ландшафт" aria-label="Ландшафт">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 20h18L8 6l-5 14z"/>
                        <path d="M8 6L12 3l4 5 4-2 4 14"/>
                    </svg>
                </button>
                <button type="button" class="btn btn-ghost btn-square rounded-none w-12 h-12 p-0 border border-base-300" data-map-tool="labels" title="Подписи" aria-label="Подписи">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M4 7h16M4 12h10M4 17h14"/>
                    </svg>
                </button>
                <div class="w-8 h-px bg-base-300 my-0.5 shrink-0" role="separator" aria-hidden="true"></div>
                <button type="button" class="btn btn-ghost btn-square rounded-none w-12 h-12 p-0 border border-base-300" data-map-category="settlements" title="Поселения" aria-label="Поселения">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/>
                        <path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/>
                    </svg>
                </button>
                <button type="button" class="btn btn-ghost btn-square rounded-none w-12 h-12 p-0 border border-base-300" data-map-category="mountains" title="Горы" aria-label="Горы">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 20h18L12 4 3 20z"/>
                        <path d="M12 4v16"/>
                    </svg>
                </button>
                <button type="button" class="btn btn-ghost btn-square rounded-none w-12 h-12 p-0 border border-base-300" data-map-category="forests" title="Леса" aria-label="Леса">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="8" cy="14" r="3"/>
                        <circle cx="16" cy="13" r="3.5"/>
                        <path d="M12 21s-2-3-4-5c-1-1-2-1-3 0"/>
                    </svg>
                </button>
            </aside>
            <aside id="map-sidebar-panel" class="map-sidebar-panel" aria-label="Панель инструментов и спрайтов" aria-hidden="true">
                <div id="map-panel-tool-landscape" class="map-tool-panel hidden flex flex-col gap-2">
                    <p class="text-xs font-medium text-base-content/60 uppercase tracking-wide">Ландшафт</p>
                    <ul class="map-draw-tools list-none p-0 m-0">
                        <li>
                            <button type="button" id="map-draw-landscape" class="map-draw-tool-icon btn btn-sm btn-ghost btn-square w-11 h-11 rounded-none border border-base-300/60" title="Рисование ландшафта" aria-label="Рисование ландшафта">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M17 3a2.85 2.85 0 0 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/>
                                </svg>
                            </button>
                        </li>
                        <li>
                            <button type="button" id="map-draw-borders" class="map-draw-tool-icon btn btn-sm btn-ghost btn-square w-11 h-11 rounded-none border border-base-300/60" title="Рисование границ" aria-label="Рисование границ">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 4h16v16H4z" stroke-dasharray="3 3"/>
                                    <path d="M9 9h6v6H9z"/>
                                </svg>
                            </button>
                        </li>
                        <li>
                            <button type="button" id="map-draw-erase" class="map-draw-tool-icon btn btn-sm btn-ghost btn-square w-11 h-11 rounded-none border border-base-300/60" title="Стереть" aria-label="Стереть">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/>
                                    <path d="M22 21H7"/>
                                    <path d="m5 11 9 9"/>
                                </svg>
                            </button>
                        </li>
                        <li>
                            <button type="button" id="map-draw-fill" class="map-draw-tool-icon btn btn-sm btn-ghost btn-square w-11 h-11 rounded-none border border-base-300/60" title="Заливка" aria-label="Заливка">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m19 11-8-8-8.5 8.5a2.12 2.12 0 0 0 0 3l3 3c.84.84 2.2.84 3.04 0L19 11"/>
                                    <path d="M5 21h14"/>
                                </svg>
                            </button>
                        </li>
                    </ul>
                    @php
                        $mapLineStrokeSwatches = [
                            ['key' => 'black', 'title' => 'Чёрный', 'bg' => '#1a1814'],
                            ['key' => 'earth', 'title' => 'Земля', 'bg' => 'var(--noema-earth)'],
                            ['key' => 'grass', 'title' => 'Трава', 'bg' => 'var(--noema-grass)'],
                            ['key' => 'water', 'title' => 'Вода', 'bg' => 'var(--noema-water)'],
                            ['key' => 'deep_sea', 'title' => 'Глубокая вода', 'bg' => 'var(--noema-deep-sea)'],
                            ['key' => 'ice', 'title' => 'Лёд', 'bg' => 'var(--noema-ice)'],
                            ['key' => 'forest', 'title' => 'Лес', 'bg' => 'var(--noema-forest)'],
                            ['key' => 'desert', 'title' => 'Пустыня', 'bg' => 'var(--noema-desert)'],
                            ['key' => 'mountain', 'title' => 'Горы', 'bg' => 'var(--noema-mountain)'],
                            ['key' => 'swamp', 'title' => 'Болота', 'bg' => 'var(--noema-swamp)'],
                        ];
                    @endphp
                    <div id="map-stroke-settings" class="hidden flex flex-col gap-3 mt-2 pt-2 border-t border-base-300/40" aria-label="Толщина и цвет линий">
                        <div class="flex flex-col gap-1.5" id="map-stroke-landscape-group">
                            <span class="text-[11px] text-base-content/55 uppercase tracking-wide">Линии ландшафта</span>
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-base-content/60 shrink-0" for="map-stroke-landscape-width">Толщина</label>
                                <input type="range" id="map-stroke-landscape-width" min="1" max="20" value="2" step="1" class="range range-xs flex-1 min-w-0 [--range-fill:0]" />
                                <span id="map-stroke-landscape-width-val" class="text-xs tabular-nums w-6 text-right text-base-content/80">2</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Цвет линии ландшафта">
                                @foreach ($mapLineStrokeSwatches as $sw)
                                    <button type="button" class="map-landscape-stroke-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-map-landscape-stroke="{{ $sw['key'] }}" style="background-color: {{ $sw['bg'] }}" title="{{ $sw['title'] }}" aria-label="{{ $sw['title'] }}"></button>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex flex-col gap-1.5" id="map-stroke-borders-group">
                            <span class="text-[11px] text-base-content/55 uppercase tracking-wide">Штрихи границ</span>
                            <div class="flex items-center gap-2">
                                <label class="text-xs text-base-content/60 shrink-0" for="map-stroke-borders-width">Толщина</label>
                                <input type="range" id="map-stroke-borders-width" min="1" max="20" value="2" step="1" class="range range-xs flex-1 min-w-0 [--range-fill:0]" />
                                <span id="map-stroke-borders-width-val" class="text-xs tabular-nums w-6 text-right text-base-content/80">2</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Цвет штриха границы">
                                @foreach ($mapLineStrokeSwatches as $sw)
                                    <button type="button" class="map-borders-stroke-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-map-borders-stroke="{{ $sw['key'] }}" style="background-color: {{ $sw['bg'] }}" title="{{ $sw['title'] }}" aria-label="{{ $sw['title'] }}"></button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div id="map-fill-palette" class="hidden flex flex-col gap-4 mt-2" aria-label="Цвет заливки">
                        <div>
                            <p class="text-[11px] font-medium text-base-content/55 uppercase tracking-wide mb-2">Основные</p>
                            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Основные цвета заливки">
                                <button type="button" class="map-fill-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-fill-color="earth" style="background-color: var(--noema-earth)" title="Земля" aria-label="Земля"></button>
                                <button type="button" class="map-fill-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-fill-color="grass" style="background-color: var(--noema-grass)" title="Трава" aria-label="Трава"></button>
                                <button type="button" class="map-fill-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-fill-color="water" style="background-color: var(--noema-water)" title="Вода" aria-label="Вода"></button>
                                <button type="button" class="map-fill-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-fill-color="deep_sea" style="background-color: var(--noema-deep-sea)" title="Глубокая вода" aria-label="Глубокая вода"></button>
                                <button type="button" class="map-fill-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-fill-color="ice" style="background-color: var(--noema-ice)" title="Лёд" aria-label="Лёд"></button>
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] font-medium text-base-content/55 uppercase tracking-wide mb-2">Дополнительные</p>
                            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Дополнительные цвета заливки">
                                <button type="button" class="map-fill-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-fill-color="forest" style="background-color: var(--noema-forest)" title="Лес" aria-label="Лес"></button>
                                <button type="button" class="map-fill-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-fill-color="desert" style="background-color: var(--noema-desert)" title="Пустыня" aria-label="Пустыня"></button>
                                <button type="button" class="map-fill-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-fill-color="mountain" style="background-color: var(--noema-mountain)" title="Горы" aria-label="Горы"></button>
                                <button type="button" class="map-fill-swatch w-8 h-8 rounded-none border border-base-300 shrink-0" data-fill-color="swamp" style="background-color: var(--noema-swamp)" title="Болота" aria-label="Болота"></button>
                            </div>
                        </div>
                        <div id="map-water-edge-wrap" class="hidden flex flex-col gap-1.5 pt-2 border-t border-base-300/40">
                            <label class="flex items-start gap-2 cursor-pointer max-w-full">
                                <input type="checkbox" id="map-water-edge-decorate" class="checkbox checkbox-sm rounded-none mt-0.5 shrink-0 border-base-300" />
                                <span class="text-xs text-base-content/80 leading-snug">Украсить край: светлая кромка у заливки «Вода»</span>
                            </label>
                        </div>
                    </div>
                    <div id="map-erase-settings" class="hidden flex flex-col gap-1.5 mt-2 pt-2 border-t border-base-300/40" aria-label="Размер ластика">
                        <span class="text-[11px] text-base-content/55 uppercase tracking-wide">Ластик</span>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-base-content/60 shrink-0" for="map-erase-radius">Размер</label>
                            <input type="range" id="map-erase-radius" min="1" max="100" value="12" step="1" class="range range-xs flex-1 min-w-0 [--range-fill:0]" />
                            <span id="map-erase-radius-val" class="text-xs tabular-nums w-8 text-right text-base-content/80">12</span>
                        </div>
                    </div>
                </div>
                <div id="map-panel-tool-labels" class="map-tool-panel hidden flex flex-col gap-2">
                    <p class="text-xs font-medium text-base-content/60 uppercase tracking-wide">Подписи</p>
                    <ul class="flex flex-col gap-1.5 list-none p-0 m-0">
                        <li><button type="button" class="map-tool-stub btn btn-sm btn-ghost w-full rounded-none border border-base-300/60" disabled>Подпись</button></li>
                    </ul>
                </div>
                <div id="map-panel-sprites" class="flex flex-col gap-2">
                    <label for="map-type-select" class="text-xs font-medium text-base-content/60 uppercase tracking-wide">Тип</label>
                    <select id="map-type-select" class="select select-bordered w-full rounded-none bg-base-200 border-base-300 text-sm"></select>
                    <p class="text-xs text-base-content/50" id="map-placement-hint">Откройте категорию иконкой слева.</p>
                    <div id="map-sprite-grid" class="map-sprite-grid"></div>
                </div>
            </aside>
            <div class="map-canvas-wrap" id="map-canvas-wrap">
                <div id="map-stage-mount"></div>
            </div>
        </div>
        <p class="map-hint-row text-xs text-base-content/45 shrink-0">Холст <span class="tabular-nums">{{ $map->width }}×{{ $map->height }}</span> px. Перетаскивание вида: левая кнопка по пустому полю или средняя кнопка мыши; в режиме рисования ландшафта панорама только колесиком. Ctrl+Z (до 10 шагов) — отмена последнего штриха или заливки. Правый клик по объекту — удалить. Двойной клик по объекту — текст. Линейки у краёв окна; направляющие на карте. Смена размера в настройках сбрасывает сохранённую заливку (линии остаются).</p>
    </main>

    <dialog id="mapSettingsModal" class="modal modal-middle" aria-labelledby="map-settings-heading">
        <div class="modal-box noema-settings-modal-box rounded-none border border-base-300 bg-base-200 shadow-2xl p-6 md:p-8 w-full">
            <div class="noema-settings-modal-inner">
                <h2 id="map-settings-heading" class="font-display text-xl font-semibold text-base-content mb-6">Настройки карты</h2>
                <form method="POST" action="{{ route('worlds.maps.update', [$world, $map]) }}" class="min-h-0">
                @csrf
                @method('PUT')
                <div class="noema-settings-modal-body space-y-4">
                <div class="form-control w-full">
                    <label class="label py-1" for="mapSettingsTitle"><span class="label-text">Название</span></label>
                    <input type="text" id="mapSettingsTitle" name="title" value="{{ old('title', $map->title) }}" required maxlength="255"
                        class="input input-bordered w-full rounded-none bg-base-100 border-base-300 @error('title') input-error @enderror">
                    @error('title')
                        <p class="text-error text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control w-full">
                        <label class="label py-1" for="mapSettingsWidth"><span class="label-text">Ширина (px)</span></label>
                        <input type="number" id="mapSettingsWidth" name="width" value="{{ old('width', $map->width) }}" required min="{{ \App\Models\Worlds\WorldMap::MIN_SIDE }}" max="{{ \App\Models\Worlds\WorldMap::MAX_SIDE }}" step="1"
                            class="input input-bordered w-full rounded-none bg-base-100 border-base-300 tabular-nums @error('width') input-error @enderror">
                        @error('width')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-control w-full">
                        <label class="label py-1" for="mapSettingsHeight"><span class="label-text">Высота (px)</span></label>
                        <input type="number" id="mapSettingsHeight" name="height" value="{{ old('height', $map->height) }}" required min="{{ \App\Models\Worlds\WorldMap::MIN_SIDE }}" max="{{ \App\Models\Worlds\WorldMap::MAX_SIDE }}" step="1"
                            class="input input-bordered w-full rounded-none bg-base-100 border-base-300 tabular-nums @error('height') input-error @enderror">
                        @error('height')
                            <p class="text-error text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <p class="text-xs text-base-content/50">{{ \App\Models\Worlds\WorldMap::MIN_SIDE }}…{{ \App\Models\Worlds\WorldMap::MAX_SIDE }} px. Если изменить размер, файл заливки будет сброшен.</p>
                </div>
                <div class="noema-settings-modal-footer flex flex-wrap items-center justify-between gap-3 pt-4">
                    <div class="flex flex-wrap gap-1 items-center">
                        <button type="submit" form="map-settings-delete-form" class="btn btn-error btn-square rounded-none shrink-0" title="Удалить карту" aria-label="Удалить карту" onclick="return confirm('Удалить эту карту и все объекты на ней?');">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18"/>
                                <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex flex-row-reverse flex-wrap gap-2 justify-end">
                        <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                        <button type="button" class="btn btn-ghost rounded-none" onclick="document.getElementById('mapSettingsModal').close()">Отмена</button>
                    </div>
                </div>
                <div class="noema-settings-modal-grow" aria-hidden="true"></div>
            </form>
            </div>
            <form id="map-settings-delete-form" method="POST" action="{{ route('worlds.maps.destroy', [$world, $map]) }}" class="hidden" aria-hidden="true">
                @csrf
                @method('DELETE')
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default opacity-0 absolute inset-0 w-full h-full" aria-label="Закрыть">close</button></form>
    </dialog>

    <dialog id="map-object-edit-dialog" class="modal modal-middle" aria-labelledby="map-object-edit-heading">
        <div class="modal-box rounded-none border border-base-300 bg-base-200 shadow-2xl p-6 md:p-8 max-w-lg w-full">
            <h2 id="map-object-edit-heading" class="font-display text-xl font-semibold text-base-content mb-4">Объект на карте</h2>
            <div id="map-object-edit-title-field-wrap" class="form-control w-full">
                <label class="label py-1" for="map-object-edit-title"><span class="label-text">Название</span></label>
                <input type="text" id="map-object-edit-title" maxlength="255" autocomplete="off"
                    class="input input-ghost w-full rounded-none bg-transparent px-0 border-0 border-b border-base-300 focus:border-primary focus:outline-none focus:ring-0 placeholder:text-base-content/40" placeholder="Введите название">
            </div>
            <div class="form-control w-full mt-4">
                <label class="label py-1" for="map-object-edit-description"><span class="label-text">Описание</span></label>
                <textarea id="map-object-edit-description" rows="5"
                    class="textarea textarea-bordered w-full rounded-none bg-base-100 border-base-300 min-h-[120px] text-sm" placeholder="Описание (необязательно)"></textarea>
            </div>
            <div class="modal-action flex flex-wrap gap-2 justify-end mt-6">
                <button type="button" id="map-object-edit-cancel" class="btn btn-ghost rounded-none">Отмена</button>
                <button type="button" id="map-object-edit-save" class="btn btn-primary rounded-none">Сохранить</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default opacity-0 absolute inset-0 w-full h-full" aria-label="Закрыть">close</button></form>
    </dialog>

    @include('site.partials.footer')
    @if ($errors->has('title') || $errors->has('width') || $errors->has('height'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('mapSettingsModal')?.showModal();
            });
        </script>
    @endif
</body>
</html>
