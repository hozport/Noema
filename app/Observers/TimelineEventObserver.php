<?php

namespace App\Observers;

use App\Models\Timeline\TimelineEvent;
use App\Models\Timeline\TimelineLine;
use App\Support\TimelineVisualCache;

/**
 * Инвалидация кеша визуала таймлайна при изменении событий на линиях
 */
final class TimelineEventObserver
{
    /**
     * Сброс кеша по миру линии события
     *
     * @param  TimelineEvent  $event  Событие таймлайна
     */
    private function forgetForEventWorld(TimelineEvent $event): void
    {
        $line = TimelineLine::query()->find($event->timeline_line_id);
        if ($line !== null) {
            TimelineVisualCache::forget((int) $line->world_id);
        }
    }

    /**
     * Сброс кеша после создания события
     *
     * @param  TimelineEvent  $event  Событие таймлайна
     */
    public function created(TimelineEvent $event): void
    {
        $this->forgetForEventWorld($event);
    }

    /**
     * Сброс кеша после обновления события
     *
     * @param  TimelineEvent  $event  Событие таймлайна
     */
    public function updated(TimelineEvent $event): void
    {
        $this->forgetForEventWorld($event);
    }

    /**
     * Сброс кеша после удаления события
     *
     * @param  TimelineEvent  $event  Событие таймлайна
     */
    public function deleted(TimelineEvent $event): void
    {
        $this->forgetForEventWorld($event);
    }
}
