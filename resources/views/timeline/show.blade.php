<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Таймлайн — {{ $world->name }} — Noema</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/timeline.js'])
    @endif
    <style>
        /* Высокий холст; горизонтальная полоса прокрутки у нижнего края области (малый нижний паддинг). */
        .timeline-canvas-inner { min-height: min(72vh, 880px); }
        .timeline-canvas-scroll {
            padding: 10px 10px 2px;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            overflow: auto;
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
        .timeline-canvas-scroll.is-panning {
            cursor: grabbing !important;
        }
        .timeline-canvas-scroll.is-panning .timeline-line-hit,
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
        /* Футер ниже первого экрана: main не flex-1, занимает почти 100dvh минус шапку. */
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
    </style>
    <script type="application/json" id="timeline-axis-config">{!! json_encode(['tMin' => $visual['tMin'], 'tMax' => $visual['tMax'], 'canvasWidth' => $visual['canvasWidth']], JSON_THROW_ON_ERROR) !!}</script>
</head>
<body class="min-h-screen bg-base-100 flex flex-col timeline-page">
    @include('site.partials.header')

    <main class="flex flex-col w-full shrink-0 min-h-[calc(100dvh-5.75rem)]">
        <div class="shrink-0 px-6 pt-6 pb-4 w-full">
        @if (session('success'))
            <div role="alert" class="alert alert-success rounded-none mb-4 max-w-2xl">
                <span>{{ session('success') }}</span>
            </div>
        @endif
        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div class="min-w-0">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">Таймлайн</h1>
                <p class="text-base-content/60 mt-1">{{ $world->name }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0 mt-0.5 justify-end">
                <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square shrink-0" title="Назад в дашборд" aria-label="Назад в дашборд">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <button type="button" id="timeline-export-jpg" class="btn btn-ghost btn-square shrink-0" title="Сохранить холст в JPG" aria-label="Сохранить в JPG">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <path d="M21 15l-5-5L5 21"/>
                    </svg>
                </button>
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
                <button type="button" class="btn btn-ghost btn-square shrink-0 text-error hover:bg-error/15" title="Очистить таймлайн" aria-label="Очистить таймлайн" onclick="document.getElementById('timelineClearModal').showModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    </svg>
                </button>
                @include('partials.activity-log-button', ['world' => $world, 'timelineJournal' => true, 'journalTitle' => 'Журнал'])
            </div>
        </div>
        </div>

        <div class="flex-1 min-h-0 flex flex-col w-full border-y border-base-300/25 bg-base-200/20 rounded-none">
            <div class="flex-1 min-h-0 min-h-[280px] timeline-canvas-scroll">
                <div
                    id="timeline-jpg-export-root"
                    class="timeline-canvas-inner relative flex flex-col justify-end"
                    style="width: {{ $visual['canvasWidth'] }}px"
                >
                    <div
                        class="pointer-events-none absolute left-0 top-0 bottom-0 z-[5] overflow-hidden"
                        style="width: {{ $visual['canvasWidth'] }}px"
                        aria-hidden="true"
                    >
                        @foreach ($visual['rulerTicks'] as $ty)
                            @php
                                $gx = \App\Support\TimelineVisualDemo::yearToX($ty, $visual['tMin'], $visual['tMax'], $visual['canvasWidth']);
                            @endphp
                            <div
                                class="absolute top-0 bottom-0 w-px bg-base-content/[0.07]"
                                style="left: {{ $gx }}px;"
                            ></div>
                        @endforeach
                    </div>
                    @foreach ($visual['tracks'] as $track)
                        @php
                            $h = $track['kind'] === 'main' ? 56 : 48;
                            $mid = $h / 2;
                            $x1 = \App\Support\TimelineVisualDemo::yearToX($track['lineFromYear'], $visual['tMin'], $visual['tMax'], $visual['canvasWidth']);
                            $x2 = \App\Support\TimelineVisualDemo::yearToX($track['lineToYear'], $visual['tMin'], $visual['tMax'], $visual['canvasWidth']);
                            $hitPad = 12;
                            $labelText = \Illuminate\Support\Str::limit($track['label'], 42);
                            $startsAtEpoch = $track['lineFromYear'] === $visual['tMin'];
                            if ($startsAtEpoch) {
                                $labelX = 22;
                                $labelAnchor = 'start';
                            } else {
                                $labelX = max(0.0, $x1 - 8);
                                $labelAnchor = 'end';
                            }
                            $labelY = $startsAtEpoch
                                ? $mid - ($track['strokeWidth'] / 2) - 12
                                : $mid;
                        @endphp
                        <div class="timeline-track-row relative z-[10] shrink-0 bg-base-100/25" style="height: {{ $h }}px;">
                            <svg class="timeline-track-svg relative z-[10] w-full" viewBox="0 0 {{ $visual['canvasWidth'] }} {{ $h }}" width="{{ $visual['canvasWidth'] }}" height="{{ $h }}" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <line
                                    class="timeline-line-draw"
                                    x1="{{ $x1 }}"
                                    y1="{{ $mid }}"
                                    x2="{{ $x2 }}"
                                    y2="{{ $mid }}"
                                    stroke="{{ $track['color'] }}"
                                    stroke-width="{{ $track['strokeWidth'] }}"
                                    stroke-linecap="round"
                                    style="--line-sw: {{ $track['strokeWidth'] }};"
                                />
                                @php
                                    $hitBandW = abs($x2 - $x1);
                                    $hitBandX = min($x1, $x2);
                                    $hitBandH = max(28, (int) $track['strokeWidth'] + 22);
                                    $hitBandY = $mid - $hitBandH / 2;
                                @endphp
                                <rect
                                    class="timeline-line-hit"
                                    x="{{ $hitBandX }}"
                                    y="{{ $hitBandY }}"
                                    width="{{ max(1, $hitBandW) }}"
                                    height="{{ $hitBandH }}"
                                    fill="transparent"
                                    data-track-label="{{ e($track['label']) }}"
                                    data-line-id="{{ $track['id'] }}"
                                />
                                @foreach ($track['eventGroups'] as $group)
                                    @php
                                        $cx = \App\Support\TimelineVisualDemo::yearToX($group['year'], $visual['tMin'], $visual['tMax'], $visual['canvasWidth']);
                                        $tooltipPayload = [
                                            'year' => $group['year'],
                                            'titles' => $group['titles'],
                                            'exactDates' => $group['exactDates'],
                                            'lineLabel' => $track['label'],
                                            'count' => $group['count'],
                                            'eventIds' => $group['eventIds'] ?? [],
                                        ];
                                        $hitR = $track['dotRadius'] + $hitPad;
                                    @endphp
                                    <g class="timeline-point-group">
                                        <circle
                                            cx="{{ $cx }}"
                                            cy="{{ $mid }}"
                                            r="{{ $track['dotRadius'] }}"
                                            fill="{{ $track['color'] }}"
                                        />
                                        @if ($group['count'] > 1)
                                            <text
                                                x="{{ $cx }}"
                                                y="{{ $mid - $track['dotRadius'] - 6 }}"
                                                text-anchor="middle"
                                                fill="rgba(255,255,255,0.82)"
                                                font-size="11"
                                                font-family="system-ui, sans-serif"
                                            >×{{ $group['count'] }}</text>
                                        @endif
                                        <circle
                                            class="timeline-point-hit cursor-grab"
                                            cx="{{ $cx }}"
                                            cy="{{ $mid }}"
                                            r="{{ $hitR }}"
                                            fill="transparent"
                                            stroke="none"
                                            data-tooltip='@json($tooltipPayload)'
                                            data-track-label="{{ e($track['label']) }}"
                                            data-line-id="{{ $track['id'] }}"
                                        />
                                    </g>
                                @endforeach
                                <g class="pointer-events-none timeline-line-label" style="isolation: isolate;">
                                    <title>{{ $track['label'] }}</title>
                                    <text
                                        x="{{ $labelX }}"
                                        y="{{ $labelY }}"
                                        text-anchor="{{ $labelAnchor }}"
                                        dominant-baseline="middle"
                                        fill="rgba(255,255,255,0.72)"
                                        font-size="11"
                                        font-family="ui-sans-serif, system-ui, sans-serif"
                                        font-weight="600"
                                        style="text-transform: uppercase; letter-spacing: 0.06em;"
                                    >{{ $labelText }}</text>
                                </g>
                            </svg>
                        </div>
                    @endforeach

                    @php
                        $rulerH = 36;
                    @endphp
                    <div class="relative z-[10] shrink-0 bg-base-200/35" style="height: {{ $rulerH }}px;">
                        <svg class="timeline-track-svg w-full block" viewBox="0 0 {{ $visual['canvasWidth'] }} {{ $rulerH }}" width="{{ $visual['canvasWidth'] }}" height="{{ $rulerH }}" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <line x1="0" y1="1" x2="{{ $visual['canvasWidth'] }}" y2="1" stroke="rgba(255,255,255,0.12)" stroke-width="1"/>
                            @foreach ($visual['rulerTicks'] as $ty)
                                @php
                                    $rx = \App\Support\TimelineVisualDemo::yearToX($ty, $visual['tMin'], $visual['tMax'], $visual['canvasWidth']);
                                @endphp
                                <line x1="{{ $rx }}" y1="1" x2="{{ $rx }}" y2="12" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
                                <text x="{{ $rx }}" y="{{ $rulerH - 6 }}" text-anchor="middle" fill="rgba(255,255,255,0.4)" font-size="10" font-family="system-ui, sans-serif">{{ $ty }}</text>
                            @endforeach
                        </svg>
                    </div>

                    <div
                        id="timeline-crosshair"
                        class="pointer-events-none absolute inset-0 z-[30] hidden"
                        aria-hidden="true"
                    >
                        <div
                            id="timeline-crosshair-line"
                            class="absolute top-0 bottom-0 w-px -translate-x-1/2 bg-primary/65"
                            style="left: 0"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="timeline-point-tooltip" class="timeline-point-tooltip fixed z-[5000] opacity-0 invisible pointer-events-none" role="tooltip"></div>
    <div id="timeline-year-at-cursor" class="pointer-events-none fixed z-[45] hidden opacity-0 invisible" aria-hidden="true">0 г.</div>
    <div id="timeline-context-menu" class="fixed z-[10050] hidden" role="menu" aria-hidden="true"></div>

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

    @include('timeline.partials.modals', ['world' => $world, 'timelineLines' => $timelineLines, 'timelineEventsForJs' => $timelineEventsForJs])

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
    @elseif ($errors->any() && old('form_context') === 'event_create' && ($errors->has('timeline_line_id') || $errors->has('title') || $errors->has('epoch_year') || $errors->has('month') || $errors->has('day') || $errors->has('breaks_line')))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('timeline-event-dialog')?.showModal();
            });
        </script>
    @endif

    @include('site.partials.footer')
</body>
</html>
