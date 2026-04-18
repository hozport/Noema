{{-- Холст таймлайна (экспорт JPG и интерактив); при смене шкалы подменяется через AJAX. --}}
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
            $reorder = $lineReorderMeta[$track['id']] ?? null;
            $showReorder = $track['kind'] !== 'main' && $reorder && ($reorder['can_up'] || $reorder['can_down']);
        @endphp
        <div
            class="timeline-track-row relative z-[10] w-full shrink-0 bg-base-100/25 {{ $showReorder ? 'timeline-track-row--reorderable' : '' }}"
            style="height: {{ $h }}px;"
            @if ($showReorder)
                data-reorder-line-id="{{ $track['id'] }}"
                data-can-move-up="{{ $reorder['can_up'] ? '1' : '0' }}"
                data-can-move-down="{{ $reorder['can_down'] ? '1' : '0' }}"
            @endif
        >
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
                >@if ($showReorder)
                    <title>Потяните дорожку вверх или вниз, чтобы поменять порядок с соседней линией (не с основной)</title>
                @endif
                </rect>
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
                <g class="timeline-line-label" style="isolation: isolate;">
                    <title>@if ($showReorder)
Потяните дорожку вверх или вниз, чтобы поменять порядок с соседней линией (не с основной). {{ $track['label'] }}
                    @else
{{ $track['label'] }}
                    @endif</title>
                    <text
                        class="timeline-line-label-reorder-hit"
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
            <div class="timeline-swap-target-layer" aria-hidden="true"></div>
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
