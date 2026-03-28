<?php

namespace App\Support;

use Illuminate\Http\Request;

final class SiteLocales
{
    /** @var list<string> */
    public const SUPPORTED = ['ru', 'en', 'fr', 'es'];

    public const DEFAULT = 'ru';

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && in_array($locale, self::SUPPORTED, true);
    }

    /**
     * Pick the best locale from the Accept-Language header, or default.
     */
    public static function negotiateFromRequest(Request $request): string
    {
        $accept = $request->header('Accept-Language');
        if (! is_string($accept) || $accept === '') {
            return self::DEFAULT;
        }

        foreach (explode(',', $accept) as $part) {
            $segment = trim(explode(';', $part)[0]);
            if ($segment === '') {
                continue;
            }
            $primary = strtolower(substr($segment, 0, 2));
            if (in_array($primary, self::SUPPORTED, true)) {
                return $primary;
            }
        }

        return self::DEFAULT;
    }
}
