<?php

namespace App\Support;

/**
 * Степень родства для связи «текущая биография → родственник» (pivot biography_relative).
 */
final class BiographyKinship
{
    public const HUSBAND = 'husband';

    public const WIFE = 'wife';

    public const MOTHER = 'mother';

    public const FATHER = 'father';

    public const GRANDFATHER = 'grandfather';

    public const GRANDMOTHER = 'grandmother';

    public const SON = 'son';

    public const DAUGHTER = 'daughter';

    public const NEPHEW = 'nephew';

    public const NIECE = 'niece';

    public const GRANDSON = 'grandson';

    public const GRANDDAUGHTER = 'granddaughter';

    public const BROTHER = 'brother';

    public const SISTER = 'sister';

    public const UNCLE = 'uncle';

    public const AUNT = 'aunt';

    public const CUSTOM = 'custom';

    /**
     * @return list<string>
     */
    public static function presetKeys(): array
    {
        return [
            self::HUSBAND,
            self::WIFE,
            self::MOTHER,
            self::FATHER,
            self::GRANDFATHER,
            self::GRANDMOTHER,
            self::SON,
            self::DAUGHTER,
            self::NEPHEW,
            self::NIECE,
            self::GRANDSON,
            self::GRANDDAUGHTER,
            self::BROTHER,
            self::SISTER,
            self::UNCLE,
            self::AUNT,
            self::CUSTOM,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::HUSBAND => 'Муж',
            self::WIFE => 'Жена',
            self::MOTHER => 'Мать',
            self::FATHER => 'Отец',
            self::GRANDFATHER => 'Дед',
            self::GRANDMOTHER => 'Бабка',
            self::SON => 'Сын',
            self::DAUGHTER => 'Дочь',
            self::NEPHEW => 'Племянник',
            self::NIECE => 'Племянница',
            self::GRANDSON => 'Внук',
            self::GRANDDAUGHTER => 'Внучка',
            self::BROTHER => 'Брат',
            self::SISTER => 'Сестра',
            self::UNCLE => 'Дядя',
            self::AUNT => 'Тётя',
            self::CUSTOM => 'Свой вариант',
        ];
    }

    public static function displayLabel(?string $kinship, ?string $kinshipCustom): string
    {
        if ($kinship === null || $kinship === '') {
            return '';
        }
        if ($kinship === self::CUSTOM) {
            return $kinshipCustom !== null && $kinshipCustom !== '' ? (string) $kinshipCustom : '';
        }

        return self::labels()[$kinship] ?? $kinship;
    }
}
