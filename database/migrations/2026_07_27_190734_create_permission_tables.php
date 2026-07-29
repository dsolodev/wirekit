<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the tables behind `spatie/laravel-permission`, which Filament Shield builds on.
 *
 * This is a retyped version of the migration published by the package. The published file reads every
 * table and column name out of the config as `mixed`, which cannot pass this project's PHPStan settings,
 * so each lookup is resolved through a helper that returns a string and falls back to the package
 * default. Behaviour is unchanged; if you upgrade the package and its schema changes, re-publish the
 * stub and reapply this typing.
 */
return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams         = Config::boolean('permission.teams', false);
        $teamKey       = $this->name('permission.column_names.team_foreign_key', 'team_id');
        $morphKey      = $this->name('permission.column_names.model_morph_key', 'model_id');
        $rolePivot     = $this->name('permission.column_names.role_pivot_key', 'role_id');
        $permissionPvt = $this->name('permission.column_names.permission_pivot_key', 'permission_id');
        $permissions   = $this->name('permission.table_names.permissions', 'permissions');
        $roles         = $this->name('permission.table_names.roles', 'roles');
        $modelHasPerms = $this->name('permission.table_names.model_has_permissions', 'model_has_permissions');
        $modelHasRoles = $this->name('permission.table_names.model_has_roles', 'model_has_roles');
        $roleHasPerms  = $this->name('permission.table_names.role_has_permissions', 'role_has_permissions');

        // `permission.testing` is the package's own workaround for SQLite test databases.
        $needsTeamColumn = $teams || Config::boolean('permission.testing', false);

        if ($this->shouldCreate($permissions)) {
            Schema::create($permissions, function(Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();

                $table->unique(['name', 'guard_name']);
            });
        }

        if ($this->shouldCreate($roles)) {
            Schema::create($roles, function(Blueprint $table) use ($needsTeamColumn, $teamKey): void {
                $table->id();

                if ($needsTeamColumn) {
                    $table->unsignedBigInteger($teamKey)->nullable();
                    $table->index($teamKey, 'roles_team_foreign_key_index');
                }

                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();

                if ($needsTeamColumn) {
                    $table->unique([$teamKey, 'name', 'guard_name']);
                } else {
                    $table->unique(['name', 'guard_name']);
                }
            });
        }

        if ($this->shouldCreate($modelHasPerms)) {
            Schema::create($modelHasPerms, function(Blueprint $table) use ($teams, $teamKey, $morphKey, $permissionPvt, $permissions): void {
                $table->unsignedBigInteger($permissionPvt);

                $table->string('model_type');
                $table->unsignedBigInteger($morphKey);
                $table->index([$morphKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');

                $table->foreign($permissionPvt)
                    ->references('id')
                    ->on($permissions)
                    ->cascadeOnDelete();

                if ($teams) {
                    $table->unsignedBigInteger($teamKey);
                    $table->index($teamKey, 'model_has_permissions_team_foreign_key_index');

                    $table->primary(
                        [$teamKey, $permissionPvt, $morphKey, 'model_type'],
                        'model_has_permissions_permission_model_type_primary',
                    );
                } else {
                    $table->primary(
                        [$permissionPvt, $morphKey, 'model_type'],
                        'model_has_permissions_permission_model_type_primary',
                    );
                }
            });
        }

        if ($this->shouldCreate($modelHasRoles)) {
            Schema::create($modelHasRoles, function(Blueprint $table) use ($teams, $teamKey, $morphKey, $rolePivot, $roles): void {
                $table->unsignedBigInteger($rolePivot);

                $table->string('model_type');
                $table->unsignedBigInteger($morphKey);
                $table->index([$morphKey, 'model_type'], 'model_has_roles_model_id_model_type_index');

                $table->foreign($rolePivot)
                    ->references('id')
                    ->on($roles)
                    ->cascadeOnDelete();

                if ($teams) {
                    $table->unsignedBigInteger($teamKey);
                    $table->index($teamKey, 'model_has_roles_team_foreign_key_index');

                    $table->primary(
                        [$teamKey, $rolePivot, $morphKey, 'model_type'],
                        'model_has_roles_role_model_type_primary',
                    );
                } else {
                    $table->primary(
                        [$rolePivot, $morphKey, 'model_type'],
                        'model_has_roles_role_model_type_primary',
                    );
                }
            });
        }

        if ($this->shouldCreate($roleHasPerms)) {
            Schema::create($roleHasPerms, function(Blueprint $table) use ($rolePivot, $permissionPvt, $permissions, $roles): void {
                $table->unsignedBigInteger($permissionPvt);
                $table->unsignedBigInteger($rolePivot);

                $table->foreign($permissionPvt)
                    ->references('id')
                    ->on($permissions)
                    ->cascadeOnDelete();

                $table->foreign($rolePivot)
                    ->references('id')
                    ->on($roles)
                    ->cascadeOnDelete();

                $table->primary([$permissionPvt, $rolePivot], 'role_has_permissions_permission_id_role_id_primary');
            });
        }

        $this->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->name('permission.table_names.role_has_permissions', 'role_has_permissions'));
        Schema::dropIfExists($this->name('permission.table_names.model_has_roles', 'model_has_roles'));
        Schema::dropIfExists($this->name('permission.table_names.model_has_permissions', 'model_has_permissions'));
        Schema::dropIfExists($this->name('permission.table_names.roles', 'roles'));
        Schema::dropIfExists($this->name('permission.table_names.permissions', 'permissions'));
    }

    /**
     * Resolve a configured table or column name, falling back to the package default when it is unset.
     */
    private function name(string $key, string $default): string
    {
        $value = Config::get($key);

        return is_string($value) && $value !== '' ? $value : $default;
    }

    /**
     * Drop the package's cached permission map so the new tables are picked up immediately.
     */
    private function forgetCachedPermissions(): void
    {
        $store = $this->name('permission.cache.store', 'default');

        Cache::store($store === 'default' ? null : $store)
            ->forget($this->name('permission.cache.key', 'spatie.permission.cache'));
    }

    /**
     * Determine whether a table should be created, skipping existing tables during local development.
     *
     * This matches the behaviour of the other migrations in this project: dropping a single table and
     * migrating again is a convenient way to iterate locally, while anywhere else a pre-existing table
     * fails loudly rather than silently leaving the schema unchanged.
     */
    private function shouldCreate(string $table): bool
    {
        if (! App::environment('local')) {
            return true;
        }

        return ! Schema::hasTable($table);
    }
};
