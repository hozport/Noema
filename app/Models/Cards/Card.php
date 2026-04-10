<?php

namespace App\Models\Cards;

use App\Markup\NoemaMarkupHtmlRenderer;
use App\Markup\NoemaMarkupParser;
use App\Markup\NoemaMarkupPlainRenderer;
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

    /** Безопасный HTML для отображения разметки карточки. */
    public function getMarkupHtmlContent(): string
    {
        if ($this->content === null || $this->content === '') {
            return '';
        }
        $parser = new NoemaMarkupParser;
        $nodes = $parser->parse((string) $this->content);

        return NoemaMarkupHtmlRenderer::render($nodes);
    }

    /**
     * HTML для сетки карточек: абзацы (двойной перевод строки) как отдельные блоки, внутри — разметка Noema.
     */
    public function getMarkupHtmlParagraphsForGrid(): string
    {
        if ($this->content === null || $this->content === '') {
            return '';
        }
        $trimmed = trim((string) $this->content);
        if ($trimmed === '') {
            return '';
        }
        $parts = preg_split('/\n\s*\n/', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
        $parser = new NoemaMarkupParser;
        $html = '';
        foreach ($parts as $para) {
            $nodes = $parser->parse(trim($para));
            $html .= '<p class="story-card-preview-p">'.NoemaMarkupHtmlRenderer::render($nodes).'</p>';
        }

        return $html;
    }

    /** Плоский текст без тегов (превью, поиск). */
    public function getPlainContentForPreview(): string
    {
        if ($this->content === null || $this->content === '') {
            return '';
        }
        $parser = new NoemaMarkupParser;
        $nodes = $parser->parse((string) $this->content);

        return NoemaMarkupPlainRenderer::render($nodes);
    }
}
