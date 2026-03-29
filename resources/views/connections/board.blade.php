<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $connectionBoard->name }} — Связи — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/connections.js'])
    @endif
    <style>
        .connections-canvas-inner { min-height: min(72vh, 880px); min-width: 2400px; }
        .connections-canvas-scroll {
            padding: 10px 10px 2px;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            overflow: auto;
        }
        .connections-canvas-scroll.is-panning {
            cursor: grabbing !important;
        }
        .connections-canvas-inner {
            position: relative;
            z-index: 0;
            isolation: isolate;
            background-color: oklch(var(--b2) / 0.35);
            background-image:
                linear-gradient(oklch(var(--b3) / 0.45) 1px, transparent 1px),
                linear-gradient(90deg, oklch(var(--b3) / 0.45) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        #connections-context-menu {
            z-index: 10050;
            min-width: 12rem;
            max-width: min(22rem, calc(100vw - 20px));
            max-height: min(70vh, 520px);
            overflow: auto;
            padding: 0.25rem 0;
            border-radius: 0;
            opacity: 1 !important;
            isolation: isolate;
            border: 1px solid oklch(var(--b3));
            background-color: oklch(var(--b1));
            box-shadow: 0 16px 44px -10px rgba(0, 0, 0, 0.65);
        }
        #connections-context-menu .connections-ctx-item {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.5rem 0.85rem;
            font-size: 0.875rem;
            line-height: 1.25;
            color: oklch(var(--bc));
            background: transparent;
            border: none;
            cursor: pointer;
        }
        #connections-context-menu .connections-ctx-item:hover:not(:disabled) {
            background: oklch(var(--b2));
        }
        #connections-context-menu .connections-ctx-item:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        #connections-context-menu .connections-ctx-heading {
            padding: 0.35rem 0.85rem 0.2rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: oklch(var(--bc) / 0.55);
        }
        #connections-context-menu .connections-ctx-back {
            border-bottom: 1px solid oklch(var(--b3) / 0.85);
            margin-bottom: 0.2rem;
        }
        .connection-board-node {
            position: absolute;
            z-index: 2;
            width: 176px;
            min-height: 4.5rem;
            padding: 0.65rem 1.9rem 0.65rem 0.75rem;
            border: 1px solid oklch(var(--b3));
            background: oklch(var(--b1));
            box-shadow: 0 8px 28px -8px rgba(0, 0, 0, 0.55);
            cursor: grab;
        }
        .connection-board-node.is-dragging {
            z-index: 100;
            cursor: grabbing;
            opacity: 0.92;
        }
        .connection-board-node__remove {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 1.5rem;
            height: 1.5rem;
            padding: 0;
            line-height: 1;
            font-size: 1.1rem;
            border: none;
            background: transparent;
            color: oklch(var(--bc) / 0.55);
            cursor: pointer;
            border-radius: 0;
        }
        .connection-board-node__remove:hover {
            color: oklch(var(--bc));
            background: oklch(var(--b2));
        }
        .connections-rail-btn {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0;
            border: 1px solid oklch(var(--b3) / 0.6);
            background: oklch(var(--b2) / 0.5);
            color: oklch(var(--bc) / 0.85);
            cursor: pointer;
        }
        .connections-rail-btn:hover {
            border-color: oklch(var(--p) / 0.45);
            color: oklch(var(--bc));
        }
        /* Режим нитей — явно зелёная кнопка (высокая специфичность + !important против утилит). */
        #connections-rail-link-btn.connections-rail-btn--link-on {
            background: oklch(0.38 0.08 150 / 0.92) !important;
            border: 2px solid oklch(0.62 0.17 150) !important;
            color: oklch(0.95 0.02 150) !important;
            box-shadow:
                inset 0 0 0 1px oklch(0.55 0.14 150 / 0.5),
                0 0 0 1px oklch(0.55 0.14 150 / 0.55);
        }
        #connections-rail-link-btn.connections-rail-btn--link-on:hover {
            filter: brightness(1.08);
            border-color: oklch(0.7 0.18 150) !important;
        }
        #connections-link-mode-banner {
            border-bottom: 1px solid oklch(0.5 0.12 150 / 0.55);
            background: oklch(0.38 0.06 150 / 0.35);
        }
        #connections-link-mode-banner strong {
            color: oklch(0.78 0.15 150);
        }
        /* Нити между фоном сетки и карточками: отрицательный z внутри isolate у холста. */
        #connections-edges-svg {
            position: absolute;
            inset: 0;
            z-index: -1;
        }
        #connections-edges-svg .connections-edge-hit {
            pointer-events: stroke;
            cursor: pointer;
        }
        .connection-board-node--link-pick {
            outline: 2px solid oklch(0.62 0.17 150);
            outline-offset: 2px;
        }
        #connections-nodes-layer {
            position: absolute;
            inset: 0;
            z-index: 1;
            transform: translateZ(0);
        }
        /* Как на таймлайне: футер сразу под холстом без лишнего отступа. */
        body.connections-page footer {
            margin-top: 0 !important;
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 flex flex-col connections-page">
    @include('site.partials.header')

    <main class="flex flex-col w-full shrink-0 min-h-[calc(100dvh-5.75rem)]">
        <div class="shrink-0 px-6 pt-6 pb-4 w-full">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                <div class="min-w-0">
                    <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">{{ $connectionBoard->name }}</h1>
                    <p class="text-base-content/60 mt-1">
                        <a href="{{ route('worlds.connections', $world) }}" class="link link-hover">Связи</a>
                        <span class="text-base-content/40 mx-1">·</span>
                        {{ $world->name }}
                    </p>
                </div>
                <a href="{{ route('worlds.connections', $world) }}" class="btn btn-ghost btn-square shrink-0 mt-0.5" title="К списку досок" aria-label="К списку досок">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            </div>
        </div>

        <div id="connections-link-mode-banner" class="hidden shrink-0 px-6 py-2.5 text-sm text-base-content/95 border-t border-base-300/30" role="status" aria-live="polite">
            <strong class="font-semibold">Режим нитей включён.</strong>
            Кликните по двум разным блокам по очереди, чтобы связать их. Перетаскивание карточек в этом режиме отключено.
            Нажмите ту же кнопку слева или Escape, чтобы выйти.
        </div>

        <div class="flex-1 min-h-0 flex flex-col w-full border-y border-base-300/25 bg-base-200/20 rounded-none">
            <div class="flex flex-1 min-h-0 min-h-[max(280px,min(78vh,920px))] overflow-hidden">
                <aside class="w-[60px] shrink-0 border-r border-base-300 bg-base-200/40 flex flex-col items-center py-4 gap-3" aria-label="Модули для доски">
                <button type="button" class="connections-rail-btn" data-connections-rail="link" id="connections-rail-link-btn" aria-pressed="false" title="Нить между блоками: два клика по карточкам" aria-label="Включить режим нитей между блоками">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M8 12h8M12 8v8"/><circle cx="8" cy="12" r="2"/><circle cx="16" cy="12" r="2"/>
                    </svg>
                </button>
                <button type="button" class="connections-rail-btn" data-connections-rail="timeline" title="События таймлайнов" aria-label="События таймлайнов">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <line x1="3" y1="12" x2="21" y2="12"/>
                        <polyline points="7 8 3 12 7 16"/>
                        <polyline points="17 8 21 12 17 16"/>
                    </svg>
                </button>
                <button type="button" class="connections-rail-btn" data-connections-rail="cards" title="Карточки" aria-label="Карточки">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                </button>
                <button type="button" class="connections-rail-btn" data-connections-rail="maps" title="Объекты карт" aria-label="Объекты карт">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/>
                        <line x1="8" y1="2" x2="8" y2="18"/>
                        <line x1="16" y1="6" x2="16" y2="22"/>
                    </svg>
                </button>
                <button type="button" class="connections-rail-btn" data-connections-rail="bestiary" title="Бестиарий" aria-label="Бестиарий">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                        <path d="M8 7h8"/>
                    </svg>
                </button>
                <button type="button" class="connections-rail-btn" data-connections-rail="biographies" title="Биографии" aria-label="Биографии">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </button>
                </aside>
                <div class="connections-canvas-scroll flex-1 min-w-0 min-h-0" id="connections-canvas-scroll">
                    <div class="connections-canvas-inner relative" id="connections-canvas-inner">
                        <svg id="connections-edges-svg" class="absolute top-0 left-0 block overflow-visible" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"></svg>
                        <div id="connections-nodes-layer" class="absolute inset-0"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    @php
        $connectionsPageMeta = [
            'csrf' => csrf_token(),
            'urls' => array_merge($urls, [
                'worldData' => url("/worlds/{$world->id}/connections"),
            ]),
            'nodes' => $nodesPayload,
            'edges' => $edgesPayload,
        ];
    @endphp
    <script type="application/json" id="connections-page-meta">@json($connectionsPageMeta)</script>

    <div id="connections-context-menu" class="fixed z-[10050] hidden" role="menu" aria-hidden="true"></div>

    @include('site.partials.footer')
</body>
</html>
