<?php

namespace App\Models\Worlds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorldMapSprite extends Model
{
    protected $fillable = [
        'world_id',
        'sprite_path',
        'pos_x',
        'pos_y',
    ];

    protected function casts(): array
    {
        return [
            'pos_x' => 'float',
            'pos_y' => 'float',
        ];
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }
}
