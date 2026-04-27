<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeDocument>
 */
class KnowledgeDocumentFactory extends Factory
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
            'created_by_user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(4, true),
            'status' => 'pending',
            'chunk_count' => 0,
            'metadata' => null,
        ];
    }
}
