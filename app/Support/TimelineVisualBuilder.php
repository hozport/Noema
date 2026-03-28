<?php

namespace App\Support;

use App\Models\Timeline\TimelineEvent;
use App\Models\Worlds\World;
use Illuminate\Support\Collection;

/**
 * Холст таймлайна из БД (см. docs/timeline-spec.md).
 */
final class TimelineVisualBuilder
{
    public const CANVAS_MIN_WIDTH = 3000;

    public const MAIN_STROKE = 10;

    public const SECONDARY_STROKE = 3;

    /**
     * @return array{
     *   canvasWidth: int,
     *   tMin: int,
     *   tMax: int,
     *   paddingYears: int,
     *   dataSpanYears: int,
     *   displayRangeYears: int,
     *   rulerStep: int,
     *   referenceLabel: string,
     *   tracks: list<array{
     *     id: int|string,
     *     kind: 'main'|'secondary',
     *     label: string,
     *     color: string,
     *     strokeWidth: float,
     *     dotRadius: float,
     *     lineFromYear: int,
     *     lineToYear: int,
     *     eventGroups: list<array{year: int, count: int, titles: list<string>, exactDates: list<string|null>, eventIds: list<int>}>
     *   }>,
     *   rulerTicks: list<int>
     * }
     */
    public static function build(World $world): array
    {
        $referenceLabel = filled($world->reference_point)
            ? (string) $world->reference_point
            : 'Точка отсчёта';

        $lines = $world->timelineLines()
            ->with(['events'])
            ->orderBy('is_main')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tMin = 0;
        $allYears = [$tMin];

        foreach ($lines as $line) {
            $allYears[] = (int) $line->start_year;
            if ($line->end_year !== null) {
                $allYears[] = (int) $line->end_year;
            }
            foreach ($line->events as $event) {
                $allYears[] = (int) $event->epoch_year;
            }
        }

        $dataMax = max($allYears);
        $span = $dataMax - $tMin;
        $paddingYears = TimelineVisualDemo::adaptivePaddingYears($span, $dataMax);
        $tMax = $dataMax + $paddingYears;
        if ($tMax <= $tMin) {
            $tMax = $tMin + 1;
        }

        $canvasWidth = self::CANVAS_MIN_WIDTH;

        $tracks = [];

        foreach ($lines as $line) {
            $isMain = $line->is_main;
            $stroke = (float) ($isMain ? self::MAIN_STROKE : self::SECONDARY_STROKE);
            $dotR = max(8, $stroke) / 2;

            $terminatorYear = $isMain ? null : self::firstTerminatorYear($line->events);
            $lineEndFromDefinition = $line->end_year !== null ? (int) $line->end_year : $tMax;
            $lineEnd = $lineEndFromDefinition;
            if ($terminatorYear !== null) {
                $lineEnd = min($lineEnd, $terminatorYear);
            }
            $lineEnd = min($lineEnd, $tMax);
            $lineStart = min(max((int) $line->start_year, $tMin), $tMax);

            $eventsForGroups = $line->events->filter(function (TimelineEvent $e) use ($lineStart, $lineEnd) {
                $y = (int) $e->epoch_year;

                return $y >= $lineStart && $y <= $lineEnd;
            })->values()->all();

            $tracks[] = [
                'id' => $line->id,
                'kind' => $isMain ? 'main' : 'secondary',
                'label' => $line->name,
                'color' => $line->color,
                'strokeWidth' => $stroke,
                'dotRadius' => $dotR,
                'lineFromYear' => $lineStart,
                'lineToYear' => max($lineStart, $lineEnd),
                'eventGroups' => self::groupEventsForYear($eventsForGroups),
            ];
        }

        $rulerStep = TimelineVisualDemo::rulerStepForYearRange($tMin, $tMax);
        $rulerTicks = [];
        for ($y = $tMin; $y <= $tMax; $y += $rulerStep) {
            $rulerTicks[] = $y;
        }

        return [
            'canvasWidth' => $canvasWidth,
            'tMin' => $tMin,
            'tMax' => $tMax,
            'paddingYears' => $paddingYears,
            'dataSpanYears' => $span,
            'displayRangeYears' => $tMax - $tMin,
            'rulerStep' => $rulerStep,
            'referenceLabel' => $referenceLabel,
            'tracks' => $tracks,
            'rulerTicks' => $rulerTicks,
        ];
    }

    /**
     * @param  Collection<int, TimelineEvent>|list<TimelineEvent>  $events
     */
    private static function firstTerminatorYear($events): ?int
    {
        $sorted = collect($events)->sortBy([
            ['epoch_year', 'asc'],
            ['month', 'asc'],
            ['day', 'asc'],
            ['id', 'asc'],
        ]);

        foreach ($sorted as $e) {
            if ($e->breaks_line) {
                return (int) $e->epoch_year;
            }
        }

        return null;
    }

    /**
     * @param  list<TimelineEvent>  $events
     * @return list<array{year: int, count: int, titles: list<string>, exactDates: list<string|null>, eventIds: list<int>}>
     */
    private static function groupEventsForYear(array $events): array
    {
        $byYear = [];
        foreach ($events as $e) {
            $y = (int) $e->epoch_year;
            if (! isset($byYear[$y])) {
                $byYear[$y] = ['year' => $y, 'titles' => [], 'exactDates' => [], 'eventIds' => []];
            }
            $byYear[$y]['titles'][] = $e->title;
            $byYear[$y]['exactDates'][] = self::formatExactDate($e);
            $byYear[$y]['eventIds'][] = (int) $e->id;
        }
        ksort($byYear, SORT_NUMERIC);
        $groups = [];
        foreach ($byYear as $row) {
            $groups[] = [
                'year' => $row['year'],
                'count' => count($row['titles']),
                'titles' => $row['titles'],
                'exactDates' => $row['exactDates'],
                'eventIds' => $row['eventIds'],
            ];
        }

        return $groups;
    }

    private static function formatExactDate(TimelineEvent $e): string
    {
        return sprintf('%02d.%02d.%d', $e->day, $e->month, $e->epoch_year);
    }
}
