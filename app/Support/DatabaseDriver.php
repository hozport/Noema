<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Сведения о драйвере БД по умолчанию
 *
 * Нужен для ветвления запросов (например ILIKE только на PostgreSQL), не ломая SQLite в тестах.
 */
final class DatabaseDriver
{
    /**
     * Текущее подключение по умолчанию — PostgreSQL
     */
    public static function defaultIsPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /**
     * Шаблон `LIKE` / `ILIKE` для поиска подстроки (экранирует `%`, `_`, `\`).
     *
     * @param  string  $substring  Подстрока без шаблонных символов
     * @return string Значение вида `%…%` для биндинга в `where(..., 'ilike', ...)`
     */
    public static function likeContainsPattern(string $substring): string
    {
        return '%'.addcslashes($substring, '%_\\').'%';
    }
}
