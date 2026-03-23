<?php

namespace App\Models\Cards;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $fillable = ['story_id', 'title', 'content', 'position'];

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
