<?php

namespace App\Markup;

use App\Models\Worlds\World;
use App\Models\Worlds\WorldMapSprite;

final class NoemaMarkupValidator
{
    /**
     * Проверка разметки Noema
     *
     * При передаче мира дополнительно проверяются ссылки на объекты карты: объект должен существовать
     * в этом мире и иметь название, пригодное для ссылки (см. WorldMapSprite::titleQualifiesForMarkupEntityLink).
     *
     * @param  string  $content  Текст разметки
     * @param  World|null  $world  Мир для проверки ссылок на сущности; без мира проверяется только синтаксис
     * @return list<string> Список ошибок; пусто — ок.
     */
    public static function validate(string $content, ?World $world = null): array
    {
        $parser = new NoemaMarkupParser;
        $nodes = $parser->parse($content);
        $errors = array_merge($parser->getErrors(), self::validateNewlinesInPaired($nodes));
        $errors = array_merge($errors, self::validateModules($nodes));
        $errors = array_merge($errors, self::validateMapObjectLinks($nodes, $world));

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

    /**
     * Проверка ссылок на объекты карты по данным мира
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return list<string>
     */
    private static function validateMapObjectLinks(array $nodes, ?World $world): array
    {
        if ($world === null) {
            return [];
        }

        $refs = self::collectLinkRefs($nodes);
        $mapIds = [];
        foreach ($refs as $r) {
            if ((int) ($r['module'] ?? 0) === EntityModule::MapObject->value) {
                $mapIds[] = (int) ($r['entity'] ?? 0);
            }
        }
        $mapIds = array_values(array_unique(array_filter($mapIds, fn (int $id) => $id > 0)));
        if ($mapIds === []) {
            return [];
        }

        $sprites = WorldMapSprite::query()
            ->where('world_id', $world->id)
            ->whereIn('id', $mapIds)
            ->get(['id', 'title']);
        $byId = $sprites->keyBy('id');

        $errors = [];
        foreach ($mapIds as $id) {
            $sprite = $byId->get($id);
            if ($sprite === null) {
                $errors[] = 'Ссылка на объект карты (module=1, entity='.$id.'): объект не найден.';

                continue;
            }
            if (! WorldMapSprite::titleQualifiesForMarkupEntityLink($sprite->title)) {
                $errors[] = 'Ссылка на объект карты (module=1, entity='.$id.'): у объекта должно быть задано осмысленное название (не служебная подпись вроде «объект на карте #…»).';
            }
        }

        return $errors;
    }

    /**
     * Собирает все ссылки [link] из дерева узлов
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array{module: int, entity: int}>
     */
    private static function collectLinkRefs(array $nodes): array
    {
        $out = [];
        foreach ($nodes as $n) {
            if (($n['type'] ?? '') === 'link') {
                $out[] = [
                    'module' => (int) ($n['module'] ?? 0),
                    'entity' => (int) ($n['entity'] ?? 0),
                ];
            }
            if (! empty($n['children']) && is_array($n['children'])) {
                $out = array_merge($out, self::collectLinkRefs($n['children']));
            }
        }

        return $out;
    }
}
