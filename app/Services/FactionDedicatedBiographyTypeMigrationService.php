<?php

namespace App\Services;

use App\Models\Biography\Biography;
use App\Models\Faction\Faction;
use App\Support\FactionType;
use Illuminate\Support\Facades\DB;

/**
 * При смене типа фракции переносит привязки биографий в нужные поля (раса / народ / страна)
 * и снимает устаревшие внешние ключи на эту фракцию.
 */
class FactionDedicatedBiographyTypeMigrationService
{
    public function migrate(Faction $faction, string $oldType, string $newType): void
    {
        if ($oldType === $newType) {
            return;
        }

        $oldCol = FactionType::biographyForeignKeyColumnForDedicatedType($oldType);
        $newCol = FactionType::biographyForeignKeyColumnForDedicatedType($newType);

        $memberBioIds = DB::table('faction_biography')
            ->where('faction_id', $faction->id)
            ->pluck('biography_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $bios = Biography::query()
            ->where('world_id', $faction->world_id)
            ->where(function ($q) use ($faction, $memberBioIds) {
                $q->whereIn('id', $memberBioIds)
                    ->orWhere('race_faction_id', $faction->id)
                    ->orWhere('people_faction_id', $faction->id)
                    ->orWhere('country_faction_id', $faction->id);
            })
            ->get();

        $flags = [];
        foreach ($bios as $bio) {
            $inPivot = in_array((int) $bio->id, $memberBioIds, true);
            $hadOldDedicatedFk = $oldCol !== null
                && $bio->{$oldCol} !== null
                && (int) $bio->{$oldCol} === (int) $faction->id;
            $flags[$bio->id] = [
                'inPivot' => $inPivot,
                'hadOldDedicatedFk' => $hadOldDedicatedFk,
            ];
        }

        foreach ($bios as $bio) {
            foreach (['race_faction_id', 'people_faction_id', 'country_faction_id'] as $col) {
                if ($bio->{$col} !== null && (int) $bio->{$col} === (int) $faction->id) {
                    $bio->{$col} = null;
                }
            }

            $f = $flags[$bio->id];
            if ($newCol !== null && ($f['hadOldDedicatedFk'] || $f['inPivot'])) {
                $prev = $bio->{$newCol};
                if ($prev !== null && (int) $prev !== (int) $faction->id) {
                    $bio->membershipFactions()->detach((int) $prev);
                }
                $bio->{$newCol} = $faction->id;
            }

            $bio->save();
        }
    }
}
