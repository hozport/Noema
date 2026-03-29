<?php

namespace App\Models\Worlds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectionBoard extends Model
{
    protected $fillable = [
        'world_id',
        'name',
    ];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(ConnectionBoardNode::class)->orderBy('id');
    }

    public function edges(): HasMany
    {
        return $this->hasMany(ConnectionBoardEdge::class)->orderBy('id');
    }
}
