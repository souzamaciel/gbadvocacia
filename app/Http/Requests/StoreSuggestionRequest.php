<?php

namespace App\Http\Requests;

use App\Models\Suggestion;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSuggestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
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
            'type' => ['required', Rule::in(array_keys(Suggestion::TYPES))],
            'area' => ['required', Rule::in(array_keys(Suggestion::AREAS))],
            'impact' => ['required', Rule::in(['alta', 'media', 'baixa'])],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Escolha o tipo da sugestão.',
            'area.required' => 'Escolha a área do sistema.',
            'impact.required' => 'Diga o quanto isso afeta o seu trabalho.',
            'title.required' => 'Dê um título curto para a sugestão.',
            'title.min' => 'O título precisa ter pelo menos 5 caracteres.',
            'title.max' => 'O título pode ter no máximo 150 caracteres.',
            'description.required' => 'Descreva a sua sugestão.',
            'description.min' => 'Conte um pouco mais: a descrição precisa ter pelo menos 10 caracteres.',
            'description.max' => 'A descrição pode ter no máximo 5.000 caracteres.',
            'images.max' => 'Envie no máximo 3 imagens.',
            'images.*.image' => 'Envie apenas imagens (prints da tela).',
            'images.*.mimes' => 'As imagens devem ser JPG, PNG, WEBP ou GIF.',
            'images.*.max' => 'Cada imagem pode ter no máximo 5 MB.',
        ];
    }
}
