<?php

namespace Database\Factories;

use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Petition>
 */
class PetitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Petição inicial — '.fake()->name(),
            'status' => Petition::STATUS_DRAFT,
        ];
    }

    public function generated(): static
    {
        return $this->state(fn (): array => [
            'status' => Petition::STATUS_GENERATED,
            'file_name' => 'peticao-inicial.docx',
            'file_path' => 'petitions/1/peticao-inicial.docx',
            'generated_at' => now(),
        ]);
    }
}
