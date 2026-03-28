<?php

namespace App\Services;

use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Support\TimelineVisualDemo;

final class TimelineBootstrapService
{
    public static function bootstrap(World $world): void
    {
        $line = TimelineLine::query()->create([
            'world_id' => $world->id,
            'name' => 'История мира',
            'start_year' => 0,
            'end_year' => null,
            'color' => TimelineVisualDemo::MAIN_COLOR,
            'is_main' => true,
            'sort_order' => 0,
        ]);

        $title = filled($world->reference_point)
            ? (string) $world->reference_point
            : 'Точка отсчёта';

        TimelineEvent::query()->create([
            'timeline_line_id' => $line->id,
            'title' => $title,
            'epoch_year' => 0,
            'month' => 1,
            'day' => 1,
            'breaks_line' => false,
            'is_reference_marker' => true,
        ]);
    }
}
