<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('file_name', 255);
            $table->enum('type', ['expenses', 'categories', 'backup']);

            // previewed is the dry run: rows were parsed and counted, but
            // nothing has been written yet.
            $table->enum('status', ['pending', 'previewed', 'imported', 'failed'])->default('pending');

            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);

            // Row number and reason for every rejected row, so the user can
            // download an error report instead of guessing.
            $table->json('errors')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
