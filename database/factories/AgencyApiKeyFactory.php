<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgencyApiKey>
 */
class AgencyApiKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rawKey = 'wk_'.fake()->bothify('##############################');

        return [
            'agency_id' => Agency::factory(),
            'name' => fake()->randomElement(['Widget Key', 'Production Key', 'Website Key']),
            'key_hash' => hash('sha256', $rawKey),
            'is_active' => true,
            'last_used_at' => null,
        ];
    }
}
