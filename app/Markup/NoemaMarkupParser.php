<?php

namespace App\Markup;

/**
 * Разбор разметки Noema: [b], [i], [u], [s], [link module=M entity=E]…[/link], экранирование \.
 */
final class NoemaMarkupParser
{
    private string $s = '';

    private int $len = 0;

    private int $pos = 0;

    /** @var list<string> */
    private array $errors = [];

    /**
     * @return list<array<string, mixed>>
     */
    public function parse(string $input): array
    {
        $this->s = $input;
        $this->len = strlen($input);
        $this->pos = 0;
        $this->errors = [];

        $nodes = $this->parseUntil(null);

        return $this->mergeAdjacentText($nodes);
    }

    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseUntil(?string $close): array
    {
        $nodes = [];
        while ($this->pos < $this->len) {
            if ($close !== null) {
                $chk = $this->checkClosing($close);
                if ($chk === true) {
                    return $nodes;
                }
                if ($chk === 'mismatch') {
                    $this->errors[] = 'Неверный закрывающий тег (ожидался [/'.$close.']).';
                    $nodes[] = ['type' => 'text', 'text' => '['];
                    $this->pos++;

                    continue;
                }
            }

            if ($this->peek() === '\\') {
                $this->pos++;
                if ($this->pos >= $this->len) {
                    $nodes[] = ['type' => 'text', 'text' => '\\'];
                    break;
                }
                $nodes[] = ['type' => 'text', 'text' => $this->s[$this->pos]];
                $this->pos++;

                continue;
            }

            if ($this->peek() === '[') {
                $opened = $this->tryParseOpenTag();
                if ($opened !== null) {
                    $nodes[] = $opened;

                    continue;
                }
                $nodes[] = ['type' => 'text', 'text' => '['];
                $this->pos++;

                continue;
            }

            $nodes[] = ['type' => 'text', 'text' => $this->peek()];
            $this->pos++;
        }

        if ($close !== null) {
            $this->errors[] = 'Не найден закрывающий тег [/'.$close.'].';
        }

        return $nodes;
    }

    private function peek(): string
    {
        return $this->s[$this->pos] ?? '';
    }

    /**
     * @return true|'mismatch'|false
     */
    private function checkClosing(string $expected): bool|string
    {
        if ($this->peek() !== '[') {
            return false;
        }
        $sub = substr($this->s, $this->pos);
        if (preg_match('/^\[\/('.preg_quote($expected, '/').')\]/', $sub, $m)) {
            $this->pos += strlen($m[0]);

            return true;
        }
        if (preg_match('/^\[\//', $sub)) {
            return 'mismatch';
        }

        return false;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryParseOpenTag(): ?array
    {
        $sub = substr($this->s, $this->pos);
        if (str_starts_with($sub, '[/')) {
            return null;
        }

        if (preg_match('/^\[link\s+module\s*=\s*(\d+)\s+entity\s*=\s*(\d+)\s*\]/', $sub, $m)) {
            $this->pos += strlen($m[0]);
            $module = (int) $m[1];
            $entity = (int) $m[2];
            $children = $this->parseUntil('link');

            return [
                'type' => 'link',
                'module' => $module,
                'entity' => $entity,
                'children' => $this->mergeAdjacentText($children),
            ];
        }

        $map = [
            'b' => 'bold',
            'i' => 'italic',
            'u' => 'underline',
            's' => 'strike',
        ];
        foreach ($map as $tag => $type) {
            if (preg_match('/^\['.preg_quote($tag, '/').'\]/', $sub, $m)) {
                $this->pos += strlen($m[0]);
                $children = $this->parseUntil($tag);

                return [
                    'type' => $type,
                    'children' => $this->mergeAdjacentText($children),
                ];
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $nodes
     * @return list<array<string, mixed>>
     */
    private function mergeAdjacentText(array $nodes): array
    {
        $out = [];
        foreach ($nodes as $n) {
            if ($n['type'] === 'text' && $n['text'] === '') {
                continue;
            }
            if ($n['type'] === 'text' && $out !== []) {
                $last = $out[count($out) - 1];
                if ($last['type'] === 'text') {
                    $last['text'] .= $n['text'];
                    $out[count($out) - 1] = $last;

                    continue;
                }
            }
            $out[] = $n;
        }

        return $out;
    }
}
