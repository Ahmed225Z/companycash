<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('action', ['created', 'updated', 'deleted', 'restored', 'imported', 'exported']);

            // Nullable because an export or an import is not tied to one row.
            $table->nullableMorphs('subject');

            // Only what changed: ['field' => ['from' => x, 'to' => y]].
            $table->json('changes')->nullable();

            $table->string('ip', 45)->nullable();

            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
