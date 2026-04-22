<?php

namespace App\Observers;

use App\Models\Timeline\TimelineLine;
use App\Support\TimelineVisualCache;

/**
 * Инвалидация кеша визуала таймлайна при изменении линий
 *
 * Любая мутация `timeline_lines` влияет на дорожки и шкалу на холсте.
 */
final class TimelineLineObserver
{
    /**
     * Сброс кеша после создания линии
     *
     * @param  TimelineLine  $line  Линия таймлайна
     */
    public function created(TimelineLine $line): void
    {
        TimelineVisualCache::forget((int) $line->world_id);
    }

    /**
     * Сброс кеша после обновления линии
     *
     * @param  TimelineLine  $line  Линия таймлайна
     */
    public function updated(TimelineLine $line): void
    {
        TimelineVisualCache::forget((int) $line->world_id);
    }

    /**
     * Сброс кеша после удаления линии
     *
     * @param  TimelineLine  $line  Линия таймлайна
     */
    public function deleted(TimelineLine $line): void
    {
        TimelineVisualCache::forget((int) $line->world_id);
    }
}
