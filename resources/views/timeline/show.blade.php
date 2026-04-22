<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.flash-toast-critical-css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Таймлайн — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/timeline.js'])
    @endif
    <style>
        /*
         * Без фиксированной высоты body flex-элемент с overflow:auto получает min-height от контента
         * (высота shell = layoutH × зум), и страница растёт при каждом приближении.
         */
        html:has(body.timeline-page) {
            height: 100%;
        }
        /*
         * Высота окна фиксирована: иначе при зуме растёт scrollHeight shell и flex-родители
         * подстраиваются под контент — «увеличивается весь блок». Скролл только внутри .timeline-canvas-scroll.
         */
        /*
         * Первый экран — только .timeline-page-viewport (шапка + main) в пределах 100dvh.
         * Футер в потоке body ниже вьюпорта, не участвует в flex-распределении высоты холста.
         */
        body.timeline-page {
            min-height: 100dvh;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .timeline-page-viewport {
            height: 100dvh;
            max-height: 100dvh;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            flex-shrink: 0;
        }
        .timeline-page-viewport > header {
            flex-shrink: 0;
        }
        .timeline-page-viewport > main {
            min-height: 0;
            min-width: 0;
            overflow: hidden;
        }
        body.timeline-page > footer {
            flex-shrink: 0;
        }
        /* Высокий холст внутри вьюпорта; горизонтальная полоса прокрутки у нижнего края области. */
        .timeline-canvas-inner { min-height: min(72vh, 880px); }
        /*
         * Вьюпорт холста: фиксирован в сетке flex (не растягивается из‑за ширины/высоты контента).
         * Масштаб меняет только содержимое внутри — полосы прокрутки появляются здесь, не у всей страницы.
         */
        .timeline-canvas-scroll {
            padding: 10px 10px 2px;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            overflow: auto;
            min-width: 0;
            min-height: 0;
            flex: 1 1 0%;
            overscroll-behavior: contain;
        }
        .timeline-track-svg { display: block; overflow: visible; }
        .timeline-track-row { overflow: visible; }
        .timeline-line-draw {
            transition: stroke-width 0.14s ease, filter 0.14s ease;
        }
        .timeline-track-row:has(.timeline-line-hit:hover) .timeline-line-draw,
        .timeline-track-row:has(.timeline-point-hit:hover) .timeline-line-draw {
            stroke-width: calc(var(--line-sw, 2) * 1px + 3px);
            filter: brightness(1.2) drop-shadow(0 0 6px rgba(255, 255, 255, 0.35));
        }
        .timeline-line-hit {
            cursor: grab;
        }
        /* Подпись и группа: клики только у дорожек с перестановкой (см. timeline.js). */
        .timeline-line-label {
            pointer-events: none;
        }
        .timeline-track-row--reorderable .timeline-line-label {
            pointer-events: auto;
        }
        .timeline-line-label-reorder-hit {
            pointer-events: none;
        }
        .timeline-track-row--reorderable .timeline-line-label-reorder-hit {
            pointer-events: all;
            cursor: ns-resize;
        }
        .timeline-track-row--reorderable .timeline-line-hit {
            cursor: ns-resize;
        }
        .timeline-canvas-scroll.is-panning {
            cursor: grabbing !important;
        }
        .timeline-canvas-scroll.is-panning .timeline-line-hit,
        .timeline-canvas-scroll.is-panning .timeline-line-label-reorder-hit,
        .timeline-canvas-scroll.is-panning .timeline-point-hit {
            cursor: grabbing !important;
        }
        .timeline-point-tooltip {
            max-width: 18rem;
            padding: 0.65rem 0.85rem;
            border-radius: 0;
            border: 1px solid oklch(var(--b3) / 0.98);
            background: oklch(var(--b1));
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, 0.2),
                0 16px 44px -10px rgba(0, 0, 0, 0.65);
            font-family: ui-sans-serif, system-ui, sans-serif;
            transition: opacity 0.12s ease;
        }
        #timeline-crosshair-line {
            box-shadow: 0 0 14px rgba(248, 113, 113, 0.35);
        }
        #timeline-year-at-cursor {
            padding: 0.2rem 0.5rem;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.04em;
            color: oklch(var(--bc) / 0.95);
            background: oklch(var(--b2) / 0.96);
            border: 1px solid oklch(var(--b3) / 0.85);
            box-shadow: 0 4px 18px -4px rgba(0, 0, 0, 0.45);
            white-space: nowrap;
        }
        /* Сплошная панель и текст без альфы из темы (полностью перекрывает холст). */
        #timeline-context-menu {
            z-index: 10050;
            min-width: 12rem;
            padding: 0.25rem 0;
            border-radius: 0;
            opacity: 1 !important;
            isolation: isolate;
            border: 1px solid oklch(var(--b3));
            border: 1px solid oklch(from oklch(var(--b3)) l c h / 1);
            background-color: oklch(var(--b1));
            background-color: oklch(from oklch(var(--b1)) l c h / 1);
            box-shadow:
                0 0 0 1px rgb(0 0 0 / 0.4),
                0 18px 48px -8px rgb(0 0 0 / 0.78);
            font-family: ui-sans-serif, system-ui, sans-serif;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        #timeline-context-menu .timeline-ctx-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.5rem 0.85rem;
            font-size: 0.8125rem;
            font-weight: 500;
            font-family: inherit;
            line-height: 1.35;
            border: none;
            cursor: pointer;
            opacity: 1 !important;
            color: oklch(var(--bc));
            color: oklch(from oklch(var(--bc)) l c h / 1);
            background-color: oklch(var(--b1));
            background-color: oklch(from oklch(var(--b1)) l c h / 1);
        }
        #timeline-context-menu .timeline-ctx-item:hover:not(:disabled) {
            background-color: oklch(var(--b2));
            background-color: oklch(from oklch(var(--b2)) l c h / 1);
        }
        #timeline-context-menu .timeline-ctx-item--danger {
            color: oklch(var(--er));
            color: oklch(from oklch(var(--er)) l c h / 1);
        }
        #timeline-context-menu .timeline-ctx-item--danger:hover:not(:disabled) {
            background-color: oklch(var(--b2));
            background-color: oklch(from oklch(var(--b2)) l c h / 1);
        }
        #timeline-context-menu .timeline-ctx-item:disabled {
            cursor: not-allowed;
            color: color-mix(in oklch, oklch(var(--bc)) 52%, oklch(var(--b1)) 48%);
            color: color-mix(in oklch, oklch(from oklch(var(--bc)) l c h / 1) 52%, oklch(from oklch(var(--b1)) l c h / 1) 48%);
        }
        #timeline-context-menu .timeline-ctx-item:disabled:hover {
            background-color: oklch(var(--b1));
            background-color: oklch(from oklch(var(--b1)) l c h / 1);
        }
        .timeline-dialog {
            position: fixed !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            max-width: none !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
            background: transparent !important;
            overflow: hidden !important;
            z-index: 10100;
        }
        .timeline-dialog::backdrop { background: rgba(0,0,0,0.55); }
        .timeline-dialog:not([open]) { display: none !important; }
        .timeline-dialog[open] { display: block !important; }
        .timeline-dialog__viewport {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            box-sizing: border-box;
        }
        .timeline-dialog__scrim { position: absolute; inset: 0; z-index: 0; cursor: pointer; }
        .timeline-dialog__panel {
            position: relative;
            z-index: 1;
            width: min(100%, 26rem);
            max-height: min(90dvh, 36rem);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: oklch(var(--b1));
            padding: 0;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            border-radius: 0;
            border: 1px solid oklch(var(--b3) / 0.9);
        }
        .timeline-dialog__scroll {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
        }
        .timeline-dialog__footer {
            flex-shrink: 0;
            padding: 0.75rem 1rem 1rem;
            border-top: 1px solid oklch(var(--b3) / 0.4);
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        .timeline-dialog__close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 2;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: none;
            background: transparent;
            color: oklch(var(--bc));
            opacity: 0.75;
            cursor: pointer;
        }
        .timeline-dialog__close:hover { opacity: 1; }
        /* Футер под вьюпортом первого экрана (прокрутка body). */
        body.timeline-page footer {
            margin-top: 0 !important;
        }
        dialog.timeline-clear-dialog:not([open]) { display: none !important; }
        dialog.timeline-clear-dialog[open] {
            position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important;
            width: 100vw !important; height: 100vh !important; margin: 0 !important; padding: 1rem !important;
            display: flex !important; align-items: center !important; justify-content: center !important;
            z-index: 1000 !important; overflow-y: auto !important;
        }
        dialog.timeline-clear-dialog[open]::backdrop { background: rgba(0,0,0,0.6); }
        .timeline-track-row--reorderable .timeline-line-hit,
        .timeline-track-row--reorderable .timeline-line-label-reorder-hit {
            touch-action: none;
        }
        .timeline-track-row--reorderable:has(.timeline-line-hit:hover),
        .timeline-track-row--reorderable:has(.timeline-line-label-reorder-hit:hover) {
            background-color: oklch(from oklch(var(--b2)) l c h / 0.35);
        }
        /*
         * Важно: #timeline-crosshair — sibling с z-30 на весь холст; обычные дорожки z-10.
         * Без z-40 весь предпросмотр и пунктир тянущейся строки оказываются ПОД перекрестьем и не видны.
         */
        .timeline-track-row.timeline-track-row--dragging {
            opacity: 0.92;
            outline: 2px dashed oklch(from oklch(var(--p)) l c h / 0.75);
            outline-offset: -2px;
            z-index: 40;
        }
        /* Цель переноса: отдельный слой на всю ширину холста, бледнее фона панели */
        .timeline-swap-target-layer {
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            width: 100%;
            z-index: 100;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.08s ease;
            /* Бледнее, чем типичный фон интерфейса (base-100): лёгкое осветление полосы */
            background: color-mix(in oklch, oklch(var(--b2)) 72%, white 28%);
            box-shadow: inset 0 0 0 1px color-mix(in oklch, oklch(var(--bc)) 22%, transparent);
        }
        .timeline-track-row.timeline-track-row--swap-preview {
            position: relative;
            z-index: 40;
        }
        .timeline-track-row.timeline-track-row--swap-preview .timeline-swap-target-layer {
            opacity: 1;
        }
        .timeline-canvas-section {
            flex: 1 1 0%;
            min-height: 0;
            min-width: 0;
            overflow: hidden;
            contain: layout;
        }
        /* Внутренний контент под зум; размер задаётся в JS (scrollWidth/Height внутри .timeline-canvas-scroll). */
        .timeline-zoom-shell {
            position: relative;
            display: block;
            width: max-content;
            max-width: none;
        }
        #timeline-jpg-export-root.timeline-zoom-root--scaled {
            transform-origin: top left;
        }
        /* Плавающая панель масштаба: всегда в зоне видимости при прокрутке страницы. */
        .timeline-zoom-controls-fixed {
            position: fixed;
            z-index: 90;
            bottom: max(1rem, env(safe-area-inset-bottom, 0px));
            right: max(1rem, env(safe-area-inset-right, 0px));
            pointer-events: auto;
            isolation: isolate;
            border: 1px solid oklch(var(--b3) / 0.85);
            background: oklch(var(--b1) / 0.96);
            box-shadow:
                0 0 0 1px rgb(0 0 0 / 0.12),
                0 12px 32px -8px rgb(0 0 0 / 0.45);
            backdrop-filter: blur(8px);
        }
        @media (max-width: 480px) {
            .timeline-zoom-controls-fixed {
                left: max(1rem, env(safe-area-inset-left, 0px));
                right: max(1rem, env(safe-area-inset-right, 0px));
                width: auto;
                justify-content: center;
            }
        }
        /* DaisyUI .modal-middle .modal-box задаёт height:auto и max-height:calc(100vh - 5em) с более высокой специфичностью, чем один класс на панели */
        #timelineSettingsModal.modal-middle .modal-box.noema-settings-modal-box {
            width: min(50vw, calc(100vw - 2rem)) !important;
            max-width: min(50vw, calc(100vw - 2rem)) !important;
            height: min(90vh, calc(100dvh - 2rem)) !important;
            min-height: min(90vh, calc(100dvh - 2rem)) !important;
            max-height: min(90vh, calc(100dvh - 2rem)) !important;
            overflow: hidden !important;
        }
    </style>
    <script type="application/json" id="timeline-axis-config">{!! json_encode([
        'tMin' => $visual['tMin'],
        'tMax' => $visual['tMax'],
        'canvasWidth' => $visual['canvasWidth'],
        'eventYearMin' => $visual['eventYearMin'],
        'eventYearMax' => $visual['eventYearMax'],
    ], JSON_THROW_ON_ERROR) !!}</script>
</head>
<body class="bg-base-100 flex flex-col timeline-page" data-world-id="{{ $world->id }}">
    <div class="timeline-page-viewport">
    @include('site.partials.header')

    <main class="flex flex-col flex-1 min-h-0 w-full min-w-0">
        <div class="shrink-0 px-6 pt-6 pb-4 w-full">
        @if (session('success'))
            <div role="alert" class="alert alert-success rounded-none mb-4 max-w-2xl" data-auto-dismiss>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div role="alert" class="alert alert-error rounded-none mb-4 max-w-2xl" data-auto-dismiss>
                <span>{{ session('error') }}</span>
            </div>
        @endif
        <x-noema-page-head>
            <x-slot name="title">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">Таймлайн</h1>
            </x-slot>
            <x-slot name="below">
                <p class="text-base-content/60">{{ $world->name }}</p>
            </x-slot>
            <x-slot name="center">
                <button type="button" id="timeline-open-line-dialog" class="btn btn-primary btn-sm btn-square shrink-0 rounded-none" title="Создать линию" aria-label="Создать линию">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="5" cy="12" r="2.5" fill="currentColor" stroke="none"/>
                        <path d="M7.5 12h9"/>
                        <circle cx="19" cy="12" r="2.5" fill="currentColor" stroke="none"/>
                    </svg>
                </button>
                <button type="button" id="timeline-open-event-dialog" class="btn btn-outline btn-sm btn-square shrink-0 rounded-none" title="Создать событие" aria-label="Создать событие">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                        <path d="M12 15v6M9 18h6"/>
                    </svg>
                </button>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square shrink-0" title="Назад в дашборд" aria-label="Назад в дашборд">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <a href="{{ route('worlds.timeline.pdf', $world) }}" class="btn btn-ghost btn-square shrink-0" title="Сохранить как PDF" aria-label="Сохранить как PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <path d="M12 18V9M9 15l3 3 3-3"/>
                    </svg>
                </a>
                <button type="button" class="btn btn-ghost btn-square shrink-0" title="Настройки" aria-label="Настройки" onclick="document.getElementById('timelineSettingsModal').showModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                </button>
                <button type="button" class="btn btn-ghost btn-square shrink-0 text-error hover:bg-error/15" title="Очистить таймлайн" aria-label="Очистить таймлайн" onclick="document.getElementById('timelineClearModal').showModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    </svg>
                </button>
                @include('partials.activity-log-button', ['world' => $world, 'timelineJournal' => true])
            </x-slot>
        </x-noema-page-head>
        </div>

        <div class="timeline-canvas-section flex-1 min-h-0 min-w-0 flex flex-col w-full border-y border-base-300/25 bg-base-200/20 rounded-none overflow-hidden">
            <div class="timeline-canvas-scroll w-full">
                <div id="timeline-zoom-shell" class="timeline-zoom-shell relative">
                    @include('timeline.partials.canvas-export-root', ['visual' => $visual, 'lineReorderMeta' => $lineReorderMeta])
                </div>
            </div>
        </div>
    </main>
    </div>

    <div
        class="timeline-zoom-controls-fixed flex items-center gap-0.5 rounded-none px-1.5 py-1"
        id="timeline-zoom-controls"
        role="group"
        aria-label="Масштаб холста"
    >
        <button type="button" id="timeline-zoom-out" class="btn btn-ghost btn-xs min-h-0 h-8 w-8 p-0 rounded-none" title="Уменьшить (Ctrl + колёсико)" aria-label="Уменьшить масштаб">−</button>
        <span id="timeline-zoom-label" class="text-[11px] tabular-nums text-base-content/80 min-w-[2.75rem] text-center">100%</span>
        <button type="button" id="timeline-zoom-in" class="btn btn-ghost btn-xs min-h-0 h-8 w-8 p-0 rounded-none" title="Увеличить (Ctrl + колёсико)" aria-label="Увеличить масштаб">+</button>
        <button type="button" id="timeline-zoom-reset" class="btn btn-ghost btn-xs min-h-0 h-7 px-1.5 rounded-none text-[11px]" title="Сбросить масштаб" aria-label="Масштаб 100 %">100%</button>
    </div>

    <div id="timeline-point-tooltip" class="timeline-point-tooltip fixed z-[5000] opacity-0 invisible pointer-events-none" role="tooltip"></div>
    <div id="timeline-year-at-cursor" class="pointer-events-none fixed z-[45] hidden opacity-0 invisible" aria-hidden="true">0 г.</div>
    <div id="timeline-context-menu" class="fixed z-[10050] hidden" role="menu" aria-hidden="true"></div>

    <dialog id="timelineSettingsModal" class="modal modal-middle" aria-labelledby="timeline-settings-heading">
        <div class="modal-box noema-settings-modal-box rounded-none border border-base-300 bg-base-200 shadow-2xl p-6 md:p-8 w-full">
            <div class="noema-settings-modal-inner">
                <h2 id="timeline-settings-heading" class="font-display text-xl font-semibold text-base-content mb-4">Настройки</h2>
                <form id="timeline-settings-form" method="POST" action="{{ route('worlds.timeline.world-reference.update', $world) }}" class="min-h-0">
                @csrf
                @method('PUT')
                <div class="noema-settings-modal-body space-y-4">
                <div class="form-control w-full">
                    <label class="label py-1" for="timeline-settings-reference"><span class="label-text">Инициирующее событие</span></label>
                    <input type="text" id="timeline-settings-reference" name="reference_point" value="{{ old('reference_point', $world->reference_point) }}" maxlength="255"
                        class="input input-bordered w-full rounded-none bg-base-100 border-base-300 @error('reference_point') input-error @enderror"
                        placeholder="Подпись нуля на шкале времени">
                    <p id="timeline-settings-err-reference_point" class="text-error text-sm mt-1 {{ $errors->has('reference_point') ? '' : 'hidden' }}" role="alert">
                        @if ($errors->has('reference_point')) {{ $errors->first('reference_point') }} @endif
                    </p>
                </div>
                <div class="form-control w-full">
                    <label class="label py-1" for="timeline-settings-max-year"><span class="label-text">Ограничить таймлайн</span></label>
                    <input type="number" id="timeline-settings-max-year" name="timeline_max_year" value="{{ old('timeline_max_year', $world->timeline_max_year) }}" min="0"
                        class="input input-bordered w-full rounded-none bg-base-100 border-base-300 @error('timeline_max_year') input-error @enderror"
                        placeholder="Последний год на шкале (пусто — без ограничения)">
                    <p id="timeline-settings-err-timeline_max_year" class="text-error text-sm mt-1 {{ $errors->has('timeline_max_year') ? '' : 'hidden' }}" role="alert">
                        @if ($errors->has('timeline_max_year')) {{ $errors->first('timeline_max_year') }} @endif
                    </p>
                </div>
                </div>
                <div class="modal-action flex flex-wrap gap-2 justify-end pt-4">
                    <button type="button" class="btn btn-ghost rounded-none" onclick="document.getElementById('timelineSettingsModal').close()">Отмена</button>
                    <button type="submit" class="btn btn-primary rounded-none">Сохранить</button>
                </div>
                <div class="noema-settings-modal-grow" aria-hidden="true"></div>
            </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default opacity-0 absolute inset-0 w-full h-full" aria-label="Закрыть">close</button></form>
    </dialog>

    <dialog id="timelineClearModal" class="modal modal-middle timeline-clear-dialog">
        <div class="modal-box rounded-none border border-base-300 bg-base-200 shadow-2xl p-6 md:p-8 max-w-lg w-full">
            <h2 class="font-display text-xl font-semibold text-base-content mb-2">Очистить таймлайн</h2>
            <p class="text-sm text-base-content/70 mb-4">Будут удалены все дополнительные линии и все события на основной линии, кроме маркера точки отсчёта. Сам таймлайн остаётся.</p>
            <form method="POST" action="{{ route('worlds.timeline.clear', $world) }}" class="modal-action flex flex-row-reverse flex-wrap gap-2 justify-end">
                @csrf
                <button type="submit" class="btn btn-error rounded-none">Очистить</button>
                <button type="button" class="btn btn-ghost rounded-none" onclick="document.getElementById('timelineClearModal').close()">Отмена</button>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button type="submit" class="cursor-default opacity-0 absolute inset-0 w-full h-full" aria-label="Закрыть">close</button></form>
    </dialog>

    @include('timeline.partials.modals', ['world' => $world, 'timelineLines' => $timelineLines, 'timelineEventsForJs' => $timelineEventsForJs, 'timelineEventSourceOptions' => $timelineEventSourceOptions])

    @if ($errors->any() && old('form_context') === 'line_edit' && old('edit_line_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.timelineResumeLineEdit?.({{ (int) old('edit_line_id') }});
            });
        </script>
    @elseif ($errors->any() && old('form_context') === 'line_create' && ($errors->has('name') || $errors->has('start_year') || $errors->has('end_year') || $errors->has('color')))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('timeline-line-dialog')?.showModal();
            });
        </script>
    @endif
    @if ($errors->any() && old('form_context') === 'event_edit' && old('edit_event_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.timelineResumeEventEdit?.({{ (int) old('edit_event_id') }});
            });
        </script>
    @elseif ($errors->any() && old('form_context') === 'event_create' && ($errors->has('timeline_line_id') || $errors->has('title') || $errors->has('epoch_year') || $errors->has('month') || $errors->has('day') || $errors->has('breaks_line') || $errors->has('biography_event_id') || $errors->has('faction_event_id')))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('timeline-event-dialog')?.showModal();
                @if (old('biography_event_id') || old('faction_event_id'))
                    document.getElementById('timeline-event-source-wrap')?.classList.remove('hidden');
                @endif
            });
        </script>
    @endif
    @if ($errors->has('reference_point') || $errors->has('timeline_max_year'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('timelineSettingsModal')?.showModal();
            });
        </script>
    @endif

    @include('site.partials.footer')
</body>
</html>
