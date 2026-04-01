<?php

namespace App\Markup;

/**
 * Плоский текст без тегов (превью, поиск, PDF как plain).
 */
final class NoemaMarkupPlainRenderer
{
    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    public static function render(array $nodes): string
    {
        $out = '';
        foreach ($nodes as $n) {
            $out .= self::renderNode($n);
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $n
     */
    private static function renderNode(array $n): string
    {
        return match ($n['type']) {
            'text' => (string) ($n['text'] ?? ''),
            'bold', 'italic', 'underline', 'strike', 'link' => self::render($n['children'] ?? []),
            default => '',
        };
    }
}
