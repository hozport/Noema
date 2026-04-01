<?php

namespace App\Markup;

final class NoemaMarkupValidator
{
    /**
     * @return list<string> Список ошибок; пусто — ок.
     */
    public static function validate(string $content): array
    {
        $parser = new NoemaMarkupParser;
        $nodes = $parser->parse($content);
        $errors = array_merge($parser->getErrors(), self::validateNewlinesInPaired($nodes));
        $errors = array_merge($errors, self::validateModules($nodes));

        return array_values(array_unique($errors));
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<string>
     */
    private static function validateNewlinesInPaired(array $nodes, bool $insidePaired = false): array
    {
        $errors = [];
        foreach ($nodes as $n) {
            $type = $n['type'] ?? '';
            if ($type === 'text') {
                $text = (string) ($n['text'] ?? '');
                if ($insidePaired && preg_match('/[\r\n]/', $text)) {
                    $errors[] = 'Перенос строки внутри тега не допускается (используйте двойной перевод между абзацами снаружи тегов).';
                }

                continue;
            }
            if (in_array($type, ['bold', 'italic', 'underline', 'strike', 'link'], true)) {
                $errors = array_merge(
                    $errors,
                    self::validateNewlinesInPaired($n['children'] ?? [], true)
                );
            }
        }

        return $errors;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<string>
     */
    private static function validateModules(array $nodes): array
    {
        $errors = [];
        foreach ($nodes as $n) {
            if (($n['type'] ?? '') === 'link') {
                $m = (int) ($n['module'] ?? 0);
                if (EntityModule::tryFrom($m) === null) {
                    $errors[] = 'Неизвестный модуль ссылки (module='.$m.').';
                }
            }
            if (! empty($n['children']) && is_array($n['children'])) {
                $errors = array_merge($errors, self::validateModules($n['children']));
            }
        }

        return $errors;
    }
}
