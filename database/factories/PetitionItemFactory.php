<?php

namespace Database\Factories;

use App\Models\Petition;
use App\Models\PetitionItem;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PetitionItem>
 */
class PetitionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petition_id' => Petition::factory(),
            'topic_id' => Topic::factory(),
            'kind' => PetitionItem::KIND_PEDIDO,
            'position' => 1,
            'title' => mb_strtoupper(fake()->sentence(4)),
            'group_name' => 'HORAS EXTRAS',
            'content' => fake()->paragraph(),
            'field_values' => null,
            'reviewed_at' => null,
        ];
    }

    public function preliminar(): static
    {
        return $this->state(fn (): array => ['kind' => PetitionItem::KIND_PRELIMINAR, 'group_name' => 'PRELIMINARES']);
    }

    public function reviewed(): static
    {
        return $this->state(fn (): array => ['reviewed_at' => now()]);
    }
}
