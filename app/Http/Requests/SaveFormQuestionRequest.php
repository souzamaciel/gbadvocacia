<?php

namespace App\Http\Requests;

use App\Enums\QuestionType;
use App\Models\AppointmentFormQuestion;
use App\Services\AppointmentForm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveFormQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('appointment_form.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $choices = collect(preg_split('/\R/', (string) $this->input('choices_text')))
            ->map(fn (string $choice): string => trim($choice))
            ->filter()
            ->values()
            ->all();

        $columns = collect((array) $this->input('columns', []))
            ->map(fn (mixed $column): array => [
                'label' => trim((string) ($column['label'] ?? '')),
                'type' => (string) ($column['type'] ?? QuestionType::ShortText->value),
            ])
            ->filter(fn (array $column): bool => $column['label'] !== '')
            ->values()
            ->all();

        $this->merge([
            'label' => trim((string) $this->input('label')),
            'help' => trim((string) $this->input('help')) ?: null,
            'placeholder' => trim((string) $this->input('placeholder')) ?: null,
            'choices' => $choices,
            'columns' => $columns,
            'required' => $this->boolean('required'),
            'send_to_ai' => $this->boolean('send_to_ai'),
            'allow_other' => $this->boolean('allow_other'),
            'show_if_key' => $this->input('show_if_key') ?: null,
            'show_if_values' => array_values(array_filter((array) $this->input('show_if_values', []))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = QuestionType::tryFrom((string) $this->input('type'));

        return [
            'appointment_form_step_id' => ['required', 'integer', Rule::exists('appointment_form_steps', 'id')],
            'type' => ['required', Rule::enum(QuestionType::class)],
            'label' => ['required', 'string', 'max:1000'],
            'help' => ['nullable', 'string', 'max:500'],
            'placeholder' => ['nullable', 'string', 'max:120'],
            'width' => ['required', 'in:full,half,third,two_thirds'],
            'choices' => [Rule::requiredIf($type?->hasChoices() ?? false), 'array', 'min:'.($type?->hasChoices() ? 2 : 0)],
            'choices.*' => ['string', 'max:200', 'distinct:ignore_case', 'not_in:'.AppointmentForm::OTHER],
            'columns' => [Rule::requiredIf($type === QuestionType::Repeater), 'array', 'max:10'],
            'columns.*.label' => ['required', 'string', 'max:60', 'distinct:ignore_case'],
            'columns.*.type' => ['required', Rule::in(array_map(fn (QuestionType $columnType): string => $columnType->value, QuestionType::columnTypes()))],
            'show_if_key' => ['nullable', 'string', Rule::exists('appointment_form_questions', 'key')],
            'show_if_values' => ['required_with:show_if_key', 'array'],
            'show_if_values.*' => ['string'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var AppointmentFormQuestion|null $current */
                $current = $this->route('question');
                $key = $this->input('show_if_key');

                if ($current?->is_system && $this->input('type') !== $current->type->value) {
                    $validator->errors()->add('type', 'O tipo deste campo não pode mudar: ele é usado na qualificação da petição.');
                }

                if ($current?->is_system && ! $this->boolean('send_to_ai')) {
                    $validator->errors()->add('send_to_ai', 'Este campo é usado na qualificação da petição e precisa ser enviado.');
                }

                if ($key === null) {
                    return;
                }

                $controller = AppointmentFormQuestion::query()->where('key', $key)->first();

                if ($controller === null || ! $controller->type->canControlVisibility() || $controller->is($current)) {
                    $validator->errors()->add('show_if_key', 'Escolha uma pergunta de múltipla escolha, caixas de seleção ou Sim/Não.');

                    return;
                }

                $valid = [...$controller->choices(), ...($controller->allowsOther() ? [AppointmentForm::OTHER] : [])];

                if (array_diff((array) $this->input('show_if_values'), $valid) !== []) {
                    $validator->errors()->add('show_if_values', 'Escolha respostas que existem na pergunta selecionada.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'appointment_form_step_id.required' => 'Escolha o assunto.',
            'appointment_form_step_id.exists' => 'Escolha um assunto válido.',
            'type.required' => 'Escolha o tipo da pergunta.',
            'type.enum' => 'Escolha um tipo válido.',
            'label.required' => 'Escreva o texto da pergunta.',
            'label.max' => 'O texto deve ter no máximo 1000 caracteres.',
            'help.max' => 'O texto de ajuda deve ter no máximo 500 caracteres.',
            'placeholder.max' => 'O exemplo deve ter no máximo 120 caracteres.',
            'width.in' => 'Escolha uma largura válida.',
            'choices.required' => 'Informe as opções de resposta, uma por linha.',
            'choices.min' => 'Informe pelo menos 2 opções de resposta.',
            'choices.*.max' => 'Cada opção deve ter no máximo 200 caracteres.',
            'choices.*.distinct' => 'Há opções repetidas.',
            'choices.*.not_in' => 'Para “Outros”, marque a opção “Incluir Outros com campo livre”.',
            'columns.required' => 'Informe pelo menos uma coluna da lista.',
            'columns.max' => 'Use no máximo 10 colunas.',
            'columns.*.label.max' => 'O nome de cada coluna deve ter no máximo 60 caracteres.',
            'columns.*.label.distinct' => 'Há colunas com o mesmo nome.',
            'columns.*.type.in' => 'Escolha um tipo de coluna válido.',
            'show_if_key.exists' => 'A pergunta escolhida na condição não existe mais.',
            'show_if_values.required_with' => 'Marque ao menos uma resposta que faz esta pergunta aparecer.',
        ];
    }
}
