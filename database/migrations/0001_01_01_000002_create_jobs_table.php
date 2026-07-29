<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->shouldCreate('jobs')) {
            Schema::create('jobs', function(Blueprint $table): void {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if ($this->shouldCreate('job_batches')) {
            Schema::create('job_batches', function(Blueprint $table): void {
                $table->string('id')->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if ($this->shouldCreate('failed_jobs')) {
            Schema::create('failed_jobs', function(Blueprint $table): void {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }

    /**
     * Determine whether a table should be created, skipping existing tables during local development.
     *
     * This supports iterating on a schema by dropping a single table, clearing its row from the
     * `migrations` table, and migrating again, without disturbing tables that still hold data.
     * Anywhere other than local development the answer is always yes, so an unexpected pre-existing
     * table fails loudly instead of silently leaving the schema unchanged.
     *
     * The `Schema::create()` calls above are deliberately written out in full rather than wrapped in a
     * helper: Larastan reads them statically to infer model properties for `checkModelProperties`.
     */
    private function shouldCreate(string $table): bool
    {
        if (! App::environment('local')) {
            return true;
        }

        return ! Schema::hasTable($table);
    }
};
