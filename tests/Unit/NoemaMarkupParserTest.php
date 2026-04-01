<?php

namespace Tests\Unit;

use App\Markup\NoemaMarkupHtmlRenderer;
use App\Markup\NoemaMarkupParser;
use App\Markup\NoemaMarkupPlainRenderer;
use App\Markup\NoemaMarkupValidator;
use PHPUnit\Framework\TestCase;

class NoemaMarkupParserTest extends TestCase
{
    public function test_plain_text(): void
    {
        $p = new NoemaMarkupParser;
        $nodes = $p->parse('hello');
        $this->assertSame([], $p->getErrors());
        $this->assertSame('hello', NoemaMarkupPlainRenderer::render($nodes));
    }

    public function test_bold_nested_italic(): void
    {
        $p = new NoemaMarkupParser;
        $nodes = $p->parse('[b][i]x[/i][/b]');
        $this->assertSame([], $p->getErrors());
        $this->assertStringContainsString('<strong>', NoemaMarkupHtmlRenderer::render($nodes));
        $this->assertStringContainsString('<em>x</em>', NoemaMarkupHtmlRenderer::render($nodes));
    }

    public function test_link_with_text(): void
    {
        $p = new NoemaMarkupParser;
        $nodes = $p->parse('[link module=2 entity=16]якорь[/link]');
        $this->assertSame([], $p->getErrors());
        $html = NoemaMarkupHtmlRenderer::render($nodes);
        $this->assertStringContainsString('data-noema-module="2"', $html);
        $this->assertStringContainsString('data-noema-entity="16"', $html);
        $this->assertStringContainsString('якорь', $html);
    }

    public function test_escape_brackets(): void
    {
        $p = new NoemaMarkupParser;
        $nodes = $p->parse('\\[b\\]');
        $this->assertSame([], $p->getErrors());
        $this->assertSame('[b]', NoemaMarkupPlainRenderer::render($nodes));
    }

    public function test_validator_rejects_newline_inside_tag(): void
    {
        $errors = NoemaMarkupValidator::validate("[b]a\nb[/b]");
        $this->assertNotEmpty($errors);
    }
}
