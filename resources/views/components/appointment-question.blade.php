@props(['question'])

@php
    use App\Enums\QuestionType;

    $type = $question->type;
    $key = $question->key;
    $id = 'q-'.$key;
    $width = [
        'half' => 'col-12 col-md-6',
        'third' => 'col-12 col-md-4',
        'two_thirds' => 'col-12 col-md-8',
    ][$question->width] ?? 'col-12';
    $condition = $question->show_if;
    $describedBy = $question->help ? $id.'-help' : null;
    $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
@endphp

<div class="{{ $type->isAnswerable() ? $width : 'col-12' }} gb-question"
     data-question="{{ $key }}"
     @if (! empty($condition['key']))
         data-show-key="{{ $condition['key'] }}"
         data-show-values="{{ json_encode(array_values($condition['values'] ?? [])) }}"
     @endif>
    @switch($type)
        @case(QuestionType::Heading)
            <p class="gb-question-heading">{{ $question->label }}</p>
            @break

        @case(QuestionType::Notice)
            <p class="gb-question-notice"><i data-lucide="info"></i><span>{{ $question->label }}</span></p>
            @break

        @case(QuestionType::SingleChoice)
        @case(QuestionType::YesNo)
        @case(QuestionType::MultipleChoice)
            @php($multiple = $type === QuestionType::MultipleChoice)
            <div role="group" aria-labelledby="{{ $id }}-label" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                <p class="form-label" id="{{ $id }}-label">{{ $question->label }}@if ($question->required)<span class="gb-required" aria-hidden="true"> *</span>@endif</p>
                <div class="check-group">
                    @foreach ($question->choices() as $choice)
                        <label class="check-item">
                            <input type="{{ $multiple ? 'checkbox' : 'radio' }}" name="{{ $multiple ? $key.'[]' : $key }}" value="{{ $choice }}" @if ($question->required && ! $multiple) aria-required="true" @endif> {{ $choice }}
                        </label>
                    @endforeach
                    @if ($question->allowsOther())
                        <label class="check-item">
                            <input type="{{ $multiple ? 'checkbox' : 'radio' }}" name="{{ $multiple ? $key.'[]' : $key }}" value="{{ \App\Services\AppointmentForm::OTHER }}" class="js-other-toggle"> Outros
                        </label>
                        <input type="text" name="{{ $key }}_outro" class="form-control form-control-sm gb-other-input" placeholder="Quais?" aria-label="{{ $question->label }}: outros" disabled>
                    @endif
                </div>
            </div>
            @break

        @case(QuestionType::Repeater)
            <div class="gb-repeater" data-repeater="{{ $key }}" data-columns="{{ json_encode($question->columns()) }}">
                <p class="form-label mb-2">{{ $question->label }}@if ($question->required)<span class="gb-required" aria-hidden="true"> *</span>@endif</p>
                <div class="gb-repeater-rows"></div>
                <button type="button" class="add-btn js-repeater-add"><i data-lucide="plus"></i> Adicionar linha</button>
            </div>
            @break

        @default
            <label for="{{ $id }}" class="form-label">{{ $question->label }}@if ($question->required)<span class="gb-required" aria-hidden="true"> *</span>@endif</label>
            @if ($type === QuestionType::LongText)
                <textarea id="{{ $id }}" name="{{ $key }}" rows="3" class="form-control form-control-sm" placeholder="{{ $question->placeholder }}"
                          @if ($question->required) aria-required="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif></textarea>
            @elseif ($type === QuestionType::Uf)
                <select id="{{ $id }}" name="{{ $key }}" class="form-select form-select-sm" @if ($question->required) aria-required="true" @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
                    <option value=""></option>
                    @foreach ($ufs as $uf)
                        <option value="{{ $uf }}">{{ $uf }}</option>
                    @endforeach
                </select>
            @else
                <input id="{{ $id }}" name="{{ $key }}"
                       type="{{ $type === QuestionType::Number ? 'number' : 'text' }}"
                       class="form-control form-control-sm"
                       placeholder="{{ $type === QuestionType::Date ? 'dd/mm/aaaa' : $question->placeholder }}"
                       @if ($type === QuestionType::Date) data-date @endif
                       @if ($type->mask()) data-mask="{{ $type->mask() }}" inputmode="numeric" @endif
                       @if ($question->addressPrefix()) data-cep-lookup="{{ $question->addressPrefix() }}" @endif
                       @if ($question->required) aria-required="true" @endif
                       @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
            @endif
    @endswitch

    @if ($question->help && $type->isAnswerable())
        <p class="gb-field-hint mb-0" id="{{ $describedBy }}">{{ $question->help }}</p>
    @endif
</div>
