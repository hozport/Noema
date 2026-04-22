<?php

namespace App\Models;

use App\Models\Worlds\World;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Запись журнала активности
 *
 * Хранится в основной БД приложения вместе с `users` и `worlds`.
 */
class ActivityLog extends EloquentModel
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id',
        'owner_user_id',
        'world_id',
        'action',
        'subject_type',
        'subject_id',
        'summary',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Пользователь, совершивший действие
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Владелец записи журнала по смыслу мира (обычно владелец мира)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Мир, к которому относится запись
     */
    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class, 'world_id');
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public static function record(
        User $actor,
        ?World $world,
        string $action,
        string $summary,
        ?EloquentModel $subject = null,
        ?array $meta = null,
    ): self {
        return static::query()->create([
            'actor_id' => $actor->id,
            'owner_user_id' => $world !== null ? $world->user_id : $actor->id,
            'world_id' => $world?->id,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'summary' => $summary,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
