<?php

use App\Support\Settings;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\DB;

test('a setting falls back to its documented default', function () {
    expect(DB::table('settings')->count())->toBe(0);

    expect(setting('default_currency'))->toBe('EGP')
        ->and(setting('default_locale'))->toBe('ar')
        ->and(setting('default_theme'))->toBe('system')
        ->and(setting('warn_threshold_percent'))->toBe(80)
        ->and(setting('fiscal_year_start_month'))->toBe(1)
        ->and(setting('global_monthly_max'))->toBeNull();
});

test('an unknown key falls back to the value the caller asked for', function () {
    expect(setting('not_a_real_setting'))->toBeNull()
        ->and(setting('not_a_real_setting', 'fallback'))->toBe('fallback');
});

test('a stored value replaces the default and keeps its type', function () {
    Settings::set('warn_threshold_percent', 65);
    Settings::set('company_name', 'Xtreme Egypt');
    Settings::set('global_monthly_max', 250000.75);

    expect(setting('warn_threshold_percent'))->toBe(65)
        ->and(setting('company_name'))->toBe('Xtreme Egypt')
        ->and(setting('global_monthly_max'))->toBe(250000.75);
});

test('arabic values survive the round trip unescaped', function () {
    Settings::set('company_name', 'إكستريم');

    expect(setting('company_name'))->toBe('إكستريم')
        ->and(DB::table('settings')->where('key', 'company_name')->value('value'))
        ->toBe('"إكستريم"');
});

test('saving twice updates the same row', function () {
    Settings::set('default_currency', 'USD');
    Settings::set('default_currency', 'SAR');

    expect(DB::table('settings')->where('key', 'default_currency')->count())->toBe(1)
        ->and(setting('default_currency'))->toBe('SAR');
});

test('a setting is filed under its group', function () {
    Settings::set('default_theme', 'dark');
    Settings::set('global_monthly_max', 1000);

    expect(DB::table('settings')->where('key', 'default_theme')->value('group'))->toBe('localisation')
        ->and(DB::table('settings')->where('key', 'global_monthly_max')->value('group'))->toBe('finance');
});

test('forgetting a setting restores its default', function () {
    Settings::set('warn_threshold_percent', 50);
    expect(setting('warn_threshold_percent'))->toBe(50);

    Settings::forget('warn_threshold_percent');

    expect(setting('warn_threshold_percent'))->toBe(80)
        ->and(DB::table('settings')->where('key', 'warn_threshold_percent')->exists())->toBeFalse();
});

test('a value explicitly cleared stays cleared', function () {
    Settings::set('company_logo', null);

    expect(setting('company_logo'))->toBeNull()
        ->and(DB::table('settings')->where('key', 'company_logo')->exists())->toBeTrue();
});

test('the seeder writes every known setting', function () {
    $this->seed(SettingsSeeder::class);

    expect(DB::table('settings')->count())->toBe(count(Settings::DEFAULTS));

    foreach (Settings::DEFAULTS as $key => $value) {
        expect(setting($key))->toBe($value);
    }
});

test('the effective map merges stored values over the defaults', function () {
    Settings::set('default_currency', 'USD');

    $effective = Settings::effective();

    expect($effective)->toHaveCount(count(Settings::DEFAULTS))
        ->and($effective['default_currency'])->toBe('USD')
        ->and($effective['default_locale'])->toBe('ar');
});
