<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Вторичные индексы по внешним ключам для выборок по миру / истории / карте
 *
 * В PostgreSQL внешний ключ сам по себе не создаёт B-tree на дочерней колонке;
 * без индекса страдают списки карт, историй, карточек и модулей по `world_id` / `story_id` / `world_map_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('world_maps', 'world_id', 'world_maps_world_id_index');
        $this->addIndexIfMissing('world_map_sprites', 'world_map_id', 'world_map_sprites_world_map_id_index');
        $this->addIndexIfMissing('stories', 'world_id', 'stories_world_id_index');
        $this->addIndexIfMissing('cards', 'story_id', 'cards_story_id_index');
        $this->addIndexIfMissing('biographies', 'world_id', 'biographies_world_id_index');
        $this->addIndexIfMissing('creatures', 'world_id', 'creatures_world_id_index');
        $this->addIndexIfMissing('factions', 'world_id', 'factions_world_id_index');
    }

    public function down(): void
    {
        $pairs = [
            ['factions', 'factions_world_id_index'],
            ['creatures', 'creatures_world_id_index'],
            ['biographies', 'biographies_world_id_index'],
            ['cards', 'cards_story_id_index'],
            ['stories', 'stories_world_id_index'],
            ['world_map_sprites', 'world_map_sprites_world_map_id_index'],
            ['world_maps', 'world_maps_world_id_index'],
        ];
        foreach ($pairs as [$tbl, $idx]) {
            if (Schema::hasIndex($tbl, $idx)) {
                Schema::table($tbl, function (Blueprint $blueprint) use ($idx): void {
                    $blueprint->dropIndex($idx);
                });
            }
        }
    }

    /**
     * Создаёт B-tree по одной колонке, если индекса с таким имени ещё нет
     *
     * @param  string  $table  Имя таблицы
     * @param  string  $column  Колонка
     * @param  string  $indexName  Имя индекса
     */
    private function addIndexIfMissing(string $table, string $column, string $indexName): void
    {
        if (Schema::hasIndex($table, $indexName)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($column, $indexName): void {
            $blueprint->index($column, $indexName);
        });
    }
};
