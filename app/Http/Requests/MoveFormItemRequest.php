<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MoveFormItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('appointment_form.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'direction' => ['required', 'in:up,down'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'direction.required' => 'Informe para onde mover.',
            'direction.in' => 'Informe para onde mover.',
        ];
    }
}
