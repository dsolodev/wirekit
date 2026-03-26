<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->create([
            'name'  => 'Admin',
            'email' => str(Config::string('app.name'))
                ->lower()
                ->prepend('admin@')
                ->append('.test')
                ->toString(),
            'password' => 'password',
        ]);
    }
}
