<?php

namespace App\Support;

use App\Models\Worlds\World;
use Illuminate\Support\Facades\Cache;

/**
 * Кеш результата сборки визуала таймлайна для мира
 *
 * Хранит массив из `TimelineVisualBuilder::build()` под ключом `world:{id}:timeline_visual`.
 * Сбрасывается обсерверами при изменении линий, событий и релевантных полей мира.
 */
final class TimelineVisualCache
{
    /**
     * «Запасной» TTL записи: основная консистентность за счёт явной инвалидации.
     */
    private const REMEMBER_TTL_SECONDS = 86400;

    /**
     * Ключ кеша визуала таймлайна для мира
     *
     * @param  int  $worldId  Идентификатор мира
     */
    public static function cacheKey(int $worldId): string
    {
        return 'world:'.$worldId.':timeline_visual';
    }

    /**
     * Возвращает закешированный визуал или строит и кладёт в кеш
     *
     * @param  World  $world  Мир (уже с проверкой прав вызывающим кодом)
     * @return array<string, mixed>
     */
    public static function remember(World $world): array
    {
        $key = self::cacheKey((int) $world->id);

        return Cache::remember($key, self::REMEMBER_TTL_SECONDS, static fn (): array => TimelineVisualBuilder::build($world));
    }

    /**
     * Сбрасывает кеш визуала для мира
     *
     * @param  int  $worldId  Идентификатор мира
     */
    public static function forget(int $worldId): void
    {
        Cache::forget(self::cacheKey($worldId));
    }
}
