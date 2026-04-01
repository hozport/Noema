<?php

namespace App\Services\Markup;

use App\Markup\EntityModule;
use App\Models\Bestiary\Creature;
use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Models\Timeline\TimelineLine;
use App\Models\Worlds\World;

final class EntityPreviewResolver
{
    /**
     * @param  list<array{module: int, entity: int}>  $refs
     * @return array<string, array{title: string, description: string, image_url: ?string}>
     */
    public function resolveBatch(World $world, array $refs): array
    {
        $world->loadMissing('user');

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
                EntityModule::MapStub => null,
                EntityModule::TimelineLine => $this->fillTimeline($world, $ids, $out),
                EntityModule::BestiaryCreature => $this->fillCreatures($world, $ids, $out),
                EntityModule::Biography => $this->fillBiographies($world, $ids, $out),
                EntityModule::Faction => $this->fillFactions($world, $ids, $out),
            };
        }

        return $out;
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     */
    private function fillTimeline(World $world, array $ids, array &$out): void
    {
        $lines = TimelineLine::query()
            ->where('world_id', $world->id)
            ->whereIn('id', $ids)
            ->get(['id', 'name']);

        foreach ($lines as $line) {
            $key = EntityModule::TimelineLine->value.':'.$line->id;
            $out[$key] = [
                'title' => (string) $line->name,
                'description' => '',
                'image_url' => null,
            ];
        }
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     */
    private function fillCreatures(World $world, array $ids, array &$out): void
    {
        $rows = Creature::query()
            ->where('world_id', $world->id)
            ->whereIn('id', $ids)
            ->get();

        foreach ($rows as $c) {
            $key = EntityModule::BestiaryCreature->value.':'.$c->id;
            $out[$key] = [
                'title' => (string) $c->name,
                'description' => (string) ($c->short_description ?? ''),
                'image_url' => $c->imageUrl(),
            ];
        }
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     */
    private function fillBiographies(World $world, array $ids, array &$out): void
    {
        $rows = Biography::query()
            ->where('world_id', $world->id)
            ->whereIn('id', $ids)
            ->get();

        foreach ($rows as $b) {
            $key = EntityModule::Biography->value.':'.$b->id;
            $out[$key] = [
                'title' => (string) $b->name,
                'description' => (string) ($b->short_description ?? ''),
                'image_url' => $b->imageUrl(),
            ];
        }
    }

    /**
     * @param  list<int>  $ids
     * @param  array<string, array{title: string, description: string, image_url: ?string}>  $out
     */
    private function fillFactions(World $world, array $ids, array &$out): void
    {
        $rows = Faction::query()
            ->where('world_id', $world->id)
            ->whereIn('id', $ids)
            ->get();

        foreach ($rows as $f) {
            $key = EntityModule::Faction->value.':'.$f->id;
            $out[$key] = [
                'title' => (string) $f->name,
                'description' => (string) ($f->short_description ?? ''),
                'image_url' => $f->imageUrl(),
            ];
        }
    }
}
