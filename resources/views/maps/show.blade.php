<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Карты — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
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
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="map-page-main flex-1 flex flex-col min-h-0">
        <div class="map-title-row flex flex-wrap items-start justify-between gap-4 py-4 shrink-0">
            <div class="min-w-0">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">Карты</h1>
                <p class="text-base-content/60 mt-1 max-w-2xl text-sm">{{ $world->name }}</p>
            </div>
            <div class="flex items-center gap-1 shrink-0 mt-0.5">
                <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square" title="Назад в дашборд" aria-label="Назад в дашборд">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                @include('partials.activity-log-button', ['world' => $world])
            </div>
        </div>

        <script type="application/json" id="map-page-meta">@json($mapPageMeta)</script>

        <div class="map-layout">
            <aside class="map-sidebar-icons" aria-label="Категории объектов">
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
            <aside id="map-sidebar-panel" class="map-sidebar-panel" aria-label="Тип и спрайты" aria-hidden="true">
                <label for="map-type-select" class="text-xs font-medium text-base-content/60 uppercase tracking-wide">Тип</label>
                <select id="map-type-select" class="select select-bordered w-full rounded-none bg-base-200 border-base-300 text-sm"></select>
                <p class="text-xs text-base-content/50" id="map-placement-hint">Откройте категорию иконкой слева.</p>
                <div id="map-sprite-grid" class="map-sprite-grid"></div>
            </aside>
            <div class="map-canvas-wrap" id="map-canvas-wrap">
                <div id="map-stage-mount"></div>
            </div>
        </div>
        <p class="map-hint-row text-xs text-base-content/45 shrink-0">Холст 3000×3000 px. Средняя кнопка мыши — сдвиг карты; правый клик по объекту — удалить. Линейки у краёв окна; направляющие на карте. Рисование контуров — позже.</p>
    </main>

    @include('site.partials.footer')
</body>
</html>
