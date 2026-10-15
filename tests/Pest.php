<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Insert a category straight through the query builder.
 *
 * The Eloquent models arrive in P1.4, so the schema phases are verified
 * against the database itself.
 */
function insertCategory(array $attributes = []): int
{
    return DB::table('expense_categories')->insertGetId(array_merge([
        'name_ar' => 'الكهرباء',
        'name_en' => 'Electricity',
        'slug' => 'category-'.uniqid(),
    ], $attributes));
}

function insertExpense(array $attributes = []): int
{
    return DB::table('expenses')->insertGetId(array_merge([
        'expense_category_id' => $attributes['expense_category_id'] ?? insertCategory(),
        'amount' => 1250.00,
        'spent_at' => '2026-10-01',
    ], $attributes));
}
