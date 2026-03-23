<?php

namespace App\Models\Cards;

use App\Models\Worlds\World;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    protected $fillable = ['world_id', 'name'];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('position');
    }
}
