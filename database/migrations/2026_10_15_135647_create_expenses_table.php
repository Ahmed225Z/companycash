<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            // Restricted on purpose: a category that still holds spending can
            // only be soft-deleted, never erased from under its expenses.
            $table->foreignId('expense_category_id')
                ->constrained('expense_categories')
                ->restrictOnDelete();

            $table->string('title', 200)->nullable();

            // Money is decimal, never float. Positive amounts are enforced by
            // the domain layer (P2.4) so the schema stays portable.
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('EGP');

            // The date the money was actually spent — every statistic in the
            // system is grouped by this column, not by created_at.
            $table->date('spent_at');

            $table->enum('payment_method', ['cash', 'bank', 'card', 'wallet', 'other'])->nullable();
            $table->string('reference', 100)->nullable();

            // Who the money went to. Carries the person's name for advances,
            // which is what the beneficiary ranking reports on.
            $table->string('beneficiary', 150)->nullable();

            $table->text('notes')->nullable();
            $table->string('attachment_path', 255)->nullable();

            // Keep the record if the account that touched it is ever removed.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['expense_category_id', 'spent_at']);
            $table->index('spent_at');
            $table->index('beneficiary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
