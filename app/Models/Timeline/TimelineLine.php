<?php

namespace App\Models\Timeline;

use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Models\Worlds\World;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimelineLine extends Model
{
    protected $fillable = [
        'world_id',
        'source_biography_id',
        'source_faction_id',
        'name',
        'start_year',
        'end_year',
        'extends_to_canvas_end',
        'color',
        'is_main',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'extends_to_canvas_end' => 'boolean',
        ];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    /** Линия, созданная из биографии персонажа (если есть). */
    public function sourceBiography(): BelongsTo
    {
        return $this->belongsTo(Biography::class, 'source_biography_id');
    }

    /** Линия, созданная из фракции (если есть). */
    public function sourceFaction(): BelongsTo
    {
        return $this->belongsTo(Faction::class, 'source_faction_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TimelineEvent::class, 'timeline_line_id')->orderBy('epoch_year')->orderBy('month')->orderBy('day')->orderBy('id');
    }

    /**
     * Подстраивает границы линии по событиям, не сужая заданный пользователем отрезок.
     * Левая граница: min(текущий start_year, min(годов событий)).
     * Правая граница: главная и биография (extends_to_canvas_end) — null; иначе max(текущий end_year, max(годов событий)).
     */
    public function recalculateBoundsFromEvents(): void
    {
        $years = TimelineEvent::query()
            ->where('timeline_line_id', $this->id)
            ->pluck('epoch_year');

        if ($years->isEmpty()) {
            return;
        }

        $minY = (int) $years->min();
        $maxY = (int) $years->max();
        $oldStart = (int) $this->start_year;
        $oldEnd = $this->end_year !== null ? (int) $this->end_year : null;

        $this->start_year = min($oldStart, $minY);

        if ($this->is_main || $this->extends_to_canvas_end) {
            $this->end_year = null;
        } else {
            $this->end_year = max($oldEnd ?? $maxY, $maxY);
        }

        $this->save();
    }
}
