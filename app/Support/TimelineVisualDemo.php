<?php

namespace App\Support;

use App\Models\Worlds\World;

/**
 * Демонстрационная модель холста таймлайна для визуальной верстки без БД.
 */
final class TimelineVisualDemo
{
    public const MAIN_COLOR = '#7A1E2E';

    public const CANVAS_MIN_WIDTH = 3000;

    public const MAIN_STROKE = 10;

    public const SECONDARY_STROKE = 3;

    public const TRACK_GAP = 25;

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
     *     id: string,
     *     kind: 'main'|'secondary',
     *     label: string,
     *     color: string,
     *     strokeWidth: float,
     *     dotRadius: float,
     *     lineFromYear: int,
     *     lineToYear: int,
     *     eventGroups: list<array{year: int, count: int, titles: list<string>, exactDates: list<string|null>}>
     *   }>,
     *   rulerTicks: list<int>
     * }
     */
    public static function build(World $world): array
    {
        $referenceLabel = filled($world->reference_point)
            ? (string) $world->reference_point
            : 'Точка отсчёта';

        $secondaryRaw = [
            [
                'id' => 'line-knight',
                'label' => 'Рыцарь Пелмэн',
                'color' => '#2D6A4F',
                'lineFromYear' => 1400,
                'lineToYear' => 1456,
                'events' => [
                    ['year' => 1400, 'title' => 'Рождение', 'exact' => '14.03.1400', 'terminates' => false],
                    ['year' => 1420, 'title' => 'Рыцарский орден', 'exact' => null, 'terminates' => false],
                    ['year' => 1456, 'title' => 'Смерть', 'exact' => null, 'terminates' => true],
                ],
            ],
            [
                'id' => 'line-war',
                'label' => 'Война за Север',
                'color' => '#457B9D',
                'lineFromYear' => 1100,
                'lineToYear' => null,
                'events' => [
                    ['year' => 1100, 'title' => 'Начало', 'exact' => null, 'terminates' => false],
                    ['year' => 2400, 'title' => 'Перемирие', 'exact' => '01.06.2400', 'terminates' => false],
                ],
            ],
        ];

        $mainEventsRaw = [
            ['year' => 0, 'title' => $referenceLabel, 'exact' => null],
            ['year' => 500, 'title' => 'Первый совет', 'exact' => null],
            ['year' => 1500, 'title' => 'Союз королевств', 'exact' => null],
            ['year' => 1500, 'title' => 'Коронация', 'exact' => '12.04.1500'],
            ['year' => 2800, 'title' => 'Эпоха бури', 'exact' => null],
        ];

        $allYears = [0];
        foreach ($mainEventsRaw as $e) {
            $allYears[] = $e['year'];
        }
        foreach ($secondaryRaw as $line) {
            $allYears[] = $line['lineFromYear'];
            if ($line['lineToYear'] !== null) {
                $allYears[] = $line['lineToYear'];
            }
            foreach ($line['events'] as $e) {
                $allYears[] = $e['year'];
            }
        }

        $dataMax = max($allYears);
        $tMin = 0;
        $span = $dataMax - $tMin;
        $paddingYears = self::adaptivePaddingYears($span, $dataMax);
        $tMax = $dataMax + $paddingYears;
        if ($tMax <= $tMin) {
            $tMax = $tMin + 1;
        }
        $canvasWidth = self::CANVAS_MIN_WIDTH;

        $tracks = [];

        foreach (array_reverse($secondaryRaw) as $line) {
            $terminatorYear = null;
            $eventsSorted = $line['events'];
            usort($eventsSorted, fn ($a, $b) => $a['year'] <=> $b['year']);
            foreach ($eventsSorted as $e) {
                if (! empty($e['terminates'])) {
                    $terminatorYear = $e['year'];
                    break;
                }
            }
            $lineEnd = $line['lineToYear'];
            if ($terminatorYear !== null) {
                $lineEnd = $terminatorYear;
            } elseif ($lineEnd === null) {
                $lineEnd = $tMax;
            }
            $lineEnd = min($lineEnd, $tMax);

            $stroke = (float) self::SECONDARY_STROKE;
            $dotR = max(8, $stroke) / 2;

            $tracks[] = [
                'id' => $line['id'],
                'kind' => 'secondary',
                'label' => $line['label'],
                'color' => $line['color'],
                'strokeWidth' => $stroke,
                'dotRadius' => $dotR,
                'lineFromYear' => $line['lineFromYear'],
                'lineToYear' => $lineEnd,
                'eventGroups' => self::groupEventsForYear($eventsSorted),
            ];
        }

        $mainStroke = (float) self::MAIN_STROKE;
        $tracks[] = [
            'id' => 'main',
            'kind' => 'main',
            'label' => 'История мира',
            'color' => self::MAIN_COLOR,
            'strokeWidth' => $mainStroke,
            'dotRadius' => max(8, $mainStroke) / 2,
            'lineFromYear' => $tMin,
            'lineToYear' => $tMax,
            'eventGroups' => self::groupEventsForYear($mainEventsRaw),
        ];

        $rulerStep = self::rulerStepForYearRange($tMin, $tMax);
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
     * Отступ за последним событием: доля от размаха, с ограничениями; для «пустого» окна — минимальная длина.
     */
    public static function adaptivePaddingYears(int $spanYears, int $dataMaxYear): int
    {
        if ($spanYears === 0 && $dataMaxYear === 0) {
            return 100;
        }
        if ($spanYears === 0) {
            return max(10, min(200, (int) round(max(1, $dataMaxYear) * 0.08 + 5)));
        }

        $fromSpan = (int) max(5, round($spanYears * 0.08));

        return min(500, max(5, $fromSpan));
    }

    /**
     * Шаг подписей шкалы по длине видимого отрезка [tMin, tMax].
     */
    public static function rulerStepForYearRange(int $tMin, int $tMax): int
    {
        $range = max(1, $tMax - $tMin);
        if ($range <= 150) {
            return 10;
        }
        if ($range <= 600) {
            return 50;
        }
        if ($range <= 3000) {
            return 100;
        }
        if ($range <= 8000) {
            return 250;
        }

        return 500;
    }

    public static function yearToX(int $year, int $tMin, int $tMax, int $canvasWidth): float
    {
        if ($tMax <= $tMin) {
            return 0.0;
        }

        return (($year - $tMin) / ($tMax - $tMin)) * $canvasWidth;
    }

    /**
     * @param  list<array{year: int, title: string, exact?: string|null, terminates?: bool}>  $events
     * @return list<array{year: int, count: int, titles: list<string>, exactDates: list<string|null>}>
     */
    private static function groupEventsForYear(array $events): array
    {
        $byYear = [];
        foreach ($events as $e) {
            $y = $e['year'];
            if (! isset($byYear[$y])) {
                $byYear[$y] = ['year' => $y, 'titles' => [], 'exactDates' => []];
            }
            $byYear[$y]['titles'][] = $e['title'];
            $byYear[$y]['exactDates'][] = $e['exact'] ?? null;
        }
        ksort($byYear, SORT_NUMERIC);
        $groups = [];
        foreach ($byYear as $row) {
            $groups[] = [
                'year' => $row['year'],
                'count' => count($row['titles']),
                'titles' => $row['titles'],
                'exactDates' => $row['exactDates'],
            ];
        }

        return $groups;
    }
}
