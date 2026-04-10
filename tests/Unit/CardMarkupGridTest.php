<?php

namespace Tests\Unit;

use App\Models\Cards\Card;
use PHPUnit\Framework\TestCase;

class CardMarkupGridTest extends TestCase
{
    public function test_empty_content_returns_empty_grid_html(): void
    {
        $card = new Card(['content' => '']);
        $this->assertSame('', $card->getMarkupHtmlParagraphsForGrid());
    }

    public function test_paragraphs_split_and_markup_rendered(): void
    {
        $card = new Card([
            'content' => "[b]First[/b]\n\n[i]Second[/i]",
        ]);
        $html = $card->getMarkupHtmlParagraphsForGrid();
        $this->assertStringContainsString('story-card-preview-p', $html);
        $this->assertStringContainsString('<strong>First</strong>', $html);
        $this->assertStringContainsString('<em>Second</em>', $html);
    }

    public function test_strikethrough_in_grid_paragraph(): void
    {
        $card = new Card([
            'content' => '[s]gone[/s]',
        ]);
        $html = $card->getMarkupHtmlParagraphsForGrid();
        $this->assertStringContainsString('<s>', $html);
        $this->assertStringContainsString('gone', $html);
    }
}
