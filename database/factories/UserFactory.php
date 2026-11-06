<?php

namespace Database\Factories;

use App\Domain\Identity\Authorization\Role;
use App\Domain\Identity\Enums\UserStatus;
use App\Domain\Players\Models\PlayerProfile;
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
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
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
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * A registered player with a profile and the player role.
     */
    public function player(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->assignRole(Role::Player->value);
            PlayerProfile::factory()->for($user)->create();
        });
    }

    /**
     * A staff member with the given role. Staff need confirmed 2FA to use
     * the admin area, so combine with withTwoFactor() where needed.
     */
    public function staff(Role $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }

    public function withStatus(UserStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }
}
