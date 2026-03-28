<?php

namespace App\Models\Bestiary;

use App\Models\Worlds\World;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Creature extends Model
{
    protected $fillable = [
        'world_id',
        'name',
        'scientific_name',
        'species_kind',
        'image_path',
        'height_text',
        'weight_text',
        'lifespan_text',
        'short_description',
        'full_description',
        'habitat_text',
        'food_custom',
    ];

    protected function casts(): array
    {
        return [
            'food_custom' => 'array',
        ];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function relatedCreatures(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'creature_related',
            'creature_id',
            'related_creature_id'
        );
    }

    public function foodCreatures(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'creature_food',
            'creature_id',
            'food_creature_id'
        );
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(CreatureGallery::class)->orderBy('sort_order')->orderBy('id');
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'creatures/')) {
            return asset('storage/'.$this->image_path);
        }

        return $this->world->user->getUploadsUrl('creatures/'.$this->image_path);
    }

    /** Data URI для встраивания в PDF (только локальные файлы в public/storage). */
    public function pdfImageDataUri(): ?string
    {
        return self::publicStoragePathToDataUri($this->image_path);
    }

    public static function publicStoragePathToDataUri(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '' || ! str_starts_with($relativePath, 'creatures/')) {
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

    /** Строка для textarea «своя пища» (по строкам). */
    public function foodCustomLines(): string
    {
        $lines = $this->food_custom ?? [];

        return is_array($lines) ? implode("\n", $lines) : '';
    }
}
