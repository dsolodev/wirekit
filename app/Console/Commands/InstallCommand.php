<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PDO;
use PDOException;

#[Signature('wirekit:install {--force : Run even when the application is not in the local environment}')]
#[Description('Prepare the environment file, create the database, then migrate and seed it')]
final class InstallCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! App::environment('local') && ! (bool) $this->option('force')) {
            $this->components->error('wirekit:install only runs in the local environment. Pass --force to override.');

            return self::FAILURE;
        }

        $this->ensureEnvironmentFileExists();

        $name     = $this->resolveApplicationName();
        $database = $this->resolveDatabaseName();

        $this->setEnvironmentValue('APP_NAME', $name);
        $this->setEnvironmentValue('DB_DATABASE', $database);

        // The environment file is only read when the framework boots, so mirror the new values onto the
        // active configuration to keep this run consistent with the file we just wrote.
        Config::set('app.name', $name);

        $this->components->info(sprintf('Installing %s.', $name));

        $this->generateApplicationKey();

        if (! $this->prepareDatabase($database)) {
            return self::FAILURE;
        }

        $this->call('migrate', ['--force' => true]);

        $this->generateAuthorization();

        $this->seedAdministrator();

        return self::SUCCESS;
    }

    /**
     * Generate Shield's policies and permissions, and grant them all to the super admin role.
     *
     * This runs before the administrator is seeded so that the super admin role already carries every
     * permission by the time the first user is promoted into it. Existing policies are left alone, so
     * re-running the installer never overwrites a policy you have since customised.
     */
    private function generateAuthorization(): void
    {
        $this->call('shield:generate', [
            '--all'                      => true,
            '--panel'                    => Filament::getDefaultPanel()->getId(),
            '--ignore-existing-policies' => true,
        ]);
    }

    /**
     * Copy the example environment file when the application does not have one yet.
     */
    private function ensureEnvironmentFileExists(): void
    {
        if (File::exists(base_path('.env'))) {
            return;
        }

        File::copy(base_path('.env.example'), base_path('.env'));
    }

    /**
     * Build a human readable application name from the project directory, so "my-app" becomes "My App".
     */
    private function resolveApplicationName(): string
    {
        $name = Str::of(basename(base_path()))
            ->replaceMatches('/[^a-zA-Z0-9]+/', ' ')
            ->squish()
            ->title()
            ->toString();

        return $name === '' ? 'WireKit' : $name;
    }

    /**
     * Build a valid database identifier from the project directory, so "my-app" becomes "my_app".
     */
    private function resolveDatabaseName(): string
    {
        $database = Str::of(basename(base_path()))
            ->lower()
            ->replaceMatches('/[^a-z0-9_]+/', '_')
            ->trim('_')
            ->substr(0, 64)
            ->toString();

        return $database === '' ? 'wirekit' : $database;
    }

    /**
     * Generate an application key, unless one has already been set.
     */
    private function generateApplicationKey(): void
    {
        $key = Config::get('app.key');

        if (is_string($key) && $key !== '') {
            return;
        }

        $this->call('key:generate', ['--force' => true]);
    }

    /**
     * Point the application at the resolved database, creating it first when MySQL is in use.
     */
    private function prepareDatabase(string $database): bool
    {
        $connection = Config::string('database.default');

        Config::set(sprintf('database.connections.%s.database', $connection), $database);
        DB::purge($connection);

        if ($connection !== 'mysql') {
            return true;
        }

        return $this->createMySqlDatabase($connection, $database);
    }

    /**
     * Create the MySQL database when it does not exist yet.
     */
    private function createMySqlDatabase(string $connection, string $database): bool
    {
        $socket = $this->connectionValue($connection, 'unix_socket');

        $dsn = $socket !== ''
            ? sprintf('mysql:unix_socket=%s', $socket)
            : sprintf(
                'mysql:host=%s;port=%s',
                $this->connectionValue($connection, 'host', '127.0.0.1'),
                $this->connectionValue($connection, 'port', '3306'),
            );

        try {
            $connector = new PDO(
                $dsn,
                $this->connectionValue($connection, 'username', 'root'),
                $this->connectionValue($connection, 'password'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );

            $connector->exec(sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET `%s` COLLATE `%s`',
                $this->escapeIdentifier($database),
                $this->escapeIdentifier($this->connectionValue($connection, 'charset', 'utf8mb4')),
                $this->escapeIdentifier($this->connectionValue($connection, 'collation', 'utf8mb4_unicode_ci')),
            ));
        } catch (PDOException $pdoException) {
            $this->components->error(sprintf('Could not connect to MySQL: %s', $pdoException->getMessage()));
            $this->components->warn('Create the database yourself, then run: php artisan migrate --seed');

            return false;
        }

        $this->components->info(sprintf('Database `%s` is ready.', $database));

        return true;
    }

    /**
     * Seed the first administrator, unless the application already has users.
     */
    private function seedAdministrator(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $this->call('db:seed', ['--force' => true]);

        $administrator = User::query()->oldest('id')->first();

        if ($administrator instanceof User) {
            $this->components->info(sprintf('Administrator ready: %s / password', $administrator->email));
        }
    }

    /**
     * Read a database connection setting as a string, falling back when it is not set.
     */
    private function connectionValue(string $connection, string $key, string $default = ''): string
    {
        $value = Config::get(sprintf('database.connections.%s.%s', $connection, $key));

        if (! is_scalar($value)) {
            return $default;
        }

        $value = (string) $value;

        return $value === '' ? $default : $value;
    }

    /**
     * Strip backticks so a value can be safely wrapped in them.
     */
    private function escapeIdentifier(string $value): string
    {
        return str_replace('`', '', $value);
    }

    /**
     * Write a value to the environment file, replacing the existing entry when there is one.
     */
    private function setEnvironmentValue(string $key, string $value): void
    {
        $path     = base_path('.env');
        $contents = File::get($path);
        $entry    = $key.'='.(str_contains($value, ' ') ? '"'.$value.'"' : $value);
        $pattern  = '/^'.preg_quote($key, '/').'=.*$/m';
        $count    = 0;

        $replaced = preg_replace($pattern, $entry, $contents, 1, $count);

        File::put($path, ($count > 0 && is_string($replaced)) ? $replaced : $contents.PHP_EOL.$entry);
    }
}
