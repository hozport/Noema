<?php

namespace App\Models\Timeline;

use App\Models\Biography\Biography;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TimelineEvent extends Model
{
    protected $fillable = [
        'timeline_line_id',
        'title',
        'epoch_year',
        'month',
        'day',
        'breaks_line',
        'is_reference_marker',
        'source_type',
        'source_id',
    ];

    protected function casts(): array
    {
        return [
            'breaks_line' => 'boolean',
            'is_reference_marker' => 'boolean',
        ];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(TimelineLine::class, 'timeline_line_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function biographies(): BelongsToMany
    {
        return $this->belongsToMany(
            Biography::class,
            'biography_timeline_event',
            'timeline_event_id',
            'biography_id'
        )->withTimestamps();
    }
}
