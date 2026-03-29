<?php

namespace App\Models\Worlds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectionBoardNode extends Model
{
    protected $fillable = [
        'connection_board_id',
        'kind',
        'entity_id',
        'meta',
        'x',
        'y',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'entity_id' => 'integer',
            'x' => 'integer',
            'y' => 'integer',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(ConnectionBoard::class, 'connection_board_id');
    }
}
