<?php

namespace App\Http\Requests;

use App\Services\RichText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SuggestTopicVariablesRequest extends FormRequest
{
    /**
     * An empty editor counts as empty.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('content')) && RichText::toPlainText($this->input('content')) === '') {
            $this->merge(['content' => '']);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:30000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Cole o texto da petição antes de pedir sugestões à IA.',
            'content.max' => 'O texto é longo demais para a análise. Divida em tópicos menores.',
        ];
    }
}
