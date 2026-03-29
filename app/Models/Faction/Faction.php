<?php

namespace App\Models\Faction;

use App\Models\Biography\Biography;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Services\FactionDedicatedBiographyTypeMigrationService;
use App\Support\FactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Faction extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (Faction $faction): void {
            foreach (TimelineLine::query()->where('source_faction_id', $faction->id)->get() as $line) {
                $line->delete();
            }
        });

        static::updated(function (Faction $faction): void {
            if ($faction->wasChanged('name')) {
                TimelineLine::query()
                    ->where('source_faction_id', $faction->id)
                    ->update(['name' => Str::limit($faction->name, 255)]);
            }
            if ($faction->wasChanged('type')) {
                $oldType = (string) $faction->getOriginal('type');
                app(FactionDedicatedBiographyTypeMigrationService::class)
                    ->migrate($faction, $oldType, (string) $faction->type);
            }
        });
    }

    protected $fillable = [
        'world_id',
        'name',
        'type',
        'type_custom',
        'short_description',
        'full_description',
        'geographic_stub',
        'image_path',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function factionEvents(): HasMany
    {
        return $this->hasMany(FactionEvent::class)->orderByRaw('epoch_year IS NULL')->orderBy('epoch_year')->orderBy('id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            Biography::class,
            'faction_biography',
            'faction_id',
            'biography_id'
        );
    }

    public function relatedFactions(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'faction_related',
            'faction_id',
            'related_faction_id'
        );
    }

    public function enemyFactions(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'faction_enemy',
            'faction_id',
            'enemy_faction_id'
        );
    }

    /** Персонажи, у которых в биографии выбрана эта раса. */
    public function raceBiographies(): HasMany
    {
        return $this->hasMany(Biography::class, 'race_faction_id');
    }

    public function sourceTimelineLine(): HasOne
    {
        return $this->hasOne(TimelineLine::class, 'source_faction_id');
    }

    public function typeLabel(): string
    {
        if ($this->type === FactionType::OTHER && filled($this->type_custom)) {
            return (string) $this->type_custom;
        }

        return FactionType::label($this->type);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'factions/')) {
            return asset('storage/'.$this->image_path);
        }

        return $this->world->user->getUploadsUrl('factions/'.$this->image_path);
    }

    public static function publicStoragePathToDataUri(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '' || ! str_starts_with($relativePath, 'factions/')) {
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

    public function pdfImageDataUri(): ?string
    {
        return self::publicStoragePathToDataUri($this->image_path);
    }
}
