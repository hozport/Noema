<?php

namespace App\Models\Timeline;

use App\Models\Biography\Biography;
use App\Models\Worlds\World;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimelineLine extends Model
{
    protected $fillable = [
        'world_id',
        'source_biography_id',
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

    public function events(): HasMany
    {
        return $this->hasMany(TimelineEvent::class, 'timeline_line_id')->orderBy('epoch_year')->orderBy('month')->orderBy('day')->orderBy('id');
    }

    /**
     * Обновляет левую границу по min(годов событий). Правая граница:
     * — главная линия мира и линии из биографии (extends_to_canvas_end): end_year = null (до конца холста);
     * — ручные линии с заданным периодом: end_year = max(годов).
     */
    public function recalculateBoundsFromEvents(): void
    {
        $years = TimelineEvent::query()
            ->where('timeline_line_id', $this->id)
            ->pluck('epoch_year');

        if ($years->isEmpty()) {
            return;
        }

        $this->start_year = (int) $years->min();

        if ($this->is_main || $this->extends_to_canvas_end) {
            $this->end_year = null;
        } else {
            $this->end_year = (int) $years->max();
        }

        $this->save();
    }
}
