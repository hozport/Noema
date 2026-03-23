<?php

namespace App\Models\Worlds;

use App\Models\Cards\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class World extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'reference_point',
        'annotation',
        'image_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function imageUrl(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'worlds/')) {
            return asset('storage/' . $this->image_path);
        }

        return $this->user->getUploadsUrl('worlds/' . $this->image_path);
    }
}
