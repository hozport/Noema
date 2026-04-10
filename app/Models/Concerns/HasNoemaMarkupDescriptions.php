<?php

namespace App\Models\Concerns;

use App\Markup\NoemaMarkupHtmlRenderer;
use App\Markup\NoemaMarkupParser;

trait HasNoemaMarkupDescriptions
{
    /** HTML для отображения краткого описания (разметка Noema). */
    public function shortDescriptionMarkupHtml(): string
    {
        return $this->markupFieldToHtml($this->short_description ?? '');
    }

    /** HTML для отображения полного описания (разметка Noema). */
    public function fullDescriptionMarkupHtml(): string
    {
        return $this->markupFieldToHtml($this->full_description ?? '');
    }

    private function markupFieldToHtml(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        $parser = new NoemaMarkupParser;
        $nodes = $parser->parse($raw);

        return NoemaMarkupHtmlRenderer::render($nodes);
    }
}
