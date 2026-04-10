<?php

namespace App\Models\Biography;

use App\Models\Concerns\HasNoemaMarkupDescriptions;
use App\Models\Faction\Faction;
use App\Models\Timeline\TimelineEvent;
use App\Models\Worlds\World;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Biography extends Model
{
    use HasNoemaMarkupDescriptions;

    protected $fillable = [
        'world_id',
        'name',
        'race_faction_id',
        'people_faction_id',
        'country_faction_id',
        'gender',
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

    public function raceFaction(): BelongsTo
    {
        return $this->belongsTo(Faction::class, 'race_faction_id');
    }

    public function peopleFaction(): BelongsTo
    {
        return $this->belongsTo(Faction::class, 'people_faction_id');
    }

    public function countryFaction(): BelongsTo
    {
        return $this->belongsTo(Faction::class, 'country_faction_id');
    }

    /**
     * Связь «членство» через pivot (faction_biography).
     * Сюда же при сохранении биографии попадают фракции расы / народа / страны из отдельных полей,
     * чтобы список совпадал с карточками фракций и единым sync().
     */
    public function membershipFactions(): BelongsToMany
    {
        return $this->belongsToMany(
            Faction::class,
            'faction_biography',
            'biography_id',
            'faction_id'
        );
    }

    /**
     * Фракции для блока «Состоит в фракции»: все связи из pivot (в т.ч. заданные в карточке фракции).
     * Не дублируем фракцию, если она уже выведена в шапке как раса / народ / страна.
     */
    public function socialMembershipFactions(): Collection
    {
        $this->loadMissing('membershipFactions');

        return $this->membershipFactions
            ->filter(function (Faction $f): bool {
                if ($this->race_faction_id !== null && (int) $f->id === (int) $this->race_faction_id) {
                    return false;
                }
                if ($this->people_faction_id !== null && (int) $f->id === (int) $this->people_faction_id) {
                    return false;
                }
                if ($this->country_faction_id !== null && (int) $f->id === (int) $this->country_faction_id) {
                    return false;
                }

                return true;
            })
            ->sortBy('name')
            ->values();
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
        )->withPivot(['kinship', 'kinship_custom']);
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

    /** Подпись пола для шапки и экспорта. */
    public function genderLabel(): ?string
    {
        return match ($this->gender) {
            'm' => 'мужчина',
            'f' => 'женщина',
            default => null,
        };
    }

    /**
     * Строка под заголовком: раса и пол через запятую (например «Человек, мужчина»).
     *
     * @return non-empty-string|null
     */
    public function bioHeaderMetaLine(): ?string
    {
        $parts = [];
        $raceLabel = $this->raceLabel();
        if ($raceLabel !== null && $raceLabel !== '') {
            $parts[] = $raceLabel;
        }
        $peopleLabel = $this->peopleLabel();
        if ($peopleLabel !== null && $peopleLabel !== '') {
            $parts[] = $peopleLabel;
        }
        $countryLabel = $this->countryLabel();
        if ($countryLabel !== null && $countryLabel !== '') {
            $parts[] = $countryLabel;
        }
        $g = $this->genderLabel();
        if ($g !== null) {
            $parts[] = $g;
        }
        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }

    public function raceLabel(): ?string
    {
        $this->loadMissing('raceFaction');

        return $this->raceFaction?->name;
    }

    public function peopleLabel(): ?string
    {
        $this->loadMissing('peopleFaction');

        return $this->peopleFaction?->name;
    }

    public function countryLabel(): ?string
    {
        $this->loadMissing('countryFaction');

        return $this->countryFaction?->name;
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
