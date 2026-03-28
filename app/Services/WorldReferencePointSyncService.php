<?php

namespace App\Services;

use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;

/**
 * Синхронизирует заголовок маркера «точки отсчёта» на главной линии таймлайна с полем world.reference_point.
 */
final class WorldReferencePointSyncService
{
    public function sync(World $world): void
    {
        $title = filled($world->reference_point)
            ? (string) $world->reference_point
            : 'Точка отсчёта';

        $line = TimelineLine::query()
            ->where('world_id', $world->id)
            ->where('is_main', true)
            ->first();

        if (! $line) {
            return;
        }

        $event = TimelineEvent::query()
            ->where('timeline_line_id', $line->id)
            ->where('is_reference_marker', true)
            ->first();

        if (! $event) {
            $event = TimelineEvent::query()
                ->where('timeline_line_id', $line->id)
                ->where('epoch_year', 0)
                ->where('month', 1)
                ->where('day', 1)
                ->whereNull('source_type')
                ->whereNull('source_id')
                ->orderBy('id')
                ->first();
        }

        if (! $event) {
            return;
        }

        $event->title = $title;
        $event->is_reference_marker = true;
        $event->save();
    }
}
