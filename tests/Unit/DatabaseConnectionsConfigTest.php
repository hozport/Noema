<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Конфигурация подключений к БД из config/database.php
 */
class DatabaseConnectionsConfigTest extends TestCase
{
    /**
     * @return list<string>
     */
    private static function expectedDatabaseConnections(): array
    {
        return ['sqlite', 'mysql', 'mariadb', 'pgsql', 'sqlsrv'];
    }

    public function test_each_declared_connection_has_driver_and_database_or_url(): void
    {
        foreach (self::expectedDatabaseConnections() as $name) {
            $cfg = config("database.connections.{$name}");
            $this->assertIsArray($cfg, "Подключение «{$name}» должно быть массивом в config.");
            $this->assertNotEmpty($cfg['driver'] ?? null, "Подключение «{$name}»: нужен ключ driver.");
            $hasDatabase = filled($cfg['database'] ?? null);
            $hasUrl = filled($cfg['url'] ?? null);
            $this->assertTrue(
                $hasDatabase || $hasUrl,
                "Подключение «{$name}»: задайте database или url."
            );
        }
    }

    public function test_default_connection_resolves_and_supports_queries_in_tests(): void
    {
        $default = config('database.default');
        $this->assertNotEmpty($default);
        $one = DB::connection()->selectOne('select 1 as v');
        $this->assertSame(1, (int) $one->v);
    }
}
