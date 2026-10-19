<?php

namespace Database\Factories;

use App\Models\AppointmentFormStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentFormStep>
 */
class AppointmentFormStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
            'icon' => 'clipboard-list',
            'position' => fake()->numberBetween(50, 99),
        ];
    }
}
