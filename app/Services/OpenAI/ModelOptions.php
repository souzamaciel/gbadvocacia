<?php

namespace App\Services\OpenAI;

/**
 * Model settings shared by every request to the AI.
 *
 * Reasoning models (GPT-5 and later, o-series) take a reasoning effort and reject a
 * custom temperature; older models (GPT-4.x) keep temperature 0 for steady answers.
 */
class ModelOptions
{
    /**
     * @return array<string, mixed>
     */
    public static function payload(bool $deterministic = true): array
    {
        $model = (string) config('services.openai.model');

        if (self::isReasoningModel($model)) {
            return [
                'model' => $model,
                'reasoning' => ['effort' => (string) config('services.openai.reasoning_effort', 'medium')],
            ];
        }

        return $deterministic ? ['model' => $model, 'temperature' => 0] : ['model' => $model];
    }

    public static function isReasoningModel(string $model): bool
    {
        return (bool) preg_match('/^(gpt-[5-9]|gpt-\d{2,}|o\d)/i', $model);
    }
}
