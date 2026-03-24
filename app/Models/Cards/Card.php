<?php

namespace App\Models\Cards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $fillable = ['story_id', 'title', 'content', 'number', 'is_highlighted'];

    protected function casts(): array
    {
        return [
            'is_highlighted' => 'boolean',
        ];
    }

    /** Подпись на карточке: своё название или «Карточка N». */
    public function displayTitle(): string
    {
        $t = trim((string) ($this->title ?? ''));

        return $t !== '' ? $t : 'Карточка '.$this->number;
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    /**
     * Контент хранится как текст, абзацы разделены двойным переводом строки.
     */
    public function getParagraphs(): array
    {
        if (empty($this->content)) {
            return [];
        }
        $paragraphs = preg_split('/\n\s*\n/', trim($this->content), -1, PREG_SPLIT_NO_EMPTY);

        return array_map('trim', $paragraphs);
    }

    public function setParagraphs(array $paragraphs): void
    {
        $this->content = implode("\n\n", array_map('trim', $paragraphs));
        $this->save();
    }
}
