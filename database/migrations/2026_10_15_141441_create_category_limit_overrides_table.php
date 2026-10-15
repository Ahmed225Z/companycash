<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_limit_overrides', function (Blueprint $table) {
            $table->id();

            // An override replaces the category's own limits for one period —
            // a bigger electricity budget in summer, a one-off advance ceiling.
            $table->foreignId('expense_category_id')
                ->constrained('expense_categories')
                ->cascadeOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->decimal('min_limit', 15, 2)->nullable();
            $table->decimal('max_limit', 15, 2)->nullable();
            $table->string('reason', 255)->nullable();

            $table->timestamps();

            // Overlapping periods are rejected by the limit service (P2.3);
            // this index is what makes "the override in force today" cheap.
            $table->index(['expense_category_id', 'period_start', 'period_end'], 'limit_overrides_period_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_limit_overrides');
    }
};
