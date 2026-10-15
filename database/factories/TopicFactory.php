<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'title' => mb_strtoupper(fake()->unique()->sentence(4)),
            'content' => fake()->paragraphs(2, true),
            'notes' => null,
            'sort_order' => fake()->numberBetween(1, 50),
            'active' => true,
        ];
    }
}
