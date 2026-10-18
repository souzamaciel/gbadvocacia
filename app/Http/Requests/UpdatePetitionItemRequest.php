<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePetitionItemRequest extends FormRequest
{
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
            'field_values' => ['sometimes', 'array'],
            'field_values.*' => ['nullable', 'string', 'max:2000'],
            'content' => ['sometimes', 'string', 'min:1'],
            'reviewed' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'field_values.*.max' => 'Cada informação deve ter no máximo 2000 caracteres.',
            'content.min' => 'O texto do tópico não pode ficar vazio.',
        ];
    }
}
