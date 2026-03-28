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
        .timeline-canvas-inner { min-height: min(55vh, 520px); }
        .timeline-canvas-scroll {
            padding: 18px 10px 12px;
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
            cursor: pointer;
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
        #timeline-context-menu {
            z-index: 10050;
            min-width: 12rem;
            padding: 0.35rem 0;
            border-radius: 0;
            border: 1px solid oklch(var(--b3) / 0.98);
            background: oklch(var(--b1));
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, 0.25),
                0 18px 48px -8px rgba(0, 0, 0, 0.7);
            font-family: ui-sans-serif, system-ui, sans-serif;
        }
        #timeline-context-menu button {
            display: block;
            width: 100%;
            text-align: left;
            padding: 0.45rem 0.85rem;
            font-size: 0.8125rem;
            border: none;
            background: transparent;
            color: oklch(var(--bc) / 0.98);
            cursor: pointer;
        }
        #timeline-context-menu button:hover:not(:disabled) {
            background: oklch(var(--b2) / 0.98);
        }
        #timeline-context-menu button:disabled {
            opacity: 0.65;
            cursor: not-allowed;
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
    </style>
    <script type="application/json" id="timeline-axis-config">{!! json_encode(['tMin' => $visual['tMin'], 'tMax' => $visual['tMax'], 'canvasWidth' => $visual['canvasWidth']], JSON_THROW_ON_ERROR) !!}</script>
</head>
<body class="min-h-screen bg-base-100 flex flex-col">
    @include('site.partials.header')

    <main class="flex-1 flex flex-col min-h-0 w-full">
        <div class="shrink-0 px-6 pt-6 pb-4 w-full">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div class="min-w-0">
                <h1 class="text-[1.875rem] font-semibold text-base-content leading-tight" style="font-family: 'Cormorant Garamond', Georgia, serif;">Таймлайн</h1>
                <p class="text-base-content/60 mt-1">{{ $world->name }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0 mt-0.5">
                <a href="{{ route('worlds.dashboard', $world) }}" class="btn btn-ghost btn-square shrink-0" title="Назад в дашборд" aria-label="Назад в дашборд">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
                <button type="button" id="timeline-open-line-dialog" class="btn btn-primary btn-sm rounded-none">Создать линию</button>
                <button type="button" id="timeline-open-event-dialog" class="btn btn-outline btn-sm rounded-none">Создать событие</button>
            </div>
        </div>

        <p class="text-sm text-base-content/55 mb-4 shrink-0 max-w-3xl">
            Ось отображения: <strong class="text-base-content/75">{{ $visual['tMin'] }}…{{ $visual['tMax'] }}</strong> г.
            (размах данных {{ $visual['dataSpanYears'] }} г., отступ справа {{ $visual['paddingYears'] }} г.;
            шаг шкалы {{ $visual['rulerStep'] }} г.).
            Ширина холста {{ $visual['canvasWidth'] }}px — при короткой истории те же пиксели соответствуют меньшему числу лет (масштаб подстраивается под данные).
            Метка нуля: <strong class="text-base-content/75">{{ $visual['referenceLabel'] }}</strong>.
        </p>
        </div>

        <div class="flex-1 min-h-0 flex flex-col w-full border-y border-base-300/25 bg-base-200/20 rounded-none">
            <div class="overflow-x-auto flex-1 min-h-0 min-h-[240px] timeline-canvas-scroll cursor-crosshair">
                <div
                    class="timeline-canvas-inner relative flex flex-col justify-end"
                    style="width: {{ $visual['canvasWidth'] }}px"
                >
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
                                            class="timeline-point-hit cursor-pointer"
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
    <div id="timeline-context-menu" class="fixed z-[10050] hidden opacity-0 invisible" role="menu" aria-hidden="true"></div>

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
