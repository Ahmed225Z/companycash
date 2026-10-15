<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * System settings, stored one row per key and read through a single cached
 * map. Every key the system understands has a default here, so a fresh
 * installation works before anything has been saved.
 */
class Settings
{
    public const CACHE_KEY = 'settings.map';

    /**
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'company_name' => 'Xtreme',
        'company_logo' => null,

        'default_currency' => 'EGP',
        'default_locale' => 'ar',
        'default_theme' => 'system',

        'fiscal_year_start_month' => 1,
        // No global ceiling until someone sets one.
        'global_monthly_max' => null,
        // Share of a limit that triggers the "approaching the limit" warning.
        'warn_threshold_percent' => 80,
    ];

    /**
     * @var array<string, string>
     */
    public const GROUPS = [
        'company_name' => 'general',
        'company_logo' => 'general',

        'default_currency' => 'localisation',
        'default_locale' => 'localisation',
        'default_theme' => 'localisation',

        'fiscal_year_start_month' => 'finance',
        'global_monthly_max' => 'finance',
        'warn_threshold_percent' => 'finance',
    ];

    /**
     * Read a setting, falling back to its documented default, then to the
     * caller's own fallback for keys the system does not know about.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $stored = static::all();

        if (array_key_exists($key, $stored)) {
            return $stored[$key];
        }

        return self::DEFAULTS[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $now = now();

        DB::table('settings')->upsert(
            [[
                'key' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'group' => self::GROUPS[$key] ?? 'general',
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['key'],
            ['value', 'group', 'updated_at'],
        );

        static::flush();
    }

    /**
     * Drop a stored value so the key goes back to its default.
     */
    public static function forget(string $key): void
    {
        DB::table('settings')->where('key', $key)->delete();

        static::flush();
    }

    /**
     * Every known setting with its effective value.
     *
     * @return array<string, mixed>
     */
    public static function effective(): array
    {
        return array_merge(self::DEFAULTS, static::all());
    }

    /**
     * Only the values that have actually been stored.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => DB::table('settings')
            ->pluck('value', 'key')
            ->map(fn (?string $value) => $value === null ? null : json_decode($value, true))
            ->all());
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
