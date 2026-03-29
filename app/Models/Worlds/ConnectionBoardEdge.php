<?php

namespace App\Models\Worlds;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConnectionBoardEdge extends Model
{
    protected $fillable = [
        'connection_board_id',
        'from_node_id',
        'to_node_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(ConnectionBoard::class, 'connection_board_id');
    }

    public function fromNode(): BelongsTo
    {
        return $this->belongsTo(ConnectionBoardNode::class, 'from_node_id');
    }

    public function toNode(): BelongsTo
    {
        return $this->belongsTo(ConnectionBoardNode::class, 'to_node_id');
    }
}
