<?php

namespace App\Models\Bestiary;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatureGallery extends Model
{
    protected $table = 'creature_gallery';

    protected $fillable = [
        'creature_id',
        'path',
        'sort_order',
    ];

    public function creature(): BelongsTo
    {
        return $this->belongsTo(Creature::class);
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }

    public function pdfImageDataUri(): ?string
    {
        return Creature::publicStoragePathToDataUri($this->path);
    }
}
