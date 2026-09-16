<?php

namespace App\Support;

final class SettingsAllowlist
{
    public const SITE_NAME = 'site.name';

    public const CURRENCY_DEFAULT = 'currency.default';

    public const FALLBACK_NAME = 'Watch';

    public const FALLBACK_CODE = 'VND';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [self::SITE_NAME, self::CURRENCY_DEFAULT];
    }

    public static function isAllowed(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    public static function publicName(?array $value): string
    {
        $vi = is_string($value['vi'] ?? null) ? trim($value['vi']) : '';

        return $vi === '' ? self::FALLBACK_NAME : $vi;
    }

    /**
     * @param  array<string, mixed>|null  $value
     */
    public static function publicCode(?array $value): string
    {
        $code = is_string($value['code'] ?? null) ? strtoupper(trim($value['code'])) : '';

        return preg_match('/^[A-Z]{3}$/', $code) === 1 ? $code : self::FALLBACK_CODE;
    }
}
