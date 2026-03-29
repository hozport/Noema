<?php

namespace App\Support;

final class FactionType
{
    public const RACE = 'race';

    public const PEOPLE = 'people';

    public const COUNTRY = 'country';

    public const TRADE_ASSOCIATION = 'trade_association';

    public const ORGANIZATION = 'organization';

    public const SECRET_ORGANIZATION = 'secret_organization';

    public const UNION = 'union';

    public const ALLIANCE = 'alliance';

    public const OTHER = 'other';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::RACE,
            self::PEOPLE,
            self::COUNTRY,
            self::TRADE_ASSOCIATION,
            self::ORGANIZATION,
            self::SECRET_ORGANIZATION,
            self::UNION,
            self::ALLIANCE,
            self::OTHER,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::RACE => 'Раса',
            self::PEOPLE => 'Народ',
            self::COUNTRY => 'Страна',
            self::TRADE_ASSOCIATION => 'Торговая ассоциация',
            self::ORGANIZATION => 'Организация',
            self::SECRET_ORGANIZATION => 'Тайная организация',
            self::UNION => 'Союз',
            self::ALLIANCE => 'Альянс',
            self::OTHER => 'Другое',
        ];
    }

    public static function label(string $key): string
    {
        return self::labels()[$key] ?? $key;
    }

    /**
     * Типы фракций, которые задаются отдельными полями биографии (не в «принадлежности»).
     *
     * @return list<string>
     */
    public static function biographyDedicatedTypes(): array
    {
        return [
            self::RACE,
            self::PEOPLE,
            self::COUNTRY,
        ];
    }

    /**
     * Имя внешнего ключа на таблице biographies для типа раса / народ / страна.
     * Для остальных типов фракций — null.
     */
    public static function biographyForeignKeyColumnForDedicatedType(string $type): ?string
    {
        return match ($type) {
            self::RACE => 'race_faction_id',
            self::PEOPLE => 'people_faction_id',
            self::COUNTRY => 'country_faction_id',
            default => null,
        };
    }
}
