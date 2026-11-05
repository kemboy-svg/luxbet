<?php

namespace Database\Factories;

use App\Domain\Players\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fictional test players only.
 *
 * @extends Factory<PlayerProfile>
 */
class PlayerProfileFactory extends Factory
{
    protected $model = PlayerProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'username' => 'player_'.fake()->unique()->bothify('????##'),
            'country_code' => null,
            'preferred_locale' => 'en',
            'age_confirmed_at' => now(),
            'terms_accepted_at' => now(),
            'terms_version' => config('luxbet.terms_version'),
        ];
    }
}
