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
use Illuminate\Support\Facades\Storage;

class World extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'reference_point',
        'annotation',
        'image_path',
        'onoff',
    ];

    protected function casts(): array
    {
        return [
            'onoff' => 'boolean',
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

    public function mapSprites(): HasMany
    {
        return $this->hasMany(WorldMapSprite::class)->orderBy('id');
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
