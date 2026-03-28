<?php

namespace App\Models\Cards;

use App\Models\Worlds\World;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Story extends Model
{
    protected $fillable = ['world_id', 'name', 'cycle', 'synopsis'];

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('number')->orderBy('id');
    }

    /**
     * Перенумеровать карточки истории подряд: 1 … n по текущему порядку.
     */
    public function renumberCards(): void
    {
        DB::transaction(function () {
            $this->cards()
                ->orderBy('number')
                ->orderBy('id')
                ->get()
                ->values()
                ->each(function (Card $card, int $index) {
                    $next = $index + 1;
                    if ((int) $card->number !== $next) {
                        $card->update(['number' => $next]);
                    }
                });
        });
    }
}
