<?php

namespace App\Http\Requests;

use App\Services\RichText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePetitionItemRequest extends FormRequest
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
            'field_values' => ['sometimes', 'array'],
            'field_values.*' => ['nullable', 'string', 'max:2000'],
            'content' => ['sometimes', 'required', 'string'],
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
            'content.required' => 'O texto do tópico não pode ficar vazio.',
        ];
    }
}
