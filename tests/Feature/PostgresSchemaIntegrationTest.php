<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

/**
 * Проверка схемы PostgreSQL на подключении `pgsql` (пропускается, если нет доступа)
 */
class PostgresSchemaIntegrationTest extends TestCase
{
    private static function pgsqlReachable(): bool
    {
        try {
            DB::connection('pgsql')->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function test_pgsql_has_core_foreign_keys_when_reachable(): void
    {
        if (! self::pgsqlReachable()) {
            $this->markTestSkipped('Нет доступа к pgsql (CI или без Postgres в .env для этого подключения).');
        }

        $fkCount = (int) DB::connection('pgsql')->selectOne(
            "select count(*)::int as c from information_schema.table_constraints
             where table_schema = 'public' and constraint_type = 'FOREIGN KEY'"
        )->c;

        $this->assertGreaterThanOrEqual(
            40,
            $fkCount,
            'Ожидается полный набор внешних ключей после migrate по основным миграциям.'
        );

        $this->assertTrue(
            $this->foreignKeyExists('activity_logs', 'users', 'actor_id'),
            'activity_logs.actor_id → users'
        );
        $this->assertTrue(
            $this->foreignKeyExists('worlds', 'users', 'user_id'),
            'worlds.user_id → users'
        );
        $this->assertTrue(
            $this->foreignKeyExists('world_maps', 'worlds', 'world_id'),
            'world_maps.world_id → worlds'
        );
        $this->assertTrue(
            $this->foreignKeyExists('world_map_sprites', 'world_maps', 'world_map_id'),
            'world_map_sprites.world_map_id → world_maps'
        );
    }

    public function test_pgsql_has_indexes_on_public_tables_when_reachable(): void
    {
        if (! self::pgsqlReachable()) {
            $this->markTestSkipped('Нет доступа к pgsql.');
        }

        $idxCount = (int) DB::connection('pgsql')->selectOne(
            "select count(*)::int as c from pg_indexes where schemaname = 'public'"
        )->c;

        $this->assertGreaterThanOrEqual(
            55,
            $idxCount,
            'Ожидается набор индексов из миграций (включая уникальные, вторичные и lookup по FK).'
        );

        $this->assertTrue(
            Schema::connection('pgsql')->hasIndex('worlds', 'worlds_user_onoff_updated_idx'),
            'Индекс списка миров (user_id, onoff, updated_at) из миграции 2026_04_19_160000.'
        );

        $expectedLookupIndexes = [
            'world_maps_world_id_index',
            'world_map_sprites_world_map_id_index',
            'stories_world_id_index',
            'cards_story_id_index',
            'biographies_world_id_index',
            'creatures_world_id_index',
            'factions_world_id_index',
        ];
        foreach ($expectedLookupIndexes as $indexName) {
            $found = (int) DB::connection('pgsql')->selectOne(
                'select count(*)::int as c from pg_indexes where schemaname = ? and indexname = ?',
                ['public', $indexName]
            )->c;
            $this->assertSame(1, $found, 'Ожидается индекс '.$indexName.' (миграция 2026_04_23_120000).');
        }
    }

    public function test_pgsql_btree_indexes_are_valid_when_reachable(): void
    {
        if (! self::pgsqlReachable()) {
            $this->markTestSkipped('Нет доступа к pgsql.');
        }

        $invalid = DB::connection('pgsql')->select(
            <<<'SQL'
            select i.relname
            from pg_class i
            join pg_index ix on i.oid = ix.indexrelid
            join pg_class t on ix.indrelid = t.oid
            join pg_namespace n on t.relnamespace = n.oid
            where n.nspname = 'public'
              and t.relkind = 'r'
              and ix.indisvalid = false
            SQL
        );

        $this->assertSame([], $invalid, 'Недействительных btree-индексов в public быть не должно.');
    }

    /**
     * Проверяет наличие FK с таблицы $fromTable на $toTable по колонке $column
     */
    private function foreignKeyExists(string $fromTable, string $toTable, string $column): bool
    {
        $sql = <<<'SQL'
            select 1
            from information_schema.table_constraints tc
            join information_schema.key_column_usage kcu
              on tc.constraint_name = kcu.constraint_name and tc.table_schema = kcu.table_schema
            join information_schema.constraint_column_usage ccu
              on ccu.constraint_name = tc.constraint_name and ccu.table_schema = tc.table_schema
            where tc.constraint_type = 'FOREIGN KEY'
              and tc.table_schema = 'public'
              and tc.table_name = ?
              and kcu.column_name = ?
              and ccu.table_name = ?
            limit 1
            SQL;

        $rows = DB::connection('pgsql')->select($sql, [$fromTable, $column, $toTable]);

        return $rows !== [];
    }
}
