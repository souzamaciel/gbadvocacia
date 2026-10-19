@extends('layouts.app')

@section('title', $appointment ? $appointment->displayName() : 'Novo atendimento')

@section('page-header')
    <x-page-header :title="$appointment ? 'Atendimento' : 'Novo atendimento'"
                   description="Registre o relato e as informações do caso, no ritmo da conversa."
                   :breadcrumbs="['Atendimentos' => route('appointment.index'), $appointment ? $appointment->displayName() : 'Novo atendimento']">
        @can('appointment_form.manage')
            <x-slot:actions>
                <a href="{{ route('admin.form.index') }}" class="btn btn-white"><i data-lucide="settings-2"></i> Configurar ficha</a>
            </x-slot:actions>
        @endcan
    </x-page-header>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<style>
    .flatpickr-calendar { font-family: var(--df-font); }
    .flatpickr-day.selected, .flatpickr-day.selected:hover, .flatpickr-day.today.selected { background: var(--df-brand-01); border-color: var(--df-brand-01); }
    .flatpickr-day.today { border-color: var(--df-brand-01); }
    .flatpickr-months .flatpickr-month, .flatpickr-current-month .flatpickr-monthDropdown-months { text-transform: capitalize; }
    input.flatpickr-input[readonly] { background-color: #fff; }
    .subsection-title { font: 600 10px/14px var(--df-font); letter-spacing: .5px; text-transform: uppercase; color: var(--df-text-03); margin-bottom: .5rem; }
    .accent-note { background: var(--df-ui-01); border-radius: var(--df-radius); padding: 10px 12px; font-size: 13px; color: var(--df-text-03); margin: 0; }
    .add-btn { font: 500 13px/20px var(--df-font); color: var(--df-brand-01); background: none; border: none; padding: 0; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .add-btn:hover { text-decoration: underline; }

    .print-signature-area { display: none; }
    .gb-question-heading { font: 600 11px/16px var(--df-font); letter-spacing: .5px; text-transform: uppercase; color: var(--df-brand-02, #7a5a2e); border-bottom: 1px solid var(--df-border); padding: 8px 0 6px; margin: 4px 0 0; }
    .gb-question-notice { display: flex; gap: 8px; align-items: flex-start; background: var(--df-ui-01); border-left: 3px solid var(--df-brand-01); border-radius: var(--df-radius); padding: 10px 12px; font-size: 13px; color: var(--df-text-02); margin: 0; }
    .gb-question-notice svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px; color: var(--df-brand-01); }
    .gb-required { color: var(--df-danger); }
    .gb-other-input { max-width: 260px; }
    .gb-question.is-missing .form-control, .gb-question.is-missing .form-select { border-color: var(--df-danger); }
    .gb-question.is-missing .form-label { color: var(--df-danger); }
    .gb-repeater-row { border: 1px solid var(--df-border); border-radius: var(--df-radius); padding: 4px 12px 12px; margin: 0 0 8px !important; background: var(--df-ui-01); }

    @media print {
        .gb-header, .gb-footer, .no-print { display: none !important; }
        .gb-step-panel[hidden] { display: block !important; }
        .gb-step-panel { break-inside: auto; border: 0 !important; }
        .form-control, .form-select, textarea.form-control { border: none !important; box-shadow: none !important; padding-left: 0 !important; background: transparent !important; }
        .form-control::placeholder, .form-select option[value=""] { color: transparent !important; }
        .check-item input[type="checkbox"]:not(:checked),
        .check-item input[type="radio"]:not(:checked) { opacity: .3; }
        .print-signature-area { display: block !important; margin-top: 48px; page-break-inside: avoid; }
    }
</style>
@endsection

@php
    $total = $steps->count() + 1;
@endphp

@section('content')
    @if ($appointment?->import_file_name)
        <div class="alert alert-warning alert-dismissible gb-import-banner no-print" role="status">
            <i data-lucide="sparkles"></i>
            <div>
                <strong>Ficha importada de “{{ $appointment->import_file_name }}” pela IA.</strong>
                Confira todas as respostas, as preliminares e os pedidos sugeridos antes de concluir: a IA pode ter se enganado.
                @if (! empty($appointment->import_notes['pedidos']))
                    <p class="mb-0 mt-2"><strong>Pedidos sugeridos e por quê:</strong></p>
                    <ul>
                        @foreach ($appointment->import_notes['pedidos'] as $pedido)
                            <li><strong>{{ $pedido['titulo'] }}</strong>@if ($pedido['motivo']) — {{ $pedido['motivo'] }}@endif</li>
                        @endforeach
                    </ul>
                @endif
                @if (! empty($appointment->import_notes['observacoes']))
                    <p class="mb-0 mt-2"><strong>Pontos para conferir:</strong></p>
                    <ul>
                        @foreach ($appointment->import_notes['observacoes'] as $note)
                            <li>{{ $note }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar aviso"></button>
        </div>
    @endif

    <form id="appointment-form" method="POST" action="{{ route('appointment.store') }}" novalidate>
    @csrf
    <input type="hidden" name="appointment_id" id="appointment-id" value="{{ $appointment?->id }}">
    <div class="row g-3 align-items-start gb-appointment">
        {{-- Roteiro do atendimento --}}
        <div class="col-12 col-lg-3 no-print">
            <nav class="card gb-steps" aria-label="Roteiro do atendimento">
                <div class="gb-steps-header">
                    <div class="d-flex justify-content-between">
                        <span class="gb-list-label p-0">Roteiro do atendimento</span>
                        <span class="gb-steps-count" id="steps-count">1 de {{ $total }}</span>
                    </div>
                    <div class="gb-progress" aria-hidden="true"><span id="steps-progress" style="width: {{ round(100 / $total, 2) }}%"></span></div>
                </div>
                <div class="gb-steps-select">
                    <label for="steps-select" class="form-label">Assunto atual</label>
                    <select id="steps-select" class="form-select">
                        @foreach ($steps as $step)
                            <option value="{{ $loop->iteration }}">{{ $loop->iteration }}. {{ $step->title }}</option>
                        @endforeach
                        <option value="{{ $total }}">{{ $total }}. Pedidos</option>
                    </select>
                </div>
                <ol class="gb-steps-list">
                    @foreach ($steps as $step)
                        <li>
                            <button type="button" class="gb-step-link @if ($loop->first) is-active @endif" data-step-target="{{ $loop->iteration }}" @if ($loop->first) aria-current="step" @endif>
                                <span class="gb-step-number">{{ $loop->iteration }}</span>
                                <span>{{ $step->title }}</span>
                            </button>
                        </li>
                    @endforeach
                    <li>
                        <button type="button" class="gb-step-link" data-step-target="{{ $total }}">
                            <span class="gb-step-number">{{ $total }}</span>
                            <span>Pedidos</span>
                        </button>
                    </li>
                </ol>
                <p class="gb-groups-hint">
                    <i data-lucide="arrow-down-up"></i>
                    Navegue livremente entre os assuntos. Os campos com * são obrigatórios para concluir.
                </p>
            </nav>
        </div>

        {{-- Assuntos --}}
        <div class="col-12 col-lg-9">
            @foreach ($steps as $step)
                <section class="card gb-step-panel" id="assunto-{{ $loop->iteration }}" data-step="{{ $loop->iteration }}" aria-labelledby="assunto-{{ $loop->iteration }}-title" @unless ($loop->first) hidden @endunless>
                    <div class="card-header gb-step-header">
                        <div class="flex-grow-1">
                            <p class="gb-step-eyebrow">Assunto {{ $loop->iteration }} de {{ $total }}</p>
                            <h2 class="card-title" id="assunto-{{ $loop->iteration }}-title">{{ $step->title }}</h2>
                            @if ($step->description)
                                <p class="card-description">{{ $step->description }}</p>
                            @endif
                        </div>
                        <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="{{ $step->icon }}"></i></span>
                    </div>
                    <div class="gb-step-body">
                        <div class="row g-3">
                            @foreach ($step->questions as $question)
                                <x-appointment-question :question="$question" />
                            @endforeach
                        </div>
                    </div>
                    <p class="gb-step-note">Preencha o que o cliente souber informar. Você pode voltar a qualquer assunto depois.</p>
                </section>
            @endforeach
<section class="card gb-step-panel" id="assunto-{{ $total }}" data-step="{{ $total }}" aria-labelledby="assunto-{{ $total }}-title" hidden>
                    <div class="card-header gb-step-header">
                        <div class="flex-grow-1">
                            <p class="gb-step-eyebrow">Assunto {{ $total }} de {{ $total }}</p>
                            <h2 class="card-title" id="assunto-{{ $total }}-title">Pedidos</h2>
                            <p class="card-description">Confira as preliminares e escolha os pedidos da petição.</p>
                        </div>
                        <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="list-checks"></i></span>
                    </div>
                    <div class="gb-step-body">
                    <div class="row g-3">
                        <div class="col-12 col-xl-5">
                            <x-preliminares-panel :preliminares="$preliminares" />
                        </div>
                        <div class="col-12 col-xl-7">
                            <x-orders-panel :pedidos="$pedidos" />
                        </div>
                    </div>
                    </div>
                    <p class="gb-step-note">Preencha o que o cliente souber informar. Você pode voltar a qualquer assunto depois.</p>
                </section>

            <div class="gb-step-nav no-print">
                <button type="button" class="btn btn-white" id="step-previous" disabled>
                    <i data-lucide="arrow-left"></i> Assunto anterior
                </button>
                <button type="button" class="btn btn-white" id="step-next">
                    Próximo assunto <i data-lucide="arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="gb-form-actions no-print">
        <p class="gb-footer-hint is-warning m-0" id="submit-hint" aria-live="polite">
            <i data-lucide="circle-alert"></i>
            <span>Para concluir, confira os pedidos no <button type="button" class="gb-hint-link" data-step-target="{{ $total }}">passo {{ $total }} · Pedidos</button>.</span>
        </p>
        <div class="gb-page-actions">
            <span class="gb-field-hint m-0 align-self-center" id="draft-status" aria-live="polite">
                @if ($appointment) Rascunho salvo em {{ $appointment->updated_at->format('d/m/Y') }} às {{ $appointment->updated_at->format('H:i') }} @endif
            </span>
            <button type="button" class="btn btn-white js-cancel-flow" id="btn-cancel-appointment" data-item="o atendimento"
                    data-list-url="{{ route('appointment.index') }}"
                    @if ($appointment) data-delete-url="{{ route('appointment.destroy', $appointment) }}" @endif>
                <i data-lucide="x"></i> Cancelar
            </button>
            <button type="button" id="btn-save-draft" class="btn btn-white" disabled title="Nenhuma alteração para salvar">
                <i data-lucide="save"></i> Salvar rascunho
            </button>
            <button type="button" id="btn-print-form" class="btn btn-white">
                <i data-lucide="printer"></i> Imprimir ficha
            </button>
            <button type="submit" id="btn-submit-appointment" class="btn btn-primary" disabled>
                Concluir atendimento <i data-lucide="check"></i>
            </button>
        </div>
    </div>
    </form>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/pt.js"></script>
<script>
    window.gbStepper = (function () {
        const panels = Array.from(document.querySelectorAll('.gb-step-panel'));
        const links = Array.from(document.querySelectorAll('.gb-step-link'));
        const select = document.getElementById('steps-select');
        const total = panels.length;
        let current = 1;
        let visitedLast = false;

        function show(step, focus = true) {
            current = Math.min(Math.max(step, 1), total);
            visitedLast = visitedLast || current === total;
            document.getElementById('btn-submit-appointment').disabled = !visitedLast;
            const hint = document.getElementById('submit-hint');
            if (visitedLast && hint.classList.contains('is-warning')) {
                hint.classList.remove('is-warning');
                hint.innerHTML = '<i data-lucide="circle-check"></i><span>Pronto para concluir. Os dados são enviados só quando você conclui o atendimento.</span>';
                lucide.createIcons();
            }
            panels.forEach(panel => { panel.hidden = Number(panel.dataset.step) !== current; });
            links.forEach(link => {
                const active = Number(link.dataset.stepTarget) === current;
                link.classList.toggle('is-active', active);
                link.toggleAttribute('aria-current', active);
                if (active) { link.setAttribute('aria-current', 'step'); }
            });
            select.value = String(current);
            document.getElementById('steps-count').textContent = current + ' de ' + total;
            document.getElementById('steps-progress').style.width = (current / total * 100) + '%';
            document.getElementById('step-previous').disabled = current === 1;
            document.getElementById('step-next').disabled = current === total;
            if (focus) {
                const title = document.getElementById('assunto-' + current + '-title');
                title.setAttribute('tabindex', '-1');
                title.focus({ preventScroll: true });
                document.querySelector('.gb-appointment').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        links.forEach(link => link.addEventListener('click', () => show(Number(link.dataset.stepTarget))));
        document.getElementById('submit-hint').addEventListener('click', event => {
            const link = event.target.closest('[data-step-target]');
            if (link) { show(Number(link.dataset.stepTarget)); }
        });
        select.addEventListener('change', () => show(Number(select.value)));
        document.getElementById('step-previous').addEventListener('click', () => show(current - 1));
        document.getElementById('step-next').addEventListener('click', () => show(current + 1));

        return {
            go(step) { show(step); },
            readyToSubmit() {
                if (visitedLast) { return true; }
                show(total);
                return false;
            },
        };
    })();
    // ─── Input Masks ───
    function maskCpf(v) {
        return v.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2').slice(0, 14);
    }
    function maskCnpj(v) {
        return v.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2').slice(0, 18);
    }
    function maskCep(v) {
        return v.replace(/\D/g, '').replace(/(\d{5})(\d)/, '$1-$2').slice(0, 9);
    }
    function maskCpfCnpj(v) {
        return v.replace(/\D/g, '').length > 11 ? maskCnpj(v) : maskCpf(v);
    }
    function maskPhone(v) {
        v = v.replace(/\D/g, '');
        if (v.length <= 10) {
            return v.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2').slice(0, 14);
        }
        return v.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2').slice(0, 15);
    }
    function maskPis(v) {
        return v.replace(/\D/g, '').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{5})(\d)/, '$1.$2').replace(/(\d{2})(\d{1})$/, '$1-$2').slice(0, 14);
    }
    function maskMoney(v) {
        v = v.replace(/\D/g, '');
        if (v === '') { return ''; }
        var num = parseInt(v, 10);
        var formatted = (num / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return 'R$ ' + formatted;
    }

    function applyMask(input, maskFn) {
        input.addEventListener('input', function () {
            var pos = this.selectionStart;
            var oldLen = this.value.length;
            this.value = maskFn(this.value);
            var newLen = this.value.length;
            var newPos = pos + (newLen - oldLen);
            this.setSelectionRange(newPos, newPos);
        });
    }

    function applyAllMasks(container) {
        container = container || document;
        container.querySelectorAll('[data-mask]').forEach(function (el) {
            if (el._maskApplied) { return; }
            el._maskApplied = true;
            var fn = {
                'cpf': maskCpf,
                'cnpj': maskCnpj,
                'cpf_cnpj': maskCpfCnpj,
                'cep': maskCep,
                'phone': maskPhone,
                'pis': maskPis,
                'money': maskMoney,
            }[el.getAttribute('data-mask')];
            if (fn) { applyMask(el, fn); }
        });
    }

    function formatExistingValues(container) {
        container = container || document;
        container.querySelectorAll('[data-mask]').forEach(function (el) {
            if (el.value) {
                var fn = {
                    'cpf': maskCpf,
                    'cnpj': maskCnpj,
                    'cpf_cnpj': maskCpfCnpj,
                    'cep': maskCep,
                    'phone': maskPhone,
                    'pis': maskPis,
                    'money': maskMoney,
                }[el.getAttribute('data-mask')];
                if (fn) { el.value = fn(el.value); }
            }
        });
    }

    // ─── Lookup de CEP (ViaCEP) ───
    function lookupCep(input) {
        var prefix = input.dataset.cepLookup;
        if (!prefix) { return; }

        var cep = (input.value || '').replace(/\D/g, '');
        if (cep.length !== 8) { return; }

        var fields = {
            endereco: document.querySelector('[name="' + prefix + '_endereco"]'),
            bairro: document.querySelector('[name="' + prefix + '_bairro"]'),
            cidade: document.querySelector('[name="' + prefix + '_cidade"]'),
            uf: document.querySelector('[name="' + prefix + '_uf"]'),
        };

        input.disabled = true;
        setCepMessage(input, '');
        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.erro) {
                    setCepMessage(input, 'CEP não encontrado. Preencha o endereço manualmente.');
                    return;
                }
                if (fields.endereco) { fields.endereco.value = data.logradouro || ''; }
                if (fields.bairro) { fields.bairro.value = data.bairro || ''; }
                if (fields.cidade) { fields.cidade.value = data.localidade || ''; }
                if (fields.uf) { fields.uf.value = data.uf || ''; }
            })
            .catch(function () {
                setCepMessage(input, 'Não foi possível consultar o CEP agora. Preencha o endereço manualmente.');
            })
            .finally(function () { input.disabled = false; });
    }

    function setCepMessage(input, message) {
        var feedback = input.parentElement.querySelector('.gb-cep-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback gb-cep-feedback';
            feedback.id = input.name + '-feedback';
            feedback.setAttribute('role', 'status');
            input.insertAdjacentElement('afterend', feedback);
            input.setAttribute('aria-describedby', feedback.id);
        }
        feedback.textContent = message;
        feedback.classList.toggle('d-block', message !== '');
        input.classList.toggle('is-invalid', message !== '');
    }

    function applyCepLookup(container) {
        container = container || document;
        container.querySelectorAll('[data-cep-lookup]').forEach(function (el) {
            if (el._cepLookupApplied) { return; }
            el._cepLookupApplied = true;
            el.addEventListener('blur', function () { lookupCep(el); });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        applyAllMasks();
        applyCepLookup();
    });

    // ─── Listas repetíveis ───
    const UFS = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
    const MASKS = { money: 'money', cpf: 'cpf', cpf_cnpj: 'cpf_cnpj', cep: 'cep', phone: 'phone' };

    function escapeAttr(text) {
        return String(text).replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
    }

    function addRepeaterRow(repeater, values) {
        const key = repeater.dataset.repeater;
        const columns = JSON.parse(repeater.dataset.columns || '[]');
        const rows = repeater.querySelector('.gb-repeater-rows');
        const index = Number(repeater.dataset.next || 0);
        repeater.dataset.next = index + 1;
        const size = columns.length <= 3 ? Math.floor(10 / columns.length) : 4;
        const row = document.createElement('div');
        row.className = 'gb-repeater-row row g-2 mb-2';
        row.innerHTML = columns.map(column => {
            const name = `${key}[${index}][${column.key}]`;
            const value = escapeAttr((values || {})[column.key] || '');
            const field = column.type === 'uf'
                ? `<select name="${name}" class="form-select form-select-sm" aria-label="${escapeAttr(column.label)}"><option value=""></option>${UFS.map(uf => `<option ${uf === value ? 'selected' : ''}>${uf}</option>`).join('')}</select>`
                : `<input type="${column.type === 'number' ? 'number' : 'text'}" name="${name}" value="${value}" class="form-control form-control-sm" aria-label="${escapeAttr(column.label)}" ${column.type === 'date' ? 'data-date placeholder="dd/mm/aaaa"' : ''} ${MASKS[column.type] ? `data-mask="${MASKS[column.type]}"` : ''}>`;
            return `<div class="col-12 col-md-${size}"><label class="form-label">${escapeAttr(column.label)}</label>${field}</div>`;
        }).join('') + `<div class="col-12 col-md-auto d-flex align-items-end"><button type="button" class="gb-icon-action gb-icon-action-danger js-repeater-remove" aria-label="Remover linha"><i data-lucide="trash-2"></i></button></div>`;
        rows.appendChild(row);
        lucide.createIcons();
        applyAllMasks(row);
        formatExistingValues(row);
        initDatePickers(row);
    }

    // ─── Calendário em português (dd/mm/aaaa) ───
    function maskDate(v) {
        return v.replace(/\D/g, '').replace(/(\d{2})(\d)/, '$1/$2').replace(/(\d{2})(\d)/, '$1/$2').slice(0, 10);
    }

    function parseTypedDate(text) {
        let match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(text);
        if (match) { return new Date(Number(match[3]), Number(match[2]) - 1, Number(match[1])); }
        match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(text);
        if (match) { return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])); }
        return undefined;
    }

    function initDatePickers(container) {
        (container || document).querySelectorAll('input[data-date]').forEach(input => {
            if (input._flatpickr) { return; }
            const picker = flatpickr(input, {
                locale: 'pt',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true,
                disableMobile: true,
                parseDate: parseTypedDate,
                onChange: () => input.dispatchEvent(new Event('change', { bubbles: true })),
            });
            const alt = picker.altInput;
            alt.placeholder = 'dd/mm/aaaa';
            alt.setAttribute('inputmode', 'numeric');
            ['aria-label', 'aria-describedby', 'aria-required'].forEach(attribute => {
                if (input.hasAttribute(attribute)) { alt.setAttribute(attribute, input.getAttribute(attribute)); }
            });
            if (input.id) { alt.id = input.id; input.removeAttribute('id'); }
            alt.disabled = input.disabled;
            alt.addEventListener('input', () => { alt.value = maskDate(alt.value); });
        });
    }

    document.addEventListener('click', event => {
        const add = event.target.closest('.js-repeater-add');
        if (add) { addRepeaterRow(add.closest('[data-repeater]')); }
        const remove = event.target.closest('.js-repeater-remove');
        if (remove) { remove.closest('.gb-repeater-row').remove(); }
    });

    // ─── "Outros" e perguntas condicionais ───
    function answersOf(key) {
        return Array.from(document.querySelectorAll(`[name="${key}"], [name="${key}[]"]`))
            .filter(input => !input.disabled && (input.type === 'radio' || input.type === 'checkbox' ? input.checked : input.value !== ''))
            .map(input => input.value);
    }

    function refreshConditions() {
        const wrappers = Array.from(document.querySelectorAll('.gb-question'));
        for (let pass = 0; pass < 5; pass++) {
            let changed = false;
            wrappers.forEach(wrapper => {
                const controller = wrapper.dataset.showKey;
                if (!controller) { return; }
                const controllerWrapper = document.querySelector(`.gb-question[data-question="${controller}"]`);
                const values = JSON.parse(wrapper.dataset.showValues || '[]');
                const visible = (!controllerWrapper || !controllerWrapper.hidden) && answersOf(controller).some(value => values.includes(value));
                if (wrapper.hidden === visible) {
                    wrapper.hidden = !visible;
                    wrapper.querySelectorAll('input, select, textarea').forEach(input => {
                        input.disabled = !visible || (input.classList.contains('gb-other-input') && !otherChecked(input));
                    });
                    changed = true;
                }
            });
            if (!changed) { break; }
        }
    }

    function otherChecked(otherInput) {
        const toggle = otherInput.closest('.check-group').querySelector('.js-other-toggle');
        return !!toggle && toggle.checked;
    }

    function refreshOthers() {
        document.querySelectorAll('.gb-other-input').forEach(input => {
            const wrapper = input.closest('.gb-question');
            input.disabled = wrapper.hidden || !otherChecked(input);
        });
    }

    document.getElementById('appointment-form').addEventListener('change', () => { refreshOthers(); refreshConditions(); });
    document.getElementById('appointment-form').addEventListener('input', event => {
        if (event.target.matches('select')) { refreshConditions(); }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.gb-question[data-show-key]').forEach(wrapper => {
            wrapper.hidden = true;
            wrapper.querySelectorAll('input, select, textarea').forEach(input => { input.disabled = true; });
        });
        refreshConditions();
        refreshOthers();
    });

    // ─── Campos obrigatórios ───
    function markMissing(missing) {
        document.querySelectorAll('.gb-question.is-missing').forEach(wrapper => wrapper.classList.remove('is-missing'));
        (missing || []).forEach(item => {
            const wrapper = document.querySelector(`.gb-question[data-question="${item.key}"]`);
            if (wrapper) { wrapper.classList.add('is-missing'); }
        });
    }

    function checkRequired() {
        const missing = [];
        document.querySelectorAll('.gb-question').forEach(wrapper => {
            if (wrapper.hidden || !wrapper.querySelector('[aria-required="true"], .gb-required')) { return; }
            const key = wrapper.dataset.question;
            if (answersOf(key).filter(value => value.trim() !== '').length === 0 && !wrapper.querySelector('.gb-repeater-row')) {
                missing.push({ key, label: wrapper.querySelector('.form-label').textContent.replace('*', '').trim(), step: Number(wrapper.closest('.gb-step-panel').dataset.step) });
            }
        });
        return missing;
    }

    function showMissing(missing) {
        markMissing(missing);
        window.gbStepper.go(missing[0].step);
        Swal.fire({
            icon: 'warning',
            title: 'Faltam campos obrigatórios',
            html: '<ul class="text-start mb-0">' + missing.map(item => `<li>${escapeAttr(item.label)} <small class="text-muted">(assunto ${item.step})</small></li>`).join('') + '</ul>',
            confirmButtonText: 'Preencher',
            confirmButtonColor: 'var(--df-brand-01)',
        });
    }

    document.getElementById('appointment-form').addEventListener('input', event => {
        const wrapper = event.target.closest('.gb-question.is-missing');
        if (wrapper) { wrapper.classList.remove('is-missing'); }
    });

    @if ($prefill)
    document.addEventListener('DOMContentLoaded', function () {
        const prefill = @json($prefill);

        Object.entries(prefill.answers || {}).forEach(([key, value]) => {
            const repeater = document.querySelector(`[data-repeater="${key}"]`);
            if (repeater) {
                (value || []).forEach(row => addRepeaterRow(repeater, row));
                return;
            }
            if (Array.isArray(value)) {
                value.forEach(v => {
                    const el = document.querySelector(`[name="${key}[]"][value="${v}"]`);
                    if (el) { el.checked = true; }
                });
                return;
            }
            const radio = document.querySelector(`[name="${key}"][type="radio"][value="${value}"]`);
            if (radio) { radio.checked = true; return; }
            const el = document.querySelector(`[name="${key}"]`);
            if (el) { el.value = value; }
        });

        (prefill.pedidos || []).forEach(p => addPedido(p));

        const prefillPreliminarIds = (prefill.preliminares || []).map(p => String(p.id));
        document.querySelectorAll('.preliminar-checkbox').forEach(cb => {
            cb.checked = prefillPreliminarIds.includes(cb.value);
        });
        if (typeof onPreliminarChange === 'function') { onPreliminarChange(); }

        refreshOthers();
        refreshConditions();
        formatExistingValues();
    });
    @endif
    document.addEventListener('DOMContentLoaded', function () { initDatePickers(document); });

    document.addEventListener('DOMContentLoaded', function () {
        var printBtn = document.getElementById('btn-print-form');
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                var form = document.getElementById('appointment-form');
                var formData = new FormData(form);

                var originalText = printBtn.innerHTML;
                printBtn.disabled = true;
                printBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Gerando...';

                fetch('{{ route('appointment.print') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': formData.get('_token'),
                    },
                    body: formData,
                })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (data) {
                            throw new Error(data.message || 'Erro ao gerar formulário.');
                        });
                    }

                    var disposition = response.headers.get('Content-Disposition') || '';
                    var filenameMatch = disposition.match(/filename="?([^";\s]+)"?/);
                    var filename = filenameMatch ? filenameMatch[1] : 'formulario-atendimento.docx';

                    return response.blob().then(function (blob) {
                        var url = URL.createObjectURL(blob);
                        var link = document.createElement('a');
                        link.href = url;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);
                    });
                })
                .catch(function (err) {
                    Swal.fire({ icon: 'error', title: 'Não foi possível gerar a ficha', text: err.message || 'Erro de conexão. Tente novamente.', confirmButtonColor: 'var(--df-brand-01)' });
                })
                .finally(function () {
                    printBtn.disabled = false;
                    printBtn.innerHTML = originalText;
                });
            });
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('appointment-form');
        if (!form) { return; }

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!window.gbStepper.readyToSubmit()) { return; }

            var missing = checkRequired();
            if (missing.length) { showMissing(missing); return; }

            var btn = document.getElementById('btn-submit-appointment');
            var originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Carregando...';

            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': formData.get('_token'),
                },
                body: formData,
            })
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (data) {
                        var error = new Error(data.message || 'Erro ao gerar petição.');
                        error.missing = data.missing;
                        throw error;
                    });
                }

                var disposition = response.headers.get('Content-Disposition') || '';
                var filenameMatch = disposition.match(/filename="?([^";\s]+)"?/);
                var filename = filenameMatch ? filenameMatch[1] : 'peticao-inicial.docx';

                if (response.headers.get('X-Appointment-Id')) {
                    window.gbDraft.saved({
                        id: response.headers.get('X-Appointment-Id'),
                        edit_url: response.headers.get('X-Appointment-Url'),
                        update_url: response.headers.get('X-Appointment-Update-Url'),
                        saved_at: new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
                    });
                }

                return response.blob().then(function (blob) {
                    var url = URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);

                    btn.disabled = false;
                    btn.innerHTML = originalText;

                    Swal.fire({
                        title: 'Petição gerada com sucesso!',
                        text: 'O atendimento ficou salvo em Atendimentos, na aba Geradas. Deseja começar outro atendimento?',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--gb-navy)',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Sim, novo atendimento',
                        cancelButtonText: 'Não',
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route('appointment.create') }}';
                        }
                    });
                });
            })
            .catch(function (err) {
                btn.disabled = false;
                btn.innerHTML = originalText;
                if (err.missing && err.missing.length) { showMissing(err.missing); return; }
                Swal.fire({ icon: 'error', title: 'Não foi possível gerar a petição', text: err.message || 'Erro de conexão. Tente novamente.', confirmButtonColor: 'var(--df-brand-01)' });
            });
        });
    });
    // ─── Rascunho ───
    window.gbDraft = (function () {
        const form = document.getElementById('appointment-form');
        const button = document.getElementById('btn-save-draft');
        const status = document.getElementById('draft-status');
        let updateUrl = @json($appointment ? route('appointment.draft.update', $appointment) : null);
        let savedSnapshot = null;
        let saving = false;

        function snapshot() {
            const entries = [];
            new FormData(form).forEach((value, key) => {
                if (key !== '_token' && key !== 'appointment_id' && typeof value === 'string' && value.trim() !== '') { entries.push(key + '=' + value.trim()); }
            });
            return entries.sort().join('&');
        }

        function isDirty() { return savedSnapshot !== null && snapshot() !== savedSnapshot; }

        function refresh() {
            const dirty = isDirty();
            button.disabled = saving || !dirty;
            button.title = dirty ? '' : 'Nenhuma alteração para salvar';
        }

        function saved(data) {
            document.getElementById('appointment-id').value = data.id;
            document.getElementById('btn-cancel-appointment').dataset.deleteUrl = @json(route('appointment.destroy', '__ID__')).replace('__ID__', data.id);
            updateUrl = data.update_url;
            savedSnapshot = snapshot();
            status.textContent = 'Rascunho salvo às ' + data.saved_at;
            if (data.edit_url && location.href !== data.edit_url) { history.replaceState(null, '', data.edit_url); }
            refresh();
        }

        button.addEventListener('click', function () {
            const formData = new FormData(form);
            if (updateUrl) { formData.append('_method', 'PUT'); }
            saving = true;
            refresh();
            button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Salvando…';
            fetch(updateUrl || @json(route('appointment.draft.store')), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': formData.get('_token') },
                body: formData,
            })
            .then(response => response.json().then(data => {
                if (!response.ok) { throw new Error(data.message || 'Não foi possível salvar o rascunho.'); }
                return data;
            }))
            .then(saved)
            .catch(error => Swal.fire({ icon: 'error', title: 'Não foi possível salvar', text: error.message, confirmButtonColor: 'var(--df-brand-01)' }))
            .finally(() => {
                saving = false;
                button.innerHTML = '<i data-lucide="save"></i> Salvar rascunho';
                lucide.createIcons();
                refresh();
            });
        });

        ['input', 'change', 'click'].forEach(type => form.addEventListener(type, () => setTimeout(refresh, 0)));
        window.gbIsDirty = isDirty;
        window.addEventListener('load', () => { savedSnapshot = snapshot(); refresh(); });

        return { saved };
    })();
</script>
@endsection
