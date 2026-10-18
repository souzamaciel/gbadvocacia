<?php

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role_id' => fn (): ?int => Role::administrator()?->id,
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A user whose profile grants only the given permissions.
     *
     * @param  list<Permission>  $permissions
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::factory()->withPermissions($permissions),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn (array $attributes) => ['must_change_password' => true]);
    }
}
