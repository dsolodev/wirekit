<?php

declare(strict_types = 1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    private static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<model-property<User>, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => self::$password ??= bcrypt('password'),
            'remember_token'    => Str::random(10),
            'is_active'         => true,
        ];
    }

    /**
     * Indicate that the user may not access the panel.
     */
    public function inactive(): self
    {
        return $this->state(fn(array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user's email address should be unverified.
     */
    public function unverified(): self
    {
        return $this->state(fn(array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
