<?php

namespace App\Models\Worlds;

use App\Models\Bestiary\Creature;
use App\Models\Biography\Biography;
use App\Models\Cards\Story;
use App\Models\Faction\Faction;
use App\Models\Timeline\TimelineLine;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

class World extends Model
{
    /**
     * Сторона холста новой карты по умолчанию (px), совпадает с дефолтом в миграции.
     */
    public const MAPS_DEFAULT_SIDE_PX = 2000;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'setting' => 'fantasy',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'reference_point',
        'timeline_max_year',
        'annotation',
        'image_path',
        'onoff',
        'setting',
        'maps_default_width',
        'maps_default_height',
    ];

    protected function casts(): array
    {
        return [
            'onoff' => 'boolean',
            'setting' => WorldSetting::class,
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('onoff', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function creatures(): HasMany
    {
        return $this->hasMany(Creature::class);
    }

    public function biographies(): HasMany
    {
        return $this->hasMany(Biography::class);
    }

    public function factions(): HasMany
    {
        return $this->hasMany(Faction::class);
    }

    public function timelineLines(): HasMany
    {
        return $this->hasMany(TimelineLine::class);
    }

    public function connectionBoards(): HasMany
    {
        return $this->hasMany(ConnectionBoard::class)->orderByDesc('updated_at');
    }

    /**
     * Карты мира (отдельные холсты).
     */
    public function maps(): HasMany
    {
        return $this->hasMany(WorldMap::class)->orderByDesc('updated_at');
    }

    /**
     * Все спрайты на всех картах мира (для разметки и выборов по миру).
     */
    public function mapSprites(): HasManyThrough
    {
        return $this->hasManyThrough(WorldMapSprite::class, WorldMap::class);
    }

    /**
     * Ширина по умолчанию для новых карт (px).
     */
    public function mapsDefaultWidth(): int
    {
        return (int) ($this->maps_default_width ?? self::MAPS_DEFAULT_SIDE_PX);
    }

    /**
     * Высота по умолчанию для новых карт (px).
     */
    public function mapsDefaultHeight(): int
    {
        return (int) ($this->maps_default_height ?? self::MAPS_DEFAULT_SIDE_PX);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'worlds/')) {
            return asset('storage/'.$this->image_path);
        }

        return $this->user->getUploadsUrl('worlds/'.$this->image_path);
    }

    /**
     * Удаляет файл обложки с диска (не меняет image_path в БД).
     */
    public function deleteImageFile(): void
    {
        if (! $this->image_path) {
            return;
        }

        $path = $this->image_path;
        if (str_starts_with($path, 'worlds/')) {
            Storage::disk('public')->delete($path);
        } else {
            $this->loadMissing('user');
            $full = $this->user->getUploadsPath('worlds/'.$path);
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }
}
