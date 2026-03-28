<?php

namespace App\Support;

/**
 * Индексация существ по первой букве названия для латинского (A–Z + 1–9)
 * и кириллического (А–Я + 1–9) алфавитов.
 * Алфавиты независимы: кириллическая «А» и латинская «A» — разные группы;
 * имя не «перекидывается» из одной раскладки в другую.
 */
class BestiaryAlphabet
{
    public const SCRIPT_LAT = 'lat';

    public const SCRIPT_CYR = 'cyr';

    /** Прочие символы / другая раскладка (не буква текущего алфавита). */
    public const OTHER_BUCKET = '…';

    /** @return list<string> */
    public static function navLetters(string $script): array
    {
        if ($script === self::SCRIPT_CYR) {
            return array_merge(self::cyrillicLetters(), ['0-9', self::OTHER_BUCKET]);
        }

        return array_merge(range('A', 'Z'), ['0-9', self::OTHER_BUCKET]);
    }

    /** @return list<string> */
    public static function cyrillicLetters(): array
    {
        return [
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
            'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я',
        ];
    }

    public static function bucketFor(string $name, string $script): string
    {
        $first = self::firstSignificantChar($name);
        if ($first === null || $first === '') {
            return self::OTHER_BUCKET;
        }

        if (self::isDigitChar($first)) {
            return '0-9';
        }

        if ($script === self::SCRIPT_LAT) {
            return self::bucketLatinFirst($first);
        }

        return self::bucketCyrillicFirst($first);
    }

    public static function defaultLetter(string $script): string
    {
        return $script === self::SCRIPT_CYR ? 'А' : 'A';
    }

    private static function firstSignificantChar(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }
        $trimmed = trim($name);
        if ($trimmed === '') {
            return null;
        }

        return mb_strtoupper(mb_substr($trimmed, 0, 1), 'UTF-8');
    }

    private static function isDigitChar(string $ch): bool
    {
        return preg_match('/^\d$/u', $ch) === 1;
    }

    private static function bucketLatinFirst(string $ch): string
    {
        if (self::isLatinAsciiLetter($ch)) {
            return $ch;
        }

        return self::OTHER_BUCKET;
    }

    private static function bucketCyrillicFirst(string $ch): string
    {
        if (self::isCyrillicLetter($ch)) {
            return $ch;
        }

        return self::OTHER_BUCKET;
    }

    private static function isLatinAsciiLetter(string $ch): bool
    {
        return preg_match('/^[A-Z]$/u', $ch) === 1;
    }

    private static function isCyrillicLetter(string $ch): bool
    {
        return in_array($ch, self::cyrillicLetters(), true);
    }
}
