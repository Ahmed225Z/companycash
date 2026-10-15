<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('every support table exists with its documented columns', function () {
    expect(Schema::hasColumns('category_limit_overrides', [
        'id', 'expense_category_id', 'period_start', 'period_end',
        'min_limit', 'max_limit', 'reason', 'created_at', 'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('settings', ['id', 'key', 'value', 'group']))->toBeTrue();

    expect(Schema::hasColumns('import_batches', [
        'id', 'user_id', 'file_name', 'type', 'status',
        'rows_total', 'rows_imported', 'rows_failed', 'errors',
    ]))->toBeTrue();

    expect(Schema::hasColumns('activity_logs', [
        'id', 'user_id', 'action', 'subject_type', 'subject_id', 'changes', 'ip',
    ]))->toBeTrue();
});

test('an override raises a category limit for one period only', function () {
    $category = insertCategory(['slug' => 'electricity', 'max_limit' => 10000]);

    DB::table('category_limit_overrides')->insert([
        'expense_category_id' => $category,
        'period_start' => '2026-07-01',
        'period_end' => '2026-09-30',
        'max_limit' => 18000,
        'reason' => 'صيف',
    ]);

    $inForce = DB::table('category_limit_overrides')
        ->where('expense_category_id', $category)
        ->where('period_start', '<=', '2026-08-15')
        ->where('period_end', '>=', '2026-08-15')
        ->value('max_limit');

    expect((float) $inForce)->toBe(18000.00);

    $outsidePeriod = DB::table('category_limit_overrides')
        ->where('expense_category_id', $category)
        ->where('period_start', '<=', '2026-11-15')
        ->where('period_end', '>=', '2026-11-15')
        ->count();

    expect($outsidePeriod)->toBe(0);
});

test('overrides disappear with the category they belong to', function () {
    $category = insertCategory(['slug' => 'water']);

    DB::table('category_limit_overrides')->insert([
        'expense_category_id' => $category,
        'period_start' => '2026-01-01',
        'period_end' => '2026-12-31',
        'max_limit' => 5000,
    ]);

    DB::table('expense_categories')->where('id', $category)->delete();

    expect(DB::table('category_limit_overrides')->count())->toBe(0);
});

test('a setting key is stored once', function () {
    DB::table('settings')->insert(['key' => 'default_currency', 'value' => '"EGP"']);
    DB::table('settings')->insert(['key' => 'default_currency', 'value' => '"USD"']);
})->throws(QueryException::class);

test('an import batch starts pending with nothing counted', function () {
    $id = DB::table('import_batches')->insertGetId([
        'file_name' => 'expenses-october.xlsx',
        'type' => 'expenses',
    ]);

    $batch = DB::table('import_batches')->find($id);

    expect($batch->status)->toBe('pending')
        ->and((int) $batch->rows_total)->toBe(0)
        ->and((int) $batch->rows_imported)->toBe(0)
        ->and((int) $batch->rows_failed)->toBe(0)
        ->and($batch->errors)->toBeNull()
        ->and($batch->user_id)->toBeNull();
});

test('an import batch keeps the reason each row was rejected', function () {
    $errors = [
        ['row' => 4, 'message' => 'المبلغ غير صالح'],
        ['row' => 9, 'message' => 'البند غير موجود'],
    ];

    $id = DB::table('import_batches')->insertGetId([
        'file_name' => 'expenses.csv',
        'type' => 'expenses',
        'status' => 'failed',
        'rows_total' => 10,
        'rows_imported' => 8,
        'rows_failed' => 2,
        'errors' => json_encode($errors, JSON_UNESCAPED_UNICODE),
    ]);

    $stored = json_decode(DB::table('import_batches')->find($id)->errors, true);

    expect($stored)->toBe($errors);
});

test('an activity entry records who changed what', function () {
    $user = User::factory()->create();
    $category = insertCategory(['slug' => 'rent']);

    $id = DB::table('activity_logs')->insertGetId([
        'user_id' => $user->id,
        'action' => 'updated',
        'subject_type' => 'expense_category',
        'subject_id' => $category,
        'changes' => json_encode(['max_limit' => ['from' => 10000, 'to' => 12000]]),
        'ip' => '192.168.1.10',
    ]);

    $entry = DB::table('activity_logs')->find($id);

    expect($entry->action)->toBe('updated')
        ->and((int) $entry->subject_id)->toBe($category)
        ->and(json_decode($entry->changes, true))->toBe(['max_limit' => ['from' => 10000, 'to' => 12000]]);

    // Exports and imports are not tied to a single row.
    DB::table('activity_logs')->insert(['action' => 'exported']);

    expect(DB::table('activity_logs')->whereNull('subject_id')->count())->toBe(1);
});

test('the log outlives the account that wrote it', function () {
    $user = User::factory()->create();

    DB::table('activity_logs')->insert(['user_id' => $user->id, 'action' => 'created']);
    DB::table('import_batches')->insert(['user_id' => $user->id, 'file_name' => 'x.csv', 'type' => 'backup']);

    $user->delete();

    expect(DB::table('activity_logs')->count())->toBe(1)
        ->and(DB::table('activity_logs')->first()->user_id)->toBeNull()
        ->and(DB::table('import_batches')->first()->user_id)->toBeNull();
});
