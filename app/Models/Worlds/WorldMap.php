<?php

namespace App\Models\Worlds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Карта мира (холст)
 *
 * У мира может быть несколько карт. Линии, заливка PNG и спрайты привязаны к записи карты.
 * Таблица в той же БД, что и {@see World}.
 * Размеры сторон — от {@see self::MIN_SIDE} до {@see self::MAX_SIDE} пикселей.
 */
class WorldMap extends Model
{
    /** Минимальная ширина или высота холста (px). */
    public const MIN_SIDE = 500;

    /** Максимальная ширина или высота холста (px). */
    public const MAX_SIDE = 5000;

    protected $fillable = [
        'world_id',
        'title',
        'width',
        'height',
        'map_drawing_lines',
        'map_fill_path',
    ];

    protected function casts(): array
    {
        return [
            'map_drawing_lines' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (WorldMap $map): void {
            if ($map->map_fill_path !== null && $map->map_fill_path !== '') {
                Storage::disk('public')->delete($map->map_fill_path);
            }
        });
    }

    /**
     * Мир-владелец
     */
    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class, 'world_id');
    }

    /**
     * Объекты (спрайты), размещённые на этой карте.
     */
    public function mapSprites(): HasMany
    {
        return $this->hasMany(WorldMapSprite::class, 'world_map_id')->orderBy('id');
    }

    /**
     * URL PNG заливки для превью и редактора: маршрут с проверкой владельца (не зависит от симлинка public/storage).
     */
    public function fillPreviewUrl(): ?string
    {
        if (! is_string($this->map_fill_path) || $this->map_fill_path === '') {
            return null;
        }
        if (! filled($this->world_id)) {
            return null;
        }
        if (! Storage::disk('public')->exists($this->map_fill_path)) {
            return null;
        }
        $abs = Storage::disk('public')->path($this->map_fill_path);
        if (! is_file($abs)) {
            return null;
        }
        $v = @filemtime($abs) ?: time();

        return route('worlds.maps.fill.show', ['world' => $this->world_id, 'map' => $this->id]).'?v='.$v;
    }
}
