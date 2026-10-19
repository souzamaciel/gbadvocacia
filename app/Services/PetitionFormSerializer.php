<?php

namespace App\Services;

/**
 * Builds the case data sent to the AI from the attendance form answers.
 *
 * The qualification fields keep their fixed keys (they fill the petition header).
 * Every other answer goes as a readable question/answer pair in "ficha_atendimento",
 * following the form configured in "Ficha de atendimento". Answers marked as
 * "não enviar à IA" (e.g. the GOV password) never leave the system.
 */
class PetitionFormSerializer
{
    /**
     * @param  array<string, mixed>  $raw  submitted form answers
     * @param  array<int, array{sort_order: int, topic_id: int, title: string, content: string}>  $topicos
     * @param  array<int, array{sort_order: int, topic_id: int, title: string, content: string}>  $preliminares
     * @return array<string, mixed>
     */
    public static function normalizeFormData(array $raw, array $topicos = [], array $preliminares = []): array
    {
        $form = app(AppointmentForm::class);

        return [
            ...$form->qualification($raw),
            'ficha_atendimento' => $raw === [] ? [] : $form->answersForAi($raw),
            'topicos_selecionados' => $topicos,
            'preliminares_selecionadas' => $preliminares,
        ];
    }
}
