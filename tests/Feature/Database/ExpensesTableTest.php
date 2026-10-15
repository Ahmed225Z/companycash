<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('the table exists with every documented column', function () {
    expect(Schema::hasTable('expenses'))->toBeTrue();

    expect(Schema::hasColumns('expenses', [
        'id', 'expense_category_id', 'title', 'amount', 'currency', 'spent_at',
        'payment_method', 'reference', 'beneficiary', 'notes', 'attachment_path',
        'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
});

test('a new expense defaults to Egyptian pounds and no attribution', function () {
    $id = insertExpense();

    $expense = DB::table('expenses')->find($id);

    expect($expense->currency)->toBe('EGP')
        ->and($expense->title)->toBeNull()
        ->and($expense->payment_method)->toBeNull()
        ->and($expense->beneficiary)->toBeNull()
        ->and($expense->attachment_path)->toBeNull()
        ->and($expense->created_by)->toBeNull()
        ->and($expense->updated_by)->toBeNull()
        ->and($expense->deleted_at)->toBeNull();
});

test('an expense belongs to a category', function () {
    $category = insertCategory(['slug' => 'electricity']);
    $id = insertExpense(['expense_category_id' => $category, 'amount' => 9800.75]);

    expect(DB::table('expenses')->find($id)->expense_category_id)->toBe($category);
});

test('an expense cannot be filed under a category that does not exist', function () {
    insertExpense(['expense_category_id' => 9999]);
})->throws(QueryException::class);

test('a category holding expenses cannot be erased', function () {
    $category = insertCategory(['slug' => 'rent']);
    insertExpense(['expense_category_id' => $category]);

    DB::table('expense_categories')->where('id', $category)->delete();
})->throws(QueryException::class);

test('removing a user keeps their expenses and clears the attribution', function () {
    $user = User::factory()->create();
    $id = insertExpense(['created_by' => $user->id, 'updated_by' => $user->id]);

    $user->delete();

    $expense = DB::table('expenses')->find($id);

    expect($expense)->not->toBeNull()
        ->and($expense->created_by)->toBeNull()
        ->and($expense->updated_by)->toBeNull();
});

test('the amount is money, not a float', function () {
    $id = insertExpense(['amount' => 1250.5]);

    expect((float) DB::table('expenses')->find($id)->amount)->toBe(1250.50);

    $types = collect(Schema::getColumns('expenses'))->pluck('type_name', 'name');

    expect($types['amount'])->toBeIn(['decimal', 'numeric'])
        ->and($types['spent_at'])->toBe('date');
});

test('spending is indexed by category and date', function () {
    $indexed = collect(Schema::getIndexes('expenses'))
        ->map(fn (array $index) => implode(',', $index['columns']));

    expect($indexed)->toContain('expense_category_id,spent_at')
        ->and($indexed)->toContain('spent_at')
        ->and($indexed)->toContain('beneficiary');
});

test('expenses are grouped by the date the money was spent', function () {
    $category = insertCategory(['slug' => 'advances']);

    insertExpense(['expense_category_id' => $category, 'amount' => 1000, 'spent_at' => '2026-09-30', 'beneficiary' => 'أحمد أشرف']);
    insertExpense(['expense_category_id' => $category, 'amount' => 2500, 'spent_at' => '2026-10-05', 'beneficiary' => 'أحمد أشرف']);
    insertExpense(['expense_category_id' => $category, 'amount' => 700, 'spent_at' => '2026-10-20', 'beneficiary' => 'حسام']);

    $october = DB::table('expenses')
        ->whereBetween('spent_at', ['2026-10-01', '2026-10-31'])
        ->sum('amount');

    expect((float) $october)->toBe(3200.00);

    $perBeneficiary = DB::table('expenses')
        ->selectRaw('beneficiary, SUM(amount) as total')
        ->groupBy('beneficiary')
        ->pluck('total', 'beneficiary');

    expect((float) $perBeneficiary['أحمد أشرف'])->toBe(3500.00)
        ->and((float) $perBeneficiary['حسام'])->toBe(700.00);
});
