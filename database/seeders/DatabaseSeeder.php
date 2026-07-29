<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events are deliberately left enabled here. Promoting the first user to super admin is the
     * job of the `UserObserver`, and adding `WithoutModelEvents` to this seeder would silently skip it.
     */
    public function run(): void
    {
        $email = str(Config::string('app.name'))
            ->slug()
            ->prepend('admin@')
            ->append('.test')
            ->toString();

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name'     => 'Admin',
                'password' => 'password',
            ],
        );
    }
}
