<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();

            // A category may nest under another one, up to three levels deep
            // (depth 0..2). Detaching a parent keeps its children as roots
            // rather than deleting a whole branch by accident.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('expense_categories')
                ->nullOnDelete();

            $table->string('name_ar', 150);
            $table->string('name_en', 150);
            $table->string('slug', 160)->unique();
            $table->string('code', 30)->nullable()->unique();
            $table->text('description')->nullable();

            $table->string('icon', 50)->nullable();
            $table->string('color', 9)->nullable();

            $table->enum('type', ['fixed', 'variable', 'advance', 'subscription', 'other'])
                ->default('variable');

            // Spending limits. Both are optional: a category without a maximum
            // simply never raises the over-limit warning.
            $table->decimal('min_limit', 15, 2)->nullable();
            $table->decimal('max_limit', 15, 2)->nullable();
            $table->enum('limit_period', ['daily', 'weekly', 'monthly', 'yearly'])
                ->default('monthly');
            $table->boolean('limit_includes_children')->default(true);

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            // Maintained by the tree service: depth is the nesting level and
            // path is the slash-separated chain of ancestor ids ("1/5/9"),
            // which turns subtree lookups into a single LIKE query.
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('path', 255)->default('');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'sort_order']);
            $table->index('path');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
