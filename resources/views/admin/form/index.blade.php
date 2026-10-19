@extends('layouts.app')

@section('title', 'Configurar ficha')

@php
    use App\Enums\QuestionType;

    $icons = [
        'user' => 'Pessoa', 'building-2' => 'Empresa', 'alarm-clock' => 'Relógio', 'briefcase' => 'Pasta',
        'clock' => 'Horário', 'wallet' => 'Carteira', 'heart-pulse' => 'Saúde', 'scale' => 'Balança',
        'log-out' => 'Saída', 'users' => 'Pessoas', 'gift' => 'Presente', 'clipboard-check' => 'Prancheta',
        'clipboard-list' => 'Lista', 'file-text' => 'Documento', 'map-pin' => 'Local', 'shield' => 'Escudo',
    ];
    $widths = ['full' => 'Linha inteira', 'two_thirds' => '2/3 da linha', 'half' => 'Metade da linha', 'third' => '1/3 da linha'];
    $questionsData = $steps->flatMap(fn ($step) => $step->questions)->map(fn ($question) => [
        'id' => $question->id,
        'key' => $question->key,
        'step' => $question->appointment_form_step_id,
        'type' => $question->type->value,
        'label' => $question->label,
        'help' => $question->help,
        'placeholder' => $question->placeholder,
        'width' => $question->width,
        'required' => $question->required,
        'send_to_ai' => $question->send_to_ai,
        'is_system' => $question->is_system,
        'choices' => $question->type->hasChoices() ? $question->choices() : [],
        'allow_other' => $question->allowsOther(),
        'columns' => $question->columns(),
        'show_if' => $question->show_if,
        'answers' => $question->type->canControlVisibility()
            ? [...$question->choices(), ...($question->allowsOther() ? [\App\Services\AppointmentForm::OTHER] : [])]
            : [],
    ])->values();
    $stepsData = $steps->map(fn ($step) => ['id' => $step->id, 'title' => $step->title, 'description' => $step->description, 'icon' => $step->icon])->values();
    $typeMeta = collect(QuestionType::cases())->mapWithKeys(fn ($type) => [$type->value => [
        'label' => $type->label(),
        'description' => $type->description(),
        'icon' => $type->icon(),
        'answerable' => $type->isAnswerable(),
        'choices' => $type->hasChoices(),
    ]]);
    $labelByKey = $steps->flatMap(fn ($step) => $step->questions)->pluck('label', 'key');
@endphp

@section('page-header')
    <x-page-header title="Ficha de atendimento"
                   description="Adicione, edite, reordene e exclua os assuntos e as perguntas da ficha."
                   :breadcrumbs="['Atendimentos' => route('appointment.create'), 'Configurar ficha']">
        <x-slot:actions>
            <a href="{{ route('appointment.create') }}" target="_blank" class="btn btn-white"><i data-lucide="eye"></i> Ver a ficha</a>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <div class="alert alert-success alert-dismissible" role="status" id="form-status" @unless (session('status')) hidden @endunless>
        <i data-lucide="circle-check"></i>
        <span id="form-status-text">{{ session('status') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar aviso"></button>
    </div>

    <div class="row g-3 align-items-start">
        {{-- Assuntos --}}
        <div class="col-12 col-lg-3">
            <section class="card gb-groups-card" aria-labelledby="steps-title">
                <div class="card-header d-flex align-items-center gap-2">
                    <div class="flex-grow-1">
                        <h2 id="steps-title" class="card-title">Assuntos</h2>
                        <p class="card-description">{{ $steps->count() }} assuntos + Pedidos</p>
                    </div>
                    <button type="button" class="gb-icon-action js-new-step" aria-label="Novo assunto" title="Novo assunto"><i data-lucide="plus"></i></button>
                </div>
                <ol class="gb-group-list gb-form-steps" role="list">
                    @foreach ($steps as $step)
                        <li class="gb-group-row">
                            <a href="{{ route('admin.form.index', ['assunto' => $step->id]) }}"
                               class="gb-group-filter {{ $current?->is($step) ? 'is-active' : '' }}"
                               @if ($current?->is($step)) aria-current="page" @endif>
                                <span class="gb-group-name">{{ $loop->iteration }}. {{ $step->title }}</span>
                                <span class="gb-badge gb-badge-neutral">{{ $step->questions->filter(fn ($q) => $q->type->isAnswerable())->count() }}</span>
                            </a>
                        </li>
                    @endforeach
                    <li class="gb-group-row">
                        <span class="gb-group-filter is-fixed">
                            <span class="gb-group-name">{{ $steps->count() + 1 }}. Pedidos</span>
                            <i data-lucide="lock" class="gb-lock" aria-label="Fixo"></i>
                        </span>
                    </li>
                </ol>
                <p class="gb-groups-hint">
                    <i data-lucide="info"></i>
                    O assunto Pedidos é fixo: nele são escolhidas as preliminares e os pedidos da petição.
                </p>
            </section>
        </div>

        {{-- Perguntas do assunto --}}
        <div class="col-12 col-lg-9">
            @if ($current)
                <section class="card" aria-labelledby="current-step-title">
                    <div class="card-header d-flex flex-wrap align-items-center gap-3">
                        <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="{{ $current->icon }}"></i></span>
                        <div class="flex-grow-1">
                            <p class="gb-step-eyebrow mb-0">Assunto {{ $steps->search(fn ($step) => $step->is($current)) + 1 }} de {{ $steps->count() + 1 }}</p>
                            <h2 id="current-step-title" class="card-title">{{ $current->title }}</h2>
                            <p class="card-description">{{ $current->description ?: 'Sem descrição' }}</p>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="gb-icon-action js-move-step" data-direction="up" aria-label="Mover assunto para cima" title="Mover para cima" @disabled($steps->first()->is($current))><i data-lucide="arrow-up"></i></button>
                            <button type="button" class="gb-icon-action js-move-step" data-direction="down" aria-label="Mover assunto para baixo" title="Mover para baixo" @disabled($steps->last()->is($current))><i data-lucide="arrow-down"></i></button>
                            <button type="button" class="gb-icon-action js-edit-step" aria-label="Editar assunto" title="Editar assunto"><i data-lucide="pencil"></i></button>
                            @if ($current->questions->contains('is_system', true))
                                <button type="button" class="gb-icon-action" disabled aria-label="Este assunto tem campos da qualificação e não pode ser excluído" title="Tem campos da qualificação: não pode ser excluído"><i data-lucide="trash-2"></i></button>
                            @else
                                <button type="button" class="gb-icon-action gb-icon-action-danger js-delete-step" aria-label="Excluir assunto" title="Excluir assunto"><i data-lucide="trash-2"></i></button>
                            @endif
                            <button type="button" class="btn btn-primary btn-sm ms-2 js-new-question"><i data-lucide="plus"></i> Nova pergunta</button>
                        </div>
                    </div>

                    @if ($current->questions->isEmpty())
                        <div class="gb-library-empty">
                            <i data-lucide="list-plus"></i>
                            <p class="gb-hint-title">Nenhuma pergunta neste assunto</p>
                            <p class="m-0">Clique em “Nova pergunta” para começar.</p>
                        </div>
                    @else
                        <ol class="gb-form-questions" id="question-list">
                            @foreach ($current->questions as $question)
                                <li class="gb-form-question is-{{ $question->type->value }}" data-id="{{ $question->id }}">
                                    <span class="gb-form-question-icon" title="{{ $question->type->label() }}"><i data-lucide="{{ $question->type->icon() }}"></i></span>
                                    <div class="gb-form-question-body">
                                        <p class="gb-form-question-label">{{ $question->label }}</p>
                                        <p class="gb-form-question-meta">
                                            <span>{{ $question->type->label() }}</span>
                                            @if ($question->type->hasChoices() || $question->type === QuestionType::YesNo)
                                                <span>· {{ implode(', ', $question->choices()) }}@if ($question->allowsOther()), Outros @endif</span>
                                            @endif
                                            @if ($question->type === QuestionType::Repeater)
                                                <span>· Colunas: {{ collect($question->columns())->pluck('label')->implode(', ') }}</span>
                                            @endif
                                        </p>
                                        <div class="gb-form-question-badges">
                                            @if ($question->required)<span class="gb-badge gb-badge-warning">Obrigatória</span>@endif
                                            @if (! empty($question->show_if['key']))
                                                <span class="gb-badge gb-badge-neutral" title="Aparece só quando “{{ $labelByKey[$question->show_if['key']] ?? $question->show_if['key'] }}” = {{ implode(' ou ', $question->show_if['values'] ?? []) }}">
                                                    <i data-lucide="git-branch"></i> Aparece se: {{ \Illuminate\Support\Str::limit($labelByKey[$question->show_if['key']] ?? $question->show_if['key'], 40) }} = {{ implode(' ou ', $question->show_if['values'] ?? []) }}
                                                </span>
                                            @endif
                                            @if ($question->type->isAnswerable() && ! $question->send_to_ai)<span class="gb-badge gb-badge-neutral"><i data-lucide="eye-off"></i> Não vai à IA</span>@endif
                                            @if ($question->is_system)<span class="gb-badge gb-badge-neutral" title="Usado na qualificação da petição"><i data-lucide="lock"></i> Qualificação da petição</span>@endif
                                        </div>
                                    </div>
                                    <div class="gb-form-question-actions">
                                        <button type="button" class="gb-icon-action js-move-question" data-direction="up" aria-label="Mover para cima" title="Mover para cima" @disabled($loop->first)><i data-lucide="arrow-up"></i></button>
                                        <button type="button" class="gb-icon-action js-move-question" data-direction="down" aria-label="Mover para baixo" title="Mover para baixo" @disabled($loop->last)><i data-lucide="arrow-down"></i></button>
                                        <button type="button" class="gb-icon-action js-edit-question" aria-label="Editar" title="Editar"><i data-lucide="pencil"></i></button>
                                        @if ($question->is_system)
                                            <button type="button" class="gb-icon-action" disabled aria-label="Campo da qualificação: não pode ser excluído" title="Usado na qualificação da petição: não pode ser excluído"><i data-lucide="trash-2"></i></button>
                                        @else
                                            <button type="button" class="gb-icon-action gb-icon-action-danger js-delete-question" aria-label="Excluir" title="Excluir"><i data-lucide="trash-2"></i></button>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    <div class="gb-list-summary gb-list-summary-footer">
                        <span>As mudanças valem na hora para os próximos atendimentos.</span>
                    </div>
                </section>
            @else
                <div class="card">
                    <div class="gb-library-empty">
                        <i data-lucide="list-plus"></i>
                        <p class="gb-hint-title">A ficha ainda não tem assuntos</p>
                        <p class="m-0">Use o + em “Assuntos” para criar o primeiro.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: assunto --}}
    <div class="modal fade" id="step-modal" tabindex="-1" aria-labelledby="step-modal-title" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="step-form" novalidate>
                <div class="modal-header">
                    <h2 class="card-title" id="step-modal-title">Novo assunto</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body d-flex flex-column gap-3">
                    <div>
                        <label for="step-title" class="form-label">Nome do assunto</label>
                        <input type="text" id="step-title" maxlength="80" class="form-control" placeholder="Ex.: Jornada e ponto">
                    </div>
                    <div>
                        <label for="step-description" class="form-label">Descrição · opcional</label>
                        <input type="text" id="step-description" maxlength="255" class="form-control" placeholder="Aparece abaixo do nome do assunto">
                    </div>
                    <div>
                        <p class="form-label" id="step-icon-label">Ícone</p>
                        <div class="gb-icon-picker" role="radiogroup" aria-labelledby="step-icon-label">
                            @foreach ($icons as $icon => $iconLabel)
                                <label class="gb-icon-choice" title="{{ $iconLabel }}">
                                    <input type="radio" name="step-icon" value="{{ $icon }}" aria-label="{{ $iconLabel }}">
                                    <span><i data-lucide="{{ $icon }}"></i></span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <p class="gb-footer-hint me-auto m-0" id="step-hint" aria-live="polite"></p>
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="step-submit" disabled><i data-lucide="check"></i> Salvar assunto</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: pergunta --}}
    <div class="modal fade" id="question-modal" tabindex="-1" aria-labelledby="question-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="question-form" novalidate>
                <div class="modal-header">
                    <div>
                        <h2 class="card-title" id="question-modal-title">Nova pergunta</h2>
                        <p class="card-description" id="question-modal-description">A pergunta aparece no atendimento, na ficha impressa e é enviada à IA.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2" id="question-system-note" hidden>
                        <i data-lucide="lock"></i>
                        <span>Este campo é usado na qualificação da petição: você pode mudar o texto, a ajuda, a largura e se é obrigatório, mas não o tipo.</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="question-type" class="form-label">Tipo</label>
                            <select id="question-type" class="form-select">
                                <optgroup label="Respostas de texto">
                                    @foreach ([QuestionType::ShortText, QuestionType::LongText] as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Escolhas">
                                    @foreach ([QuestionType::SingleChoice, QuestionType::MultipleChoice, QuestionType::YesNo] as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Datas, números e documentos">
                                    @foreach ([QuestionType::Date, QuestionType::Number, QuestionType::Money, QuestionType::Cpf, QuestionType::CpfCnpj, QuestionType::Pis, QuestionType::Cep, QuestionType::Phone, QuestionType::Uf] as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Outros">
                                    @foreach ([QuestionType::Repeater, QuestionType::Heading, QuestionType::Notice] as $type)
                                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <p class="gb-field-hint mb-0" id="question-type-description"></p>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="question-step" class="form-label">Assunto</label>
                            <select id="question-step" class="form-select">
                                @foreach ($steps as $step)
                                    <option value="{{ $step->id }}">{{ $loop->iteration }}. {{ $step->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="question-label" class="form-label" id="question-label-title">Pergunta</label>
                            <textarea id="question-label" rows="2" maxlength="1000" class="form-control" placeholder="Ex.: Recebia comissão pelo cumprimento de metas?"></textarea>
                        </div>

                        <div class="col-12 js-for-choices">
                            <label for="question-choices" class="form-label">Opções de resposta · uma por linha</label>
                            <textarea id="question-choices" rows="5" class="form-control" placeholder="PIX&#10;Transferência bancária&#10;Dinheiro em mãos"></textarea>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="question-allow-other">
                                <label class="form-check-label" for="question-allow-other">Incluir “Outros” com campo livre para escrever</label>
                            </div>
                        </div>

                        <div class="col-12 js-for-repeater">
                            <p class="form-label">Colunas da lista</p>
                            <div id="question-columns" class="d-flex flex-column gap-2"></div>
                            <button type="button" class="add-btn mt-2" id="question-add-column"><i data-lucide="plus"></i> Adicionar coluna</button>
                        </div>

                        <div class="col-12 col-md-8 js-for-answerable">
                            <label for="question-help" class="form-label">Texto de ajuda · opcional</label>
                            <input type="text" id="question-help" maxlength="500" class="form-control" placeholder="Orientação curta que aparece abaixo da pergunta">
                        </div>
                        <div class="col-12 col-md-4 js-for-answerable js-for-placeholder">
                            <label for="question-placeholder" class="form-label">Exemplo no campo · opcional</label>
                            <input type="text" id="question-placeholder" maxlength="120" class="form-control" placeholder="Ex.: 08h às 17h">
                        </div>
                        <div class="col-12 col-md-4 js-for-answerable">
                            <label for="question-width" class="form-label">Largura</label>
                            <select id="question-width" class="form-select">
                                @foreach ($widths as $value => $widthLabel)
                                    <option value="{{ $value }}">{{ $widthLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-8 js-for-answerable d-flex flex-column justify-content-end gap-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="question-required">
                                <label class="form-check-label" for="question-required">Obrigatória para concluir o atendimento</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="question-send-to-ai">
                                <label class="form-check-label" for="question-send-to-ai">Enviar a resposta à IA (desmarque para dados sensíveis, como senhas)</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="gb-condition-box">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="question-conditional">
                                    <label class="form-check-label" for="question-conditional"><strong>Mostrar só quando</strong> outra pergunta tiver uma resposta específica</label>
                                </div>
                                <div id="question-condition-fields" class="row g-2 mt-1" hidden>
                                    <div class="col-12">
                                        <label for="question-show-key" class="form-label">Pergunta</label>
                                        <select id="question-show-key" class="form-select"></select>
                                    </div>
                                    <div class="col-12">
                                        <p class="form-label mb-1">Aparece quando a resposta for</p>
                                        <div class="check-group" id="question-show-values"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <p class="gb-footer-hint me-auto m-0" id="question-hint" aria-live="polite"></p>
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="question-submit" disabled><i data-lucide="check"></i> Salvar pergunta</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const csrfToken = '{{ csrf_token() }}';
            const steps = {{ Js::from($stepsData) }};
            const questions = new Map({{ Js::from($questionsData) }}.map(question => [question.id, question]));
            const types = {{ Js::from($typeMeta) }};
            const columnTypes = {{ Js::from(collect(QuestionType::columnTypes())->mapWithKeys(fn ($type) => [$type->value => $type->label()])) }};
            const currentStepId = {{ $current?->id ?? 'null' }};
            const routes = {
                index: '{{ route('admin.form.index') }}',
                stepStore: '{{ route('admin.form.steps.store') }}',
                stepUpdate: '{{ route('admin.form.steps.update', '__ID__') }}',
                stepMove: '{{ route('admin.form.steps.move', '__ID__') }}',
                stepDestroy: '{{ route('admin.form.steps.destroy', '__ID__') }}',
                questionStore: '{{ route('admin.form.questions.store') }}',
                questionUpdate: '{{ route('admin.form.questions.update', '__ID__') }}',
                questionMove: '{{ route('admin.form.questions.move', '__ID__') }}',
                questionDestroy: '{{ route('admin.form.questions.destroy', '__ID__') }}',
            };
            const $ = id => document.getElementById(id);
            const escapeHtml = text => String(text ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
            const alertError = message => Swal.fire({ icon: 'error', title: 'Não foi possível concluir', text: message, confirmButtonColor: 'var(--df-brand-01)' });

            function request(method, url, body) {
                return fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: body ? JSON.stringify(body) : undefined,
                }).then(response => response.json().catch(() => ({})).then(data => {
                    if (!response.ok) {
                        const first = data.errors ? Object.values(data.errors)[0][0] : null;
                        throw new Error(first || data.message || 'Tente novamente.');
                    }
                    return data;
                }));
            }

            function reloadAt(stepId, message) {
                const url = new URL(routes.index);
                if (stepId) { url.searchParams.set('assunto', stepId); }
                if (message) { sessionStorage.setItem('gb-form-status', message); }
                location.href = url.toString();
            }

            try {
                const saved = sessionStorage.getItem('gb-form-status');
                if (saved) {
                    sessionStorage.removeItem('gb-form-status');
                    $('form-status-text').textContent = saved;
                    $('form-status').hidden = false;
                }
            } catch (error) { /* sem armazenamento local: segue sem o aviso */ }

            /* ---------- Assuntos ---------- */
            const stepModal = new bootstrap.Modal('#step-modal');
            let editingStep = null;

            function refreshStep() {
                const title = $('step-title').value.trim();
                const duplicate = steps.some(step => step.title.toLowerCase() === title.toLowerCase() && step.id !== editingStep?.id);
                const hint = !title ? 'Informe o nome do assunto.' : (duplicate ? 'Já existe um assunto com esse nome.' : '');
                $('step-submit').disabled = hint !== '';
                $('step-hint').className = 'gb-footer-hint me-auto m-0' + (hint ? ' is-warning' : '');
                $('step-hint').innerHTML = hint ? `<i data-lucide="circle-alert"></i><span>${hint}</span>` : '';
                lucide.createIcons();
            }

            function openStep(step) {
                editingStep = step;
                $('step-modal-title').textContent = step ? 'Editar assunto' : 'Novo assunto';
                $('step-title').value = step?.title || '';
                $('step-description').value = step?.description || '';
                const icon = step?.icon || 'clipboard-list';
                document.querySelectorAll('[name="step-icon"]').forEach(radio => { radio.checked = radio.value === icon; });
                refreshStep();
                stepModal.show();
            }

            $('step-title').addEventListener('input', refreshStep);
            $('step-modal').addEventListener('shown.bs.modal', () => $('step-title').focus());
            document.querySelector('.js-new-step').addEventListener('click', () => openStep(null));
            document.querySelector('.js-edit-step')?.addEventListener('click', () => openStep(steps.find(step => step.id === currentStepId)));

            $('step-form').addEventListener('submit', event => {
                event.preventDefault();
                const body = {
                    title: $('step-title').value,
                    description: $('step-description').value,
                    icon: document.querySelector('[name="step-icon"]:checked')?.value,
                };
                $('step-submit').disabled = true;
                request(editingStep ? 'PUT' : 'POST', editingStep ? routes.stepUpdate.replace('__ID__', editingStep.id) : routes.stepStore, body)
                    .then(data => reloadAt(data.id, data.message))
                    .catch(error => { refreshStep(); alertError(error.message); });
            });

            document.querySelectorAll('.js-move-step').forEach(button => button.addEventListener('click', () => {
                request('PUT', routes.stepMove.replace('__ID__', currentStepId), { direction: button.dataset.direction })
                    .then(() => reloadAt(currentStepId))
                    .catch(error => alertError(error.message));
            }));

            document.querySelector('.js-delete-step')?.addEventListener('click', () => {
                const step = steps.find(item => item.id === currentStepId);
                Swal.fire({
                    title: 'Excluir assunto?',
                    text: `O assunto “${step.title}” e todas as perguntas dele serão excluídos da ficha.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--df-danger)',
                    confirmButtonText: 'Excluir',
                    cancelButtonText: 'Cancelar',
                }).then(result => {
                    if (!result.isConfirmed) { return; }
                    request('DELETE', routes.stepDestroy.replace('__ID__', currentStepId))
                        .then(data => reloadAt(null, data.message))
                        .catch(error => alertError(error.message));
                });
            });

            /* ---------- Perguntas ---------- */
            const questionModal = new bootstrap.Modal('#question-modal');
            let editingQuestion = null;

            function addColumn(column) {
                const row = document.createElement('div');
                row.className = 'd-flex gap-2 align-items-center gb-column-row';
                row.innerHTML = `
                    <input type="text" class="form-control js-column-label" maxlength="60" placeholder="Nome da coluna" aria-label="Nome da coluna" value="${escapeHtml(column?.label || '')}">
                    <select class="form-select js-column-type" aria-label="Tipo da coluna" style="max-width: 220px">
                        ${Object.entries(columnTypes).map(([value, label]) => `<option value="${value}" ${value === (column?.type || 'short_text') ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                    </select>
                    <button type="button" class="gb-icon-action gb-icon-action-danger js-remove-column" aria-label="Remover coluna"><i data-lucide="trash-2"></i></button>`;
                $('question-columns').appendChild(row);
                lucide.createIcons();
            }

            function controllers() {
                const stepOrder = new Map(steps.map((step, index) => [step.id, index + 1]));
                return Array.from(questions.values())
                    .filter(question => question.answers.length && question.id !== editingQuestion?.id)
                    .sort((a, b) => stepOrder.get(a.step) - stepOrder.get(b.step) || a.id - b.id);
            }

            function renderConditionOptions(selectedKey, selectedValues) {
                const stepTitle = new Map(steps.map((step, index) => [step.id, `${index + 1}. ${step.title}`]));
                $('question-show-key').innerHTML = '<option value="">Escolha a pergunta</option>' + controllers()
                    .map(question => `<option value="${question.key}" ${question.key === selectedKey ? 'selected' : ''}>${escapeHtml(stepTitle.get(question.step))} › ${escapeHtml(question.label.length > 90 ? question.label.slice(0, 90) + '…' : question.label)}</option>`)
                    .join('');
                renderConditionValues(selectedValues);
            }

            function renderConditionValues(selectedValues) {
                const controller = Array.from(questions.values()).find(question => question.key === $('question-show-key').value);
                $('question-show-values').innerHTML = controller
                    ? controller.answers.map((answer, index) => `<label class="check-item"><input type="checkbox" value="${escapeHtml(answer)}" id="show-value-${index}" ${(selectedValues || []).includes(answer) ? 'checked' : ''}> ${escapeHtml(answer)}</label>`).join('')
                    : '<span class="gb-field-hint m-0">Escolha a pergunta para ver as respostas.</span>';
            }

            function syncType() {
                const type = $('question-type').value;
                const meta = types[type];
                $('question-type-description').textContent = meta.description;
                document.querySelectorAll('.js-for-choices').forEach(el => { el.hidden = !meta.choices; });
                document.querySelectorAll('.js-for-repeater').forEach(el => { el.hidden = type !== 'repeater'; });
                document.querySelectorAll('.js-for-answerable').forEach(el => { el.hidden = !meta.answerable; });
                document.querySelectorAll('.js-for-placeholder').forEach(el => { el.hidden = !meta.answerable || ['single_choice', 'multiple_choice', 'yes_no', 'repeater', 'uf', 'date'].includes(type); });
                $('question-label-title').textContent = type === 'heading' ? 'Subtítulo' : (type === 'notice' ? 'Texto da orientação' : 'Pergunta');
                if (type === 'repeater' && !$('question-columns').children.length) { addColumn(); }
            }

            function formState() {
                const type = $('question-type').value;
                const choices = $('question-choices').value.split('\n').map(line => line.trim()).filter(Boolean);
                const columns = Array.from(document.querySelectorAll('.gb-column-row'))
                    .map(row => ({ label: row.querySelector('.js-column-label').value.trim(), type: row.querySelector('.js-column-type').value }))
                    .filter(column => column.label);
                const conditional = $('question-conditional').checked;
                const showValues = Array.from($('question-show-values').querySelectorAll('input:checked')).map(input => input.value);
                return {
                    appointment_form_step_id: Number($('question-step').value),
                    type,
                    label: $('question-label').value.trim(),
                    help: $('question-help').value,
                    placeholder: $('question-placeholder').value,
                    width: $('question-width').value,
                    required: $('question-required').checked,
                    send_to_ai: $('question-send-to-ai').checked,
                    choices_text: choices.join('\n'),
                    allow_other: $('question-allow-other').checked,
                    columns,
                    show_if_key: conditional ? $('question-show-key').value : null,
                    show_if_values: conditional ? showValues : [],
                    _choices: choices,
                    _conditional: conditional,
                };
            }

            function refreshQuestion() {
                const state = formState();
                const lower = state._choices.map(choice => choice.toLowerCase());
                let hint = '';
                if (!state.label) { hint = state.type === 'heading' ? 'Escreva o subtítulo.' : (state.type === 'notice' ? 'Escreva o texto da orientação.' : 'Escreva a pergunta.'); }
                else if (types[state.type].choices && state._choices.length < 2) { hint = 'Informe pelo menos 2 opções de resposta, uma por linha.'; }
                else if (types[state.type].choices && new Set(lower).size !== lower.length) { hint = 'Há opções repetidas.'; }
                else if (types[state.type].choices && lower.includes('outros')) { hint = 'Para “Outros”, marque a opção “Incluir Outros com campo livre”.'; }
                else if (state.type === 'repeater' && !state.columns.length) { hint = 'Informe pelo menos uma coluna.'; }
                else if (state._conditional && !state.show_if_key) { hint = 'Escolha a pergunta da condição.'; }
                else if (state._conditional && !state.show_if_values.length) { hint = 'Marque ao menos uma resposta que faz a pergunta aparecer.'; }
                $('question-submit').disabled = hint !== '';
                $('question-hint').className = 'gb-footer-hint me-auto m-0' + (hint ? ' is-warning' : '');
                $('question-hint').innerHTML = hint ? `<i data-lucide="circle-alert"></i><span>${escapeHtml(hint)}</span>` : '<i data-lucide="circle-check"></i><span>Pronto para salvar.</span>';
                lucide.createIcons();
            }

            function openQuestion(question) {
                editingQuestion = question;
                $('question-modal-title').textContent = question ? 'Editar pergunta' : 'Nova pergunta';
                $('question-type').value = question?.type || 'short_text';
                $('question-type').disabled = !!question?.is_system;
                $('question-system-note').hidden = !question?.is_system;
                $('question-step').value = question?.step || currentStepId;
                $('question-label').value = question?.label || '';
                $('question-help').value = question?.help || '';
                $('question-placeholder').value = question?.placeholder || '';
                $('question-width').value = question?.width || 'full';
                $('question-required').checked = !!question?.required;
                $('question-send-to-ai').checked = question ? !!question.send_to_ai : true;
                $('question-send-to-ai').disabled = !!question?.is_system;
                $('question-choices').value = (question?.choices || []).join('\n');
                $('question-allow-other').checked = !!question?.allow_other;
                $('question-columns').innerHTML = '';
                (question?.columns || []).forEach(addColumn);
                $('question-conditional').checked = !!question?.show_if?.key;
                $('question-condition-fields').hidden = !question?.show_if?.key;
                renderConditionOptions(question?.show_if?.key, question?.show_if?.values);
                syncType();
                refreshQuestion();
                questionModal.show();
            }

            $('question-type').addEventListener('change', () => { syncType(); refreshQuestion(); });
            $('question-conditional').addEventListener('change', () => { $('question-condition-fields').hidden = !$('question-conditional').checked; refreshQuestion(); });
            $('question-show-key').addEventListener('change', () => { renderConditionValues([]); refreshQuestion(); });
            $('question-add-column').addEventListener('click', () => { addColumn(); refreshQuestion(); });
            $('question-columns').addEventListener('click', event => {
                const remove = event.target.closest('.js-remove-column');
                if (remove) { remove.closest('.gb-column-row').remove(); refreshQuestion(); }
            });
            $('question-form').addEventListener('input', refreshQuestion);
            $('question-form').addEventListener('change', refreshQuestion);
            $('question-modal').addEventListener('shown.bs.modal', () => $('question-label').focus());

            document.querySelector('.js-new-question')?.addEventListener('click', () => openQuestion(null));
            $('question-list')?.addEventListener('click', event => {
                const item = event.target.closest('[data-id]');
                if (!item) { return; }
                const question = questions.get(Number(item.dataset.id));
                if (event.target.closest('.js-edit-question')) { openQuestion(question); }
                const move = event.target.closest('.js-move-question');
                if (move) {
                    request('PUT', routes.questionMove.replace('__ID__', question.id), { direction: move.dataset.direction })
                        .then(() => reloadAt(currentStepId))
                        .catch(error => alertError(error.message));
                }
                if (event.target.closest('.js-delete-question')) {
                    Swal.fire({
                        title: 'Excluir pergunta?',
                        text: `“${question.label.length > 120 ? question.label.slice(0, 120) + '…' : question.label}” sai da ficha.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--df-danger)',
                        confirmButtonText: 'Excluir',
                        cancelButtonText: 'Cancelar',
                    }).then(result => {
                        if (!result.isConfirmed) { return; }
                        request('DELETE', routes.questionDestroy.replace('__ID__', question.id))
                            .then(data => reloadAt(currentStepId, data.message))
                            .catch(error => alertError(error.message));
                    });
                }
            });

            $('question-form').addEventListener('submit', event => {
                event.preventDefault();
                const state = formState();
                if (editingQuestion?.is_system) { state.type = editingQuestion.type; state.send_to_ai = true; }
                $('question-submit').disabled = true;
                request(editingQuestion ? 'PUT' : 'POST', editingQuestion ? routes.questionUpdate.replace('__ID__', editingQuestion.id) : routes.questionStore, state)
                    .then(data => reloadAt(data.step, data.message))
                    .catch(error => { refreshQuestion(); alertError(error.message); });
            });
        })();
    </script>
@endsection
