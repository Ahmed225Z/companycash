<?php

namespace Database\Seeders;

use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Writes every known setting with its default value, so the settings screen
 * has rows to edit instead of an empty table.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = collect(Settings::DEFAULTS)
            ->map(fn (mixed $value, string $key) => [
                'key' => $key,
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'group' => Settings::GROUPS[$key] ?? 'general',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        DB::table('settings')->upsert($rows, ['key'], ['group', 'updated_at']);

        Settings::flush();
    }
}
