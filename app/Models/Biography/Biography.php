<?php

namespace App\Models\Biography;

use App\Models\Timeline\TimelineEvent;
use App\Models\Worlds\World;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Biography extends Model
{
    protected $fillable = [
        'world_id',
        'name',
        'race',
        'birth_year',
        'birth_month',
        'birth_day',
        'death_year',
        'death_month',
        'death_day',
        'image_path',
        'short_description',
        'full_description',
    ];

    protected function casts(): array
    {
        return [
            'birth_year' => 'integer',
            'birth_month' => 'integer',
            'birth_day' => 'integer',
            'death_year' => 'integer',
            'death_month' => 'integer',
            'death_day' => 'integer',
        ];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function timelineEvents(): BelongsToMany
    {
        return $this->belongsToMany(
            TimelineEvent::class,
            'biography_timeline_event',
            'biography_id',
            'timeline_event_id'
        )->withTimestamps();
    }

    public function biographyEvents(): HasMany
    {
        return $this->hasMany(BiographyEvent::class)->orderByRaw('epoch_year IS NULL')->orderBy('epoch_year')->orderBy('id');
    }

    public function relatives(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'biography_relative',
            'biography_id',
            'relative_biography_id'
        );
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'biography_friend',
            'biography_id',
            'friend_biography_id'
        );
    }

    public function enemies(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'biography_enemy',
            'biography_id',
            'enemy_biography_id'
        );
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'biographies/')) {
            return asset('storage/'.$this->image_path);
        }

        return $this->world->user->getUploadsUrl('biographies/'.$this->image_path);
    }

    /** Годы жизни для карточек списка (например 1200–1284 или …–1300). */
    public function lifeYearsLabel(): string
    {
        $from = $this->birth_year !== null ? (string) $this->birth_year : '…';
        $to = $this->death_year !== null ? (string) $this->death_year : '…';

        return $from.'–'.$to;
    }

    public function pdfImageDataUri(): ?string
    {
        return self::publicStoragePathToDataUri($this->image_path);
    }

    public static function publicStoragePathToDataUri(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '' || ! str_starts_with($relativePath, 'biographies/')) {
            return null;
        }
        $full = Storage::disk('public')->path($relativePath);
        if (! is_readable($full)) {
            return null;
        }
        $ext = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
        $raw = @file_get_contents($full);
        if ($raw === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }
}
