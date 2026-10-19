<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\AppointmentFormQuestion;
use App\Models\AppointmentFormStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppointmentFormQuestion>
 */
class AppointmentFormQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_form_step_id' => AppointmentFormStep::factory(),
            'key' => 'pergunta_'.fake()->unique()->numberBetween(1000, 999999),
            'type' => QuestionType::ShortText,
            'label' => fake()->sentence(4),
            'width' => 'full',
            'required' => false,
            'send_to_ai' => true,
            'is_system' => false,
            'position' => fake()->numberBetween(50, 99),
        ];
    }

    /**
     * @param  list<string>  $choices
     */
    public function choices(QuestionType $type, array $choices, bool $allowOther = false): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'options' => ['choices' => $choices, 'allow_other' => $allowOther],
        ]);
    }
}
