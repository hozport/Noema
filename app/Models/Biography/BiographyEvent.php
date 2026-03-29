<?php

namespace App\Models\Biography;

use App\Models\Timeline\TimelineEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class BiographyEvent extends Model
{
    protected static function booted(): void
    {
        /**
         * Удаление с таймлайна не трогает факт в биографии.
         * Удаление факта из биографии удаляет и связанное событие на таймлайне.
         */
        static::deleting(function (BiographyEvent $e): void {
            $te = $e->timelineEvent()->first();
            if ($te === null) {
                return;
            }
            $line = $te->line;
            $te->biographies()->detach();
            $te->delete();
            $line->recalculateBoundsFromEvents();
        });
    }

    protected $fillable = [
        'biography_id',
        'title',
        'epoch_year',
        'year_end',
        'month',
        'day',
        'body',
        'breaks_line',
    ];

    protected function casts(): array
    {
        return [
            'epoch_year' => 'integer',
            'year_end' => 'integer',
            'month' => 'integer',
            'day' => 'integer',
            'breaks_line' => 'boolean',
        ];
    }

    public function biography(): BelongsTo
    {
        return $this->belongsTo(Biography::class);
    }

    /** Событие на таймлайне, если уже опубликовано из этой записи. */
    public function timelineEvent(): MorphOne
    {
        return $this->morphOne(TimelineEvent::class, 'source');
    }

    public function isOnTimeline(): bool
    {
        return $this->timelineEvent()->exists();
    }
}
