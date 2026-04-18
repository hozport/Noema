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
     *   rulerTicks: list<int>,
     *   eventYearMin: int|null,
     *   eventYearMax: int|null
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
        $eventYears = [];

        foreach ($lines as $line) {
            $allYears[] = (int) $line->start_year;
            if ($line->end_year !== null) {
                $allYears[] = (int) $line->end_year;
            }
            foreach ($line->events as $event) {
                $y = (int) $event->epoch_year;
                $allYears[] = $y;
                $eventYears[] = $y;
            }
        }

        $dataMax = max($allYears);
        $span = $dataMax - $tMin;
        $paddingYears = TimelineVisualDemo::adaptivePaddingYears($span, $dataMax);
        $tMax = $dataMax + $paddingYears;
        if ($tMax <= $tMin) {
            $tMax = $tMin + 1;
        }

        /* Заданный «последний год шкалы» задаёт правую границу холста (в т.ч. дальше данных — запас справа). */
        if ($world->timeline_max_year !== null) {
            $cap = (int) $world->timeline_max_year;
            if ($cap > $tMin) {
                $tMax = $cap;
            }
        }
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

        $eventYearMin = $eventYears === [] ? null : min($eventYears);
        $eventYearMax = $eventYears === [] ? null : max($eventYears);

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
            'eventYearMin' => $eventYearMin,
            'eventYearMax' => $eventYearMax,
        ];
    }

    /**
     * Год первого по времени обрыва на дополнительной линии.
     *
     * При нескольких точках с флагом «обрывает линию» действует **самый ранний** по шкале
     * (при равном годе — по месяцу, дню, id). Основная линия мира не обрывается по событиям.
     *
     * @param  Collection<int, TimelineEvent>|list<TimelineEvent>  $events
     * @return int|null Год обрыва или null, если ни одна точка не обрывает линию
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
