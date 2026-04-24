<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'session_id' => fake()->uuid(),
            'status' => fake()->randomElement(['ai_handled', 'human_required', 'human_active', 'closed']),
            'assigned_user_id' => null,
            'last_message_at' => now(),
        ];
    }
}
