<?php

namespace Tests\Unit;

use App\Services\OpenAI\ModelOptions;
use Tests\TestCase;

class ModelOptionsTest extends TestCase
{
    public function test_reasoning_models_get_reasoning_effort_and_no_temperature(): void
    {
        config(['services.openai.model' => 'gpt-6-luna', 'services.openai.reasoning_effort' => 'medium']);

        $this->assertSame(['model' => 'gpt-6-luna', 'reasoning' => ['effort' => 'medium']], ModelOptions::payload());
    }

    public function test_older_models_keep_temperature_zero(): void
    {
        config(['services.openai.model' => 'gpt-4.1']);

        $this->assertSame(['model' => 'gpt-4.1', 'temperature' => 0], ModelOptions::payload());
        $this->assertSame(['model' => 'gpt-4.1'], ModelOptions::payload(false));
    }

    public function test_model_families_are_recognized(): void
    {
        foreach (['gpt-5', 'gpt-5.4-mini', 'gpt-6.1-sol', 'o3', 'o4-mini'] as $model) {
            $this->assertTrue(ModelOptions::isReasoningModel($model), $model);
        }

        foreach (['gpt-4.1', 'gpt-4o-mini', 'gpt-3.5-turbo'] as $model) {
            $this->assertFalse(ModelOptions::isReasoningModel($model), $model);
        }
    }
}
