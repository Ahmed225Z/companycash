<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the table exists with every documented column', function () {
    expect(Schema::hasTable('expense_categories'))->toBeTrue();

    expect(Schema::hasColumns('expense_categories', [
        'id', 'parent_id', 'name_ar', 'name_en', 'slug', 'code', 'description',
        'icon', 'color', 'type', 'min_limit', 'max_limit', 'limit_period',
        'limit_includes_children', 'is_active', 'sort_order', 'depth', 'path',
        'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
});

test('a new category starts active, variable and monthly at the root', function () {
    $id = insertCategory();

    $category = DB::table('expense_categories')->find($id);

    expect($category->parent_id)->toBeNull()
        ->and($category->type)->toBe('variable')
        ->and($category->limit_period)->toBe('monthly')
        ->and((bool) $category->limit_includes_children)->toBeTrue()
        ->and((bool) $category->is_active)->toBeTrue()
        ->and((int) $category->sort_order)->toBe(0)
        ->and((int) $category->depth)->toBe(0)
        ->and($category->path)->toBe('')
        ->and($category->min_limit)->toBeNull()
        ->and($category->max_limit)->toBeNull()
        ->and($category->deleted_at)->toBeNull();
});

test('a category can nest under a parent three levels deep', function () {
    $advances = insertCategory(['name_ar' => 'السلف', 'name_en' => 'Advances', 'slug' => 'advances', 'type' => 'advance']);
    $ahmed = insertCategory(['name_ar' => 'أحمد أشرف', 'name_en' => 'Ahmed Ashraf', 'slug' => 'advances-ahmed', 'parent_id' => $advances, 'depth' => 1, 'path' => $advances]);
    $trip = insertCategory(['name_ar' => 'سلفة سفر', 'name_en' => 'Travel advance', 'slug' => 'advances-ahmed-travel', 'parent_id' => $ahmed, 'depth' => 2, 'path' => $advances.'/'.$ahmed]);

    $leaf = DB::table('expense_categories')->find($trip);

    expect($leaf->parent_id)->toBe($ahmed)
        ->and((int) $leaf->depth)->toBe(2)
        ->and($leaf->path)->toBe($advances.'/'.$ahmed);

    // The path column is what makes a subtree a single query.
    $subtree = DB::table('expense_categories')
        ->where('path', $advances)
        ->orWhere('path', 'like', $advances.'/%')
        ->count();

    expect($subtree)->toBe(2);
});

test('deleting a parent leaves its children behind as roots', function () {
    $parent = insertCategory(['slug' => 'subscriptions', 'type' => 'subscription']);
    $child = insertCategory(['slug' => 'subscriptions-claude', 'parent_id' => $parent]);

    DB::table('expense_categories')->where('id', $parent)->delete();

    expect(DB::table('expense_categories')->find($child)->parent_id)->toBeNull();
});

test('a category cannot point at a parent that does not exist', function () {
    insertCategory(['parent_id' => 9999]);
})->throws(QueryException::class);

test('slugs are unique', function () {
    insertCategory(['slug' => 'water']);
    insertCategory(['slug' => 'water']);
})->throws(QueryException::class);

test('codes are unique but optional', function () {
    insertCategory(['slug' => 'water', 'code' => null]);
    insertCategory(['slug' => 'rent', 'code' => null]);

    expect(DB::table('expense_categories')->whereNull('code')->count())->toBe(2);

    insertCategory(['slug' => 'internet', 'code' => 'ACC-100']);
    insertCategory(['slug' => 'fuel', 'code' => 'ACC-100']);
})->throws(QueryException::class);

test('limits are stored as money, not floats', function () {
    $id = insertCategory(['min_limit' => 1500.5, 'max_limit' => 12345678901.99]);

    $category = DB::table('expense_categories')->find($id);

    expect((float) $category->min_limit)->toBe(1500.50)
        ->and((float) $category->max_limit)->toBe(12345678901.99);

    // decimal(15,2) on MySQL, numeric on SQLite — never a float column.
    $types = collect(Schema::getColumns('expense_categories'))
        ->pluck('type_name', 'name');

    expect($types['min_limit'])->toBeIn(['decimal', 'numeric'])
        ->and($types['max_limit'])->toBeIn(['decimal', 'numeric']);
});
