<?php

namespace App\Http\Requests;

use App\Services\RichText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTopicRequest extends FormRequest
{
    /**
     * Keep only the formatting the Word generator supports; an empty editor counts as empty.
     */
    protected function prepareForValidation(): void
    {
        if (! is_string($this->input('content'))) {
            return;
        }

        $content = RichText::isHtml($this->input('content'))
            ? RichText::sanitize($this->input('content'))
            : $this->input('content');

        $this->merge(['content' => RichText::toPlainText($content) === '' ? '' : $content]);
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
            'group_id' => ['required', 'exists:groups,id'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:2000'],
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
            'notes.max' => 'A orientação interna deve ter no máximo 2000 caracteres.',
        ];
    }
}
