<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTopicRequest extends FormRequest
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
            'group_id' => ['required', 'exists:groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'group_id.required' => 'Selecione o grupo do tópico.',
            'group_id.exists' => 'O grupo selecionado não existe.',
            'title.required' => 'O título do tópico é obrigatório.',
            'title.max' => 'O título deve ter no máximo 255 caracteres.',
            'content.required' => 'O conteúdo do tópico é obrigatório.',
        ];
    }
}
