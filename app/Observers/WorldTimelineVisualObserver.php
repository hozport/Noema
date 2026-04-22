<?php

namespace App\Observers;

use App\Models\Worlds\World;
use App\Support\TimelineVisualCache;

/**
 * Инвалидация кеша визуала таймлайна при изменении настроек мира на холсте
 *
 * Реагирует на поля, которые читает `TimelineVisualBuilder::build()` с модели `World`.
 */
final class WorldTimelineVisualObserver
{
    /**
     * Сброс кеша при удалении мира
     *
     * @param  World  $world  Удаляемый мир
     */
    public function deleted(World $world): void
    {
        TimelineVisualCache::forget((int) $world->id);
    }

    /**
     * Сброс кеша при изменении подписи точки отсчёта или правой границы шкалы
     *
     * @param  World  $world  Мир
     */
    public function updated(World $world): void
    {
        if ($world->wasChanged(['reference_point', 'timeline_max_year'])) {
            TimelineVisualCache::forget((int) $world->id);
        }
    }
}
