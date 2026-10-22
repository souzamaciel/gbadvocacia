@extends('layouts.app')

@section('title', 'Revisar conteúdo')

@section('page-header')
    <x-page-header title="Revisar conteúdo"
                   description="Confira e ajuste os textos antes de gerar sua petição."
                   :breadcrumbs="['Petições' => route('petition.index'), $petition->name]">
        <x-slot:actions>
            <button type="button" class="btn btn-white js-cancel-flow" data-item="a petição"
                    data-list-url="{{ route('petition.index') }}"
                    @if ($petition->exists) data-delete-url="{{ route('petition.destroy', $petition) }}" @endif
                    @cannot('petitions.delete') data-can-delete="false" @endcannot>
                <i data-lucide="x"></i> Cancelar
            </button>
            <span class="gb-badge {{ $petition->isGenerated() ? 'gb-badge-success' : 'gb-badge-neutral' }}">
                {{ $petition->isGenerated() ? 'Gerada' : 'Rascunho' }}
            </span>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-warning alert-dismissible" role="status">
            <i data-lucide="info"></i>
            <span>{{ session('status') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar aviso"></button>
        </div>
    @endif

    <x-petition-steps :current="2" :petition="$petition" />

    <div class="row g-3 align-items-start">
        {{-- Estrutura da petição --}}
        <div class="col-12 col-lg-3">
            <nav class="card" aria-labelledby="structure-title">
                <div class="card-header d-flex align-items-start gap-2">
                    <div class="flex-grow-1">
                        <h2 id="structure-title" class="card-title">Estrutura da petição</h2>
                        <p class="card-description">{{ $petition->items->count() }} tópicos · ordem definida na montagem</p>
                    </div>
                    <span class="gb-badge gb-badge-neutral" id="structure-position">1 de {{ $petition->items->count() }}</span>
                </div>
                <ol class="gb-review-list" id="review-list"></ol>
                <p class="gb-groups-hint">
                    <i data-lucide="info"></i>
                    Os ajustes valem apenas para esta petição. Os modelos da biblioteca são preservados.
                </p>
            </nav>
        </div>

        {{-- Tópico atual --}}
        <div class="col-12 col-lg-9">
            <section class="card" id="review-panel" aria-live="polite">
                <div class="card-header d-flex align-items-start gap-3">
                    <div class="flex-grow-1">
                        <p class="gb-library-item-group" id="item-group"></p>
                        <h2 class="card-title" id="item-title"></h2>
                    </div>
                    <span class="gb-badge" id="item-badge"></span>
                </div>

                <div class="card-body d-flex flex-column gap-3 p-4">
                    <div class="gb-internal-note" id="item-notes" role="note" hidden>
                        <i data-lucide="message-square-text"></i>
                        <div>
                            <p class="gb-internal-note-title">Orientação interna</p>
                            <p class="gb-internal-note-text" id="item-notes-text"></p>
                            <p class="gb-internal-note-hint">Também aparece em destaque verde no documento gerado. Apague no Word antes de protocolar.</p>
                        </div>
                    </div>

                    <div class="gb-pending-box" id="fields-box" hidden>
                        <p class="gb-pending-title"><i data-lucide="circle-alert"></i> Complete as informações deste tópico</p>
                        <div class="row g-3" id="fields"></div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <p class="gb-list-label p-0" id="text-label">Texto da petição</p>
                        <button type="button" class="btn btn-white btn-sm" id="edit-text"><i data-lucide="pencil"></i> Editar texto</button>
                    </div>

                    <div class="gb-document-preview" id="preview"></div>

                    <div id="editor" hidden>
                        <div class="gb-editor"><div id="item-editor"></div></div>
                        <p class="gb-field-hint">
                            <i data-lucide="info"></i>
                            Mantenha os campos entre [colchetes] para que as informações continuem sendo preenchidas.
                        </p>
                        <div class="d-flex justify-content-end gap-2 mt-2">
                            <button type="button" class="btn btn-white" id="cancel-edit">Cancelar edição</button>
                            <button type="button" class="btn btn-primary" id="apply-edit" disabled>Aplicar texto</button>
                        </div>
                    </div>
                </div>

                <div class="gb-review-check">
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="reviewed">
                        <label class="form-check-label" for="reviewed">Conferi o conteúdo deste tópico</label>
                    </div>
                    <span class="gb-field-hint m-0" id="reviewed-hint"></span>
                </div>

                <div class="gb-card-footer bg-white">
                    <button type="button" class="btn btn-white" id="previous"><i data-lucide="arrow-left"></i> Anterior</button>
                    <span class="gb-petition-meta" id="position-label"></span>
                    <button type="button" class="btn btn-white" id="next">Próximo <i data-lucide="arrow-right"></i></button>
                </div>
            </section>
        </div>
    </div>

    <section class="card gb-review-summary">
        <div>
            <p class="gb-total m-0" id="summary-title"></p>
            <p class="gb-petition-meta" id="summary-text"></p>
        </div>
        <div class="gb-page-actions">
            <button type="button" class="btn btn-white" data-bs-toggle="modal" data-bs-target="#full-petition"><i data-lucide="file-text"></i> Ver petição completa</button>
            @can('petitions.generate')
                <a href="{{ route('petition.generate.show', $petition) }}" class="btn btn-primary" id="go-generate" aria-disabled="true">
                    Gerar documento <i data-lucide="arrow-right"></i>
                </a>
            @endcan
        </div>
    </section>

    <div class="modal fade" id="full-petition" tabindex="-1" aria-labelledby="full-petition-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="card-title" id="full-petition-title">{{ $petition->name }}</h2>
                        <p class="card-description">Conteúdo revisado, na ordem do documento.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="gb-document-preview" id="full-preview"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @php($itemsData = $petition->items->map(fn ($item) => \App\Http\Controllers\PetitionController::itemPayload($item))->values())
    <x-editor-assets />
    <script>
        (function () {
            const csrfToken = '{{ csrf_token() }}';
            const updateUrl = '{{ route('petition.items.update', [$petition, '__ID__']) }}';
            const items = {{ Js::from($itemsData) }};
            let current = Math.max(0, items.findIndex(item => !item.reviewed));
            let editing = false;
            const saveTimers = {};

            const escape = text => { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; };
            const decode = html => { const area = document.createElement('textarea'); area.innerHTML = html; return area.value; };
            const itemEditor = gbEditor.create('#item-editor', { label: 'Texto do tópico' });
            let editorStartHtml = '';
            const refreshApply = () => {
                const html = gbEditor.getHtml(itemEditor);
                document.getElementById('apply-edit').disabled = !html || html === editorStartHtml;
            };
            itemEditor.on('text-change', refreshApply);

            function renderText(item) {
                return item.content.replace(/\[([^\[\]<>]+)\]/g, (match, name) => {
                    const raw = (item.field_values[decode(name)] || '').trim();
                    const value = (item.uppercase || []).includes(decode(name)) ? raw.toLocaleUpperCase('pt-BR') : raw;
                    return value
                        ? `<span class="gb-filled">${escape(value)}</span>`
                        : `<mark class="gb-placeholder">${match}</mark>`;
                });
            }

            function documentBlock(item) {
                const note = item.notes ? `<p class="gb-document-note"><strong>Orientação interna:</strong> ${escape(item.notes).replace(/\n/g, '<br>')}</p>` : '';
                const title = item.kind === 'qualificacao' ? '' : `<p class="gb-document-title">${escape(item.title)}</p>`;
                return `${title}${note}<div class="gb-rich-text">${renderText(item)}</div>`;
            }

            function itemState(item) {
                if (item.reviewed) { return ['Revisado', 'gb-text-success', 'check']; }
                if (item.pending.length) { return [item.pending.length + (item.pending.length === 1 ? ' informação pendente' : ' informações pendentes'), 'text-warning-emphasis', null]; }
                return ['A revisar', '', null];
            }

            /**
             * Só se avança depois de conferir o tópico atual: tópicos já conferidos,
             * anteriores ou até o primeiro ainda não conferido continuam acessíveis.
             */
            function canVisit(index) {
                const firstUnreviewed = items.findIndex(item => !item.reviewed);
                return index <= current
                    || items[index].reviewed
                    || (index === current + 1 && items[current].reviewed)
                    || (firstUnreviewed !== -1 && index <= firstUnreviewed);
            }

            function renderList() {
                document.getElementById('review-list').innerHTML = items.map((item, index) => {
                    const [label, className, icon] = itemState(item);
                    const locked = !canVisit(index);
                    return `<li><button type="button" class="gb-review-link ${index === current ? 'is-active' : ''}" data-index="${index}" ${index === current ? 'aria-current="true"' : ''} ${locked ? 'disabled title="Confira o tópico atual para avançar"' : ''}>
                        <span class="gb-review-number">${index + 1}.</span>
                        <span><span class="gb-review-title">${escape(item.title)}</span>
                        <span class="gb-review-state ${className}">${icon ? `<i data-lucide="${icon}"></i> ` : ''}${label}</span></span>
                    </button></li>`;
                }).join('');
            }

            function renderFields(item) {
                const box = document.getElementById('fields-box');
                box.hidden = item.placeholders.length === 0;
                document.getElementById('fields').innerHTML = item.placeholders.map((name, index) => `
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="field-${index}">${escape(name)}</label>
                        <input type="text" class="form-control js-field" id="field-${index}" data-name="${escape(name)}"
                               value="${escape(item.field_values[name] || '')}" placeholder="Informe ${escape(name.toLowerCase())}">
                    </div>`).join('');
            }

            function renderSummary() {
                const reviewed = items.filter(item => item.reviewed).length;
                const pending = items.reduce((total, item) => total + item.pending.length, 0);
                const done = reviewed === items.length;
                document.getElementById('summary-title').textContent = `${reviewed} de ${items.length} tópicos revisados`;
                const link = document.getElementById('go-generate');
                document.getElementById('summary-text').textContent = done
                    ? (link ? 'Tudo revisado. Você já pode gerar o documento.' : 'Tudo revisado. A geração do documento fica com quem tem essa permissão no perfil.')
                    : (pending ? `${pending} ${pending === 1 ? 'informação ainda precisa' : 'informações ainda precisam'} ser preenchida${pending === 1 ? '' : 's'}.` : 'Marque cada tópico como conferido para gerar o documento.');
                if (link) {
                    link.classList.toggle('disabled', !done);
                    link.setAttribute('aria-disabled', String(!done));
                }
            }

            function render() {
                const item = items[current];
                document.getElementById('item-group').textContent = item.group_name || '';
                document.getElementById('item-title').textContent = item.title;
                document.getElementById('item-notes').hidden = !item.notes;
                document.getElementById('item-notes-text').textContent = item.notes || '';
                renderFields(item);
                document.getElementById('preview').hidden = editing;
                document.getElementById('editor').hidden = !editing;
                document.getElementById('edit-text').hidden = editing;
                document.getElementById('text-label').textContent = editing ? 'Editando texto da petição' : 'Texto da petição';
                document.getElementById('position-label').textContent = `${current + 1} de ${items.length} tópicos`;
                document.getElementById('structure-position').textContent = `${current + 1} de ${items.length}`;
                document.getElementById('previous').disabled = current === 0;
                refreshStatus();
            }

            function refreshStatus() {
                const item = items[current];
                const badge = document.getElementById('item-badge');
                badge.textContent = item.reviewed ? 'Revisado' : (item.pending.length ? 'Informações pendentes' : 'A revisar');
                badge.className = 'gb-badge ' + (item.reviewed ? 'gb-badge-success' : (item.pending.length ? 'gb-badge-warning' : 'gb-badge-neutral'));

                const box = document.getElementById('fields-box');
                box.classList.toggle('is-complete', item.pending.length === 0);
                box.querySelector('.gb-pending-title').innerHTML = item.pending.length
                    ? '<i data-lucide="circle-alert"></i> Complete as informações deste tópico'
                    : '<i data-lucide="circle-check"></i> Informações deste tópico preenchidas';
                document.getElementById('preview').innerHTML = documentBlock(item);

                const checkbox = document.getElementById('reviewed');
                checkbox.checked = item.reviewed;
                checkbox.disabled = editing || (item.pending.length > 0 && !item.reviewed);
                document.getElementById('reviewed-hint').textContent = editing
                    ? 'Conclua a edição para marcar este tópico'
                    : (item.pending.length && !item.reviewed
                        ? 'Preencha as informações pendentes para marcar e avançar'
                        : (!item.reviewed && current < items.length - 1 ? 'Marque como conferido para avançar ao próximo tópico' : ''));

                const next = document.getElementById('next');
                next.disabled = editing || current === items.length - 1 || !item.reviewed;
                next.title = !item.reviewed && current < items.length - 1 ? 'Marque o tópico como conferido para avançar' : '';

                renderList();
                renderSummary();
                document.getElementById('full-preview').innerHTML = items.map(documentBlock).join('');
                lucide.createIcons();
            }

            function save(index, payload, keepLocalValues = false) {
                const item = items[index];
                return fetch(updateUrl.replace('__ID__', item.id), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(payload),
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok) { throw new Error(data.message || 'Não foi possível salvar.'); }
                    const localValues = items[index].field_values;
                    items[index] = data.item;
                    if (keepLocalValues) {
                        items[index].field_values = localValues;
                        items[index].pending = items[index].placeholders.filter(name => !(localValues[name] || '').trim());
                    }
                    return data;
                });
            }

            function showError(error) {
                Swal.fire({ icon: 'error', title: 'Não foi possível salvar', text: error.message, confirmButtonColor: 'var(--df-brand-01)' });
            }

            function goTo(index) {
                if (editing || !canVisit(Math.min(Math.max(index, 0), items.length - 1))) { return; }
                current = Math.min(Math.max(index, 0), items.length - 1);
                render();
                document.getElementById('item-title').setAttribute('tabindex', '-1');
                document.getElementById('item-title').focus({ preventScroll: true });
            }

            document.getElementById('fields').addEventListener('input', event => {
                const input = event.target.closest('.js-field');
                if (!input) { return; }
                const index = current;
                const item = items[index];
                item.field_values[input.dataset.name] = input.value;
                item.pending = item.placeholders.filter(name => !(item.field_values[name] || '').trim());
                item.reviewed = false;
                refreshStatus();

                clearTimeout(saveTimers[index]);
                saveTimers[index] = setTimeout(() => {
                    save(index, { field_values: Object.assign({}, items[index].field_values) }, true)
                        .then(() => { if (index === current) { refreshStatus(); } })
                        .catch(showError);
                }, 600);
            });

            document.getElementById('reviewed').addEventListener('change', event => {
                clearTimeout(saveTimers[current]);
                save(current, { field_values: Object.assign({}, items[current].field_values), reviewed: event.target.checked })
                    .then(() => {
                        refreshStatus();
                        if (event.target.checked && current < items.length - 1) {
                            const next = items.findIndex((item, index) => index > current && !item.reviewed);
                            if (next !== -1) { setTimeout(() => goTo(next), 400); }
                        }
                    })
                    .catch(error => { event.target.checked = !event.target.checked; showError(error); });
            });

            document.getElementById('edit-text').addEventListener('click', () => {
                editing = true;
                gbEditor.setHtml(itemEditor, items[current].content);
                editorStartHtml = gbEditor.getHtml(itemEditor);
                refreshApply();
                render();
                itemEditor.focus();
            });
            document.getElementById('cancel-edit').addEventListener('click', () => { editing = false; render(); });
            document.getElementById('apply-edit').addEventListener('click', () => {
                const content = gbEditor.getHtml(itemEditor);
                if (!content) { return showError(new Error('O texto do tópico não pode ficar vazio.')); }
                save(current, { content }).then(() => { editing = false; render(); }).catch(showError);
            });

            document.getElementById('previous').addEventListener('click', () => goTo(current - 1));
            document.getElementById('next').addEventListener('click', () => goTo(current + 1));
            document.getElementById('review-list').addEventListener('click', event => {
                const link = event.target.closest('[data-index]');
                if (link) { goTo(Number(link.dataset.index)); }
            });
            document.getElementById('go-generate')?.addEventListener('click', event => {
                if (event.currentTarget.classList.contains('disabled')) { event.preventDefault(); }
            });

            render();
        })();
    </script>
@endsection
