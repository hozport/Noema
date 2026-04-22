<?php

namespace App\Services\Markup;

use App\Markup\EntityModule;
use App\Models\Bestiary\Creature;
use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;
use App\Models\Worlds\WorldMapSprite;
use Illuminate\Support\Facades\Cache;

/**
 * Разрешение превью сущностей для подсказок разметки
 *
 * Использует кеш по паре (мир, модуль, id) и короткий кеш целого батча по нормализованным refs.
 */
final class EntityPreviewResolver
{
    /**
     * @param  list<array{module: int, entity: int}>  $refs
     * @return array<string, array{title: string, description: string, image_url: ?string}>
     */
    public function resolveBatch(World $world, array $refs): array
    {
        $world->loadMissing('user');

        $fingerprint = $this->normalizedRefsFingerprint($refs);
        if ($fingerprint !== '') {
            $batchKey = sprintf('markup:resolve_batch:world:%d:%s', $world->id, $fingerprint);
            $cached = Cache::get($batchKey);
            if (is_array($cached)) {
                return $cached;
            }
        } else {
            $batchKey = null;
        }

        $byModule = [];
        foreach ($refs as $r) {
            $m = (int) ($r['module'] ?? 0);
            $e = (int) ($r['entity'] ?? 0);
            if ($e < 1) {
                continue;
            }
            $mod = EntityModule::tryFrom($m);
            if ($mod === null) {
                continue;
            }
            $byModule[$m][] = $e;
        }

        $out = [];

        foreach ($byModule as $moduleId => $ids) {
            $ids = array_values(array_unique($ids));
            $mod = EntityModule::tryFrom((int) $moduleId);
            if ($mod === null) {
                continue;
            }
            match ($mod) {
                EntityModule::MapObject => $this->fillMapSprites($world, $ids, $out),
                EntityModule::TimelineLine => $this->fillTimeline($world, $ids, $out),
                EntityModule::BestiaryCreature => $this->fillCreatures($world, $ids, $out),
                EntityModule::Biography => $this->fillBiographies($world, $ids, $out),
                EntityModule::Faction => $this->fillFactions($world, $ids, $out),
            };
        }

        if ($batchKey !== null && $out !== []) {
            Cache::put(
                $batchKey,
                $out,
                (int) config('markup.resolve_batch_ttl_seconds', 45)
            );
        }

        return $out;
    }

    /**
     * Отпечаток нормализованного набора ссылок для ключа батча
     *
     * @param  list<array{module?: int, entity?: int}>  $refs
     * @return string Пустая строка, если валидных ссылок нет
     */
    private function normalizedRefsFingerprint(array $refs): string
    {
        $rows = [];
        foreach ($refs as $r) {
            $m = (int) ($r['module'] ?? 0);
            $e = (int) ($r['entity'] ?? 0);
            if ($e < 1 || EntityModule::tryFrom($m) === null) {
                continue;
            }
            $rows[$m.':'.$e] = ['m' => $m, 'e' => $e];
        }
        if ($rows === []) {
            return '';
        }
        ksort($rows);

        return hash('sha256', json_encode(array_values($rows)));
    }

    /**
     * Ключ кеша превью одной сущности
     *
     * @param  int  $worldId  Идентификатор мира
     * @param  int  $moduleValue  Значение `EntityModule`
     * @param  int  $entityId  Идентификатор сущности в модуле
     */
    private function previewEntityCacheKey(int $worldId, int $moduleValue, int $entityId): string
    {
        return sprintf('markup:preview:world:%d:module:%d:entity:%d', $worldId, $moduleValue, $entityId);
    }

    /**
     * Подмешивает превью из кеша и догружает пропуски из БД
     *
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     * @param  callable(World, list<int>): array<int, array{title: string, description: string, image_url: ?string}>  $loadMissing  id сущности => превью
     */
    private function attachCachedEntityPreviews(
        World $world,
        EntityModule $module,
        array $ids,
        array &$out,
        callable $loadMissing
    ): void {
        $ttl = (int) config('markup.preview_ttl_seconds', 300);
        $modVal = $module->value;
        $missing = [];
        foreach (array_values(array_unique($ids)) as $id) {
            $ck = $this->previewEntityCacheKey((int) $world->id, $modVal, $id);
            $cached = Cache::get($ck);
            if (is_array($cached)) {
                $out[$modVal.':'.$id] = $cached;
            } else {
                $missing[] = $id;
            }
        }
        if ($missing === []) {
            return;
        }

        $fetched = $loadMissing($world, $missing);
        foreach ($fetched as $entityId => $preview) {
            $ck = $this->previewEntityCacheKey((int) $world->id, $modVal, (int) $entityId);
            Cache::put($ck, $preview, $ttl);
            $out[$modVal.':'.(int) $entityId] = $preview;
        }
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     */
    private function fillMapSprites(World $world, array $ids, array &$out): void
    {
        $this->attachCachedEntityPreviews(
            $world,
            EntityModule::MapObject,
            $ids,
            $out,
            function (World $world, array $missingIds): array {
                $rows = WorldMapSprite::query()
                    ->whereHas('worldMap', fn ($q) => $q->where('world_id', $world->id))
                    ->whereIn('id', $missingIds)
                    ->get(['id', 'sprite_path', 'title', 'description']);

                $byId = [];
                foreach ($rows as $sprite) {
                    $title = $sprite->title !== null && trim($sprite->title) !== ''
                        ? trim($sprite->title)
                        : 'Объект на карте';
                    $desc = $sprite->description !== null ? trim((string) $sprite->description) : '';
                    $byId[(int) $sprite->id] = [
                        'title' => $title,
                        'description' => $desc,
                        'image_url' => $this->mapSpritePublicUrl((string) $sprite->sprite_path),
                    ];
                }

                return $byId;
            }
        );
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     */
    private function fillTimeline(World $world, array $ids, array &$out): void
    {
        $this->attachCachedEntityPreviews(
            $world,
            EntityModule::TimelineLine,
            $ids,
            $out,
            function (World $world, array $missingIds): array {
                $lines = TimelineLine::query()
                    ->where('world_id', $world->id)
                    ->whereIn('id', $missingIds)
                    ->get(['id', 'name']);

                $byId = [];
                foreach ($lines as $line) {
                    $byId[(int) $line->id] = [
                        'title' => (string) $line->name,
                        'description' => '',
                        'image_url' => null,
                    ];
                }

                return $byId;
            }
        );
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     */
    private function fillCreatures(World $world, array $ids, array &$out): void
    {
        $this->attachCachedEntityPreviews(
            $world,
            EntityModule::BestiaryCreature,
            $ids,
            $out,
            function (World $world, array $missingIds): array {
                $rows = Creature::query()
                    ->where('world_id', $world->id)
                    ->whereIn('id', $missingIds)
                    ->get();

                $byId = [];
                foreach ($rows as $c) {
                    $byId[(int) $c->id] = [
                        'title' => (string) $c->name,
                        'description' => (string) ($c->short_description ?? ''),
                        'image_url' => $c->imageUrl(),
                    ];
                }

                return $byId;
            }
        );
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     */
    private function fillBiographies(World $world, array $ids, array &$out): void
    {
        $this->attachCachedEntityPreviews(
            $world,
            EntityModule::Biography,
            $ids,
            $out,
            function (World $world, array $missingIds): array {
                $rows = Biography::query()
                    ->where('world_id', $world->id)
                    ->whereIn('id', $missingIds)
                    ->get();

                $byId = [];
                foreach ($rows as $b) {
                    $byId[(int) $b->id] = [
                        'title' => (string) $b->name,
                        'description' => (string) ($b->short_description ?? ''),
                        'image_url' => $b->imageUrl(),
                    ];
                }

                return $byId;
            }
        );
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     */
    private function fillFactions(World $world, array $ids, array &$out): void
    {
        $this->attachCachedEntityPreviews(
            $world,
            EntityModule::Faction,
            $ids,
            $out,
            function (World $world, array $missingIds): array {
                $rows = Faction::query()
                    ->where('world_id', $world->id)
                    ->whereIn('id', $missingIds)
                    ->get();

                $byId = [];
                foreach ($rows as $f) {
                    $byId[(int) $f->id] = [
                        'title' => (string) $f->name,
                        'description' => (string) ($f->short_description ?? ''),
                        'image_url' => $f->imageUrl(),
                    ];
                }

                return $byId;
            }
        );
    }

    /**
     * Публичный URL файла спрайта в `public/sprites`.
     */
    private function mapSpritePublicUrl(string $relativePath): ?string
    {
        $s = str_replace('\\', '/', trim($relativePath));
        if ($s === '' || str_contains($s, '..')) {
            return null;
        }
        foreach (explode('/', $s) as $part) {
            if ($part === '..') {
                return null;
            }
        }
        $parts = explode('/', $s, 2);
        if (count($parts) !== 2) {
            return null;
        }

        return url('/sprites/'.rawurlencode($parts[0]).'/'.rawurlencode($parts[1]));
    }
}
