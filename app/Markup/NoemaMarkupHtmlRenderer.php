<?php

namespace App\Markup;

/**
 * Безопасный HTML из AST (только наши теги, текст экранируется).
 */
final class NoemaMarkupHtmlRenderer
{
    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    public static function render(array $nodes): string
    {
        $html = '';
        foreach ($nodes as $n) {
            $html .= self::renderNode($n);
        }

        return $html;
    }

    /**
     * @param  array<string, mixed>  $n
     */
    private static function renderNode(array $n): string
    {
        return match ($n['type']) {
            'text' => htmlspecialchars((string) $n['text'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'bold' => '<strong>'.self::render($n['children'] ?? []).'</strong>',
            'italic' => '<em>'.self::render($n['children'] ?? []).'</em>',
            'underline' => '<u>'.self::render($n['children'] ?? []).'</u>',
            'strike' => '<s>'.self::render($n['children'] ?? []).'</s>',
            'link' => self::renderLink($n),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $n
     */
    private static function renderLink(array $n): string
    {
        $module = (int) ($n['module'] ?? 0);
        $entity = (int) ($n['entity'] ?? 0);
        $inner = self::render($n['children'] ?? []);
        $attrs = ' class="noema-entity-link" data-noema-module="'.htmlspecialchars((string) $module, ENT_QUOTES, 'UTF-8').'" data-noema-entity="'.htmlspecialchars((string) $entity, ENT_QUOTES, 'UTF-8').'" href="#" role="button"';

        return '<a'.$attrs.'>'.$inner.'</a>';
    }
}
