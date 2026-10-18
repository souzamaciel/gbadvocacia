<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SavePetitionRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'preliminares' => ['sometimes', 'array'],
            'preliminares.*' => ['integer', 'exists:topics,id'],
            'topics' => ['sometimes', 'array'],
            'topics.*' => ['integer', 'exists:topics,id'],
            'action' => ['sometimes', 'in:save,review'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.max' => 'O nome do rascunho deve ter no máximo 255 caracteres.',
            'preliminares.*.exists' => 'Uma das preliminares escolhidas não existe mais.',
            'topics.*.exists' => 'Um dos pedidos escolhidos não existe mais.',
        ];
    }
}
