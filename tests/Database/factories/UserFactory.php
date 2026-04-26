<?php

declare(strict_types=1);

namespace Aristonis\LaravelAuthentication\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Aristonis\LaravelAuthentication\Tests\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Aristonis\LaravelAuthentication\Tests\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password123'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => json_encode(['code1', 'code2', 'code3']),
        ]);
    }
}
