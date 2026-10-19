<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveFormStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('appointment_form.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'description' => trim((string) $this->input('description')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Informe o nome do assunto.',
            'title.max' => 'O nome do assunto deve ter no máximo 80 caracteres.',
            'description.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'icon.regex' => 'Escolha um ícone da lista.',
        ];
    }
}
