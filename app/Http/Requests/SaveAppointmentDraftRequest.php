<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveAppointmentDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('appointments.create') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'topics' => ['nullable', 'array'],
            'topics.*' => ['integer'],
            'preliminares' => ['nullable', 'array'],
            'preliminares.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'topics.*.integer' => 'Pedido inválido.',
            'preliminares.*.integer' => 'Preliminar inválida.',
        ];
    }
}
