<?php

namespace App\Models\Faction;

use App\Models\Timeline\TimelineEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class FactionEvent extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (FactionEvent $e): void {
            $te = $e->timelineEvent()->first();
            if ($te === null) {
                return;
            }
            $line = $te->line;
            $te->factions()->detach();
            $te->delete();
            $line->recalculateBoundsFromEvents();
        });
    }

    protected $fillable = [
        'faction_id',
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

    public function faction(): BelongsTo
    {
        return $this->belongsTo(Faction::class);
    }

    public function timelineEvent(): MorphOne
    {
        return $this->morphOne(TimelineEvent::class, 'source');
    }

    public function isOnTimeline(): bool
    {
        return $this->timelineEvent()->exists();
    }
}
