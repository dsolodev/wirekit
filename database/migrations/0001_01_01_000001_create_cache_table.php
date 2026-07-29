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
        if ($this->shouldCreate('cache')) {
            Schema::create('cache', function(Blueprint $table): void {
                $table->string('key')->primary();
                $table->mediumText('value');
                $table->bigInteger('expiration')->index();
            });
        }

        if ($this->shouldCreate('cache_locks')) {
            Schema::create('cache_locks', function(Blueprint $table): void {
                $table->string('key')->primary();
                $table->string('owner');
                $table->bigInteger('expiration')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
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
