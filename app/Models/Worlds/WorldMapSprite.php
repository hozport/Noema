<?php

namespace App\Models\Worlds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Объект на карте мира
 *
 * Строка в `world_map_sprites`: координаты верхнего левого угла спрайта на холсте,
 * путь к файлу картинки в `public/sprites`, название и описание для подписи и модалки.
 */
class WorldMapSprite extends Model
{
    protected $fillable = [
        'world_id',
        'sprite_path',
        'pos_x',
        'pos_y',
        'title',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'pos_x' => 'float',
            'pos_y' => 'float',
        ];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    /**
     * Допустимо ли название для ссылки в разметке на этот объект карты
     *
     * Нужна непустая строка после trim; строка вида «объект на карте #12» (регистр не важен)
     * считается служебной подписью и для ссылок не подходит — такие объекты не показывают в списке выбора.
     *
     * @param  string|null  $title  Значение поля title
     */
    public static function titleQualifiesForMarkupEntityLink(?string $title): bool
    {
        if ($title === null) {
            return false;
        }
        $t = trim($title);
        if ($t === '') {
            return false;
        }
        if (preg_match('/^объект на карте\s*#\d+$/iu', $t)) {
            return false;
        }

        return true;
    }

    /**
     * Можно ли вставлять в разметку ссылку на этот спрайт (по названию в БД)
     */
    public function qualifiesForMarkupEntityLink(): bool
    {
        return self::titleQualifiesForMarkupEntityLink($this->title);
    }
}
