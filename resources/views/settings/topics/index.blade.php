@extends('layouts.app')

@section('title', 'Biblioteca de tópicos')

@section('page-header')
    <x-page-header title="Biblioteca de tópicos"
                   description="Organize os textos usados na montagem das petições."
                   :breadcrumbs="['Modelos do escritório']">
        <x-slot:actions>
            <button type="button" class="btn btn-primary js-new-topic">
                <i data-lucide="plus"></i> Novo tópico
            </button>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible" role="status">
            <i data-lucide="circle-check"></i>
            <span>{{ session('status') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar aviso"></button>
        </div>
    @endif

    <div class="row g-3 align-items-start">
        {{-- Grupos --}}
        <div class="col-12 col-lg-3">
            <section class="card gb-groups-card" aria-labelledby="groups-title">
                <div class="card-header d-flex align-items-center gap-2">
                    <div class="flex-grow-1">
                        <h2 id="groups-title" class="card-title">Grupos</h2>
                        <p class="card-description">{{ $groups->count() }} grupos de tópicos</p>
                    </div>
                    <button type="button" class="gb-icon-action js-new-group" aria-label="Novo grupo">
                        <i data-lucide="plus"></i>
                    </button>
                </div>

                <ul class="gb-group-list" role="list">
                    <li>
                        <button type="button" class="gb-group-filter is-active" data-group="" aria-pressed="true">
                            <span class="gb-group-name">Todos os tópicos</span>
                            <span class="gb-badge gb-badge-neutral">{{ $topics->count() }}</span>
                        </button>
                    </li>
                    @foreach ($groups as $group)
                        <li class="gb-group-row">
                            <button type="button" class="gb-group-filter" data-group="{{ $group->id }}" aria-pressed="false">
                                <span class="gb-group-name">{{ $group->name }}</span>
                                <span class="gb-badge gb-badge-neutral">{{ $group->topics_count }}</span>
                            </button>
                            <span class="gb-group-actions">
                                <button type="button" class="gb-icon-action js-edit-group"
                                        data-id="{{ $group->id }}" data-name="{{ $group->name }}"
                                        aria-label="Renomear grupo {{ $group->name }}">
                                    <i data-lucide="pencil"></i>
                                </button>
                                @if ($group->topics_count === 0)
                                    <button type="button" class="gb-icon-action gb-icon-action-danger js-delete-group"
                                            data-id="{{ $group->id }}" data-name="{{ $group->name }}"
                                            aria-label="Excluir grupo {{ $group->name }}">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>

                <p class="gb-groups-hint">
                    <i data-lucide="info"></i>
                    Os grupos ajudam a encontrar tópicos durante a montagem.
                </p>
            </section>
        </div>

        {{-- Tópicos --}}
        <div class="col-12 col-lg-9">
            <section class="card" aria-label="Tópicos">
                <div class="gb-library-filters align-items-end">
                    <div class="gb-input-icon flex-grow-1">
                        <i data-lucide="search"></i>
                        <label for="topic-search" class="visually-hidden">Buscar por título ou conteúdo</label>
                        <input type="search" id="topic-search" class="form-control" placeholder="Buscar por título ou conteúdo…" autocomplete="off">
                    </div>
                    <div>
                        <label for="topic-sort" class="form-label">Ordenar</label>
                        <select id="topic-sort" class="form-select gb-library-group">
                            <option value="group">Ordem dos grupos</option>
                            <option value="title">Título A–Z</option>
                        </select>
                    </div>
                </div>

                <div class="gb-list-summary">
                    <span id="topics-summary">{{ $topics->count() }} tópicos disponíveis</span>
                </div>

                <ul class="gb-library-list" id="topic-list">
                    @foreach ($topics as $topic)
                        <li class="gb-library-item"
                            data-id="{{ $topic->id }}"
                            data-group="{{ $topic->group_id }}"
                            data-position="{{ $loop->index }}"
                            data-title="{{ mb_strtolower($topic->title) }}"
                            data-search="{{ mb_strtolower($topic->title.' '.$topic->content) }}">
                            <div class="gb-library-item-body">
                                <p class="gb-library-item-group">{{ $topic->group?->name }}</p>
                                <h3 class="gb-library-item-title">
                                    {{ $topic->title }}
                                    @unless ($topic->active)
                                        <span class="gb-badge gb-badge-neutral ms-1">Inativo</span>
                                    @endunless
                                </h3>
                                <p class="gb-library-item-summary">{{ $topic->content }}</p>
                                <p class="gb-library-item-meta">
                                    @switch($topic->placeholderCount())
                                        @case(0) Nenhum campo a completar @break
                                        @case(1) 1 campo a completar @break
                                        @default {{ $topic->placeholderCount() }} campos a completar
                                    @endswitch
                                </p>
                            </div>
                            <div class="gb-library-item-action gap-1">
                                <button type="button" class="btn btn-white btn-sm js-preview-topic me-1">
                                    <i data-lucide="eye"></i> Ver conteúdo
                                </button>
                                <button type="button" class="gb-icon-action js-edit-topic" aria-label="Editar {{ $topic->title }}">
                                    <i data-lucide="pencil"></i>
                                </button>
                                <button type="button" class="gb-icon-action gb-icon-action-danger js-delete-topic" aria-label="Excluir {{ $topic->title }}">
                                    <i data-lucide="trash-2"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="gb-library-empty" id="topics-empty" @if ($topics->isNotEmpty()) hidden @endif>
                    <i data-lucide="search-x"></i>
                    <p class="gb-hint-title">Nenhum tópico encontrado</p>
                    <p class="m-0">Tente outro termo ou escolha outro grupo.</p>
                </div>

                <div class="gb-list-summary gb-list-summary-footer">
                    <span>GB Advocacia</span>
                    <span>Biblioteca compartilhada do escritório</span>
                </div>
            </section>
        </div>
    </div>

    {{-- Modal: novo / editar tópico --}}
    <div class="modal fade" id="topic-modal" tabindex="-1" aria-labelledby="topic-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form class="modal-content" id="topic-form" method="POST" action="{{ route('settings.topics.store') }}" novalidate>
                @csrf
                <input type="hidden" name="_method" value="POST" id="topic-form-method">
                <input type="hidden" name="topic_id" id="topic-form-id" value="{{ old('topic_id') }}">
                <div class="modal-header">
                    <div>
                        <h2 class="card-title" id="topic-modal-title">Novo tópico</h2>
                        <p class="card-description" id="topic-modal-description">Este modelo ficará disponível para novas petições.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label for="topic-title" class="form-label">Título</label>
                            <input type="text" id="topic-title" name="title" maxlength="255" placeholder="Ex.: Horas extras habituais"
                                   class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}"
                                   @error('title') aria-describedby="topic-title-error" @enderror>
                            @error('title')
                                <div id="topic-title-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="topic-group" class="form-label">Grupo</label>
                            <select id="topic-group" name="group_id" class="form-select @error('group_id') is-invalid @enderror"
                                    @error('group_id') aria-describedby="topic-group-error" @enderror>
                                <option value="">Selecione</option>
                                @foreach ($groups as $group)
                                    <option value="{{ $group->id }}" @selected((string) old('group_id') === (string) $group->id)>{{ $group->name }}</option>
                                @endforeach
                            </select>
                            @error('group_id')
                                <div id="topic-group-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label for="topic-content" class="form-label">Conteúdo do modelo</label>
                            <textarea id="topic-content" name="content" rows="12" placeholder="Digite o texto do tópico…"
                                      class="form-control @error('content') is-invalid @enderror"
                                      @error('content') aria-describedby="topic-content-error" @enderror>{{ old('content') }}</textarea>
                            @error('content')
                                <div id="topic-content-error" class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <p class="gb-field-hint">
                                <i data-lucide="info"></i>
                                Use [colchetes] para os dados que o advogado completa no documento, ex.: [NOME DA 1ª RECLAMADA].
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <p class="gb-field-hint me-auto my-0">Este modelo ficará disponível para novas petições.</p>
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="check"></i> Salvar tópico</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: prévia do modelo --}}
    <div class="modal fade" id="preview-modal" tabindex="-1" aria-labelledby="preview-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <p class="gb-library-item-group" id="preview-modal-group"></p>
                        <h2 class="card-title" id="preview-modal-title"></h2>
                        <p class="card-description" id="preview-modal-meta"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="gb-preview-text" id="preview-modal-content"></div>
                    <p class="gb-preview-note" id="preview-modal-notes" hidden></p>
                </div>
                <div class="modal-footer">
                    <p class="gb-field-hint me-auto my-0">Os trechos entre [colchetes] são completados no documento.</p>
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="preview-modal-edit"><i data-lucide="pencil"></i> Editar modelo</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: novo / renomear grupo --}}
    <div class="modal fade" id="group-modal" tabindex="-1" aria-labelledby="group-modal-title" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="group-form" novalidate>
                <div class="modal-header">
                    <div class="d-flex align-items-center gap-3">
                        <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="folder-plus"></i></span>
                        <div>
                            <h2 class="card-title" id="group-modal-title">Novo grupo</h2>
                            <p class="card-description">Organize os tópicos da biblioteca.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <label for="group-name" class="form-label">Nome do grupo</label>
                    <input type="text" id="group-name" class="form-control" maxlength="100" placeholder="Ex.: Estabilidades" aria-describedby="group-name-error">
                    <div class="invalid-feedback" id="group-name-error"></div>
                    <p class="gb-field-hint">
                        <i data-lucide="info"></i>
                        O grupo aparece na biblioteca e na montagem das petições.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="group-form-submit"><i data-lucide="plus"></i> Criar grupo</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    @php
        $topicsData = $topics->map(fn ($topic) => $topic->only(['id', 'group_id', 'title', 'content', 'notes']))->values();
        $groupsData = $groups->map(fn ($group) => $group->only(['id', 'name']))->values();
    @endphp
    <script>
        (function () {
            const csrfToken = '{{ csrf_token() }}';
            const topics = new Map({{ Js::from($topicsData) }}.map(topic => [topic.id, topic]));
            const groupNames = new Map({{ Js::from($groupsData) }}.map(group => [group.id, group.name]));
            const routes = {
                topicStore: '{{ route('settings.topics.store') }}',
                topicUpdate: '{{ route('settings.topics.update', '__ID__') }}',
                topicDestroy: '{{ route('settings.topics.destroy', '__ID__') }}',
                groupStore: '{{ route('settings.groups.store') }}',
                groupUpdate: '{{ route('settings.groups.update', '__ID__') }}',
                groupDestroy: '{{ route('settings.groups.destroy', '__ID__') }}',
            };

            const list = document.getElementById('topic-list');
            const items = Array.from(list.querySelectorAll('.gb-library-item'));
            const topicModal = new bootstrap.Modal('#topic-modal');
            const previewModal = new bootstrap.Modal('#preview-modal');
            const groupModal = new bootstrap.Modal('#group-modal');
            let activeGroup = '';
            let previewTopicId = null;
            let editingGroupId = null;

            const alertError = message => Swal.fire({ icon: 'error', title: 'Não foi possível concluir', text: message, confirmButtonColor: 'var(--df-brand-01)' });

            /* ---------- Filtros ---------- */
            function applyFilters() {
                const query = document.getElementById('topic-search').value.trim().toLowerCase();
                const sort = document.getElementById('topic-sort').value;
                let visible = 0;

                items.forEach(item => {
                    const matches = (!activeGroup || item.dataset.group === activeGroup) && (!query || item.dataset.search.includes(query));
                    item.hidden = !matches;
                    visible += matches ? 1 : 0;
                });

                items
                    .slice()
                    .sort((a, b) => sort === 'title'
                        ? a.dataset.title.localeCompare(b.dataset.title, 'pt-BR')
                        : Number(a.dataset.position) - Number(b.dataset.position))
                    .forEach(item => list.appendChild(item));

                document.getElementById('topics-summary').textContent = visible + (visible === 1 ? ' tópico disponível' : ' tópicos disponíveis');
                document.getElementById('topics-empty').hidden = visible > 0;
            }

            document.querySelectorAll('.gb-group-filter').forEach(button => {
                button.addEventListener('click', () => {
                    activeGroup = button.dataset.group;
                    document.querySelectorAll('.gb-group-filter').forEach(other => {
                        other.classList.toggle('is-active', other === button);
                        other.setAttribute('aria-pressed', String(other === button));
                    });
                    applyFilters();
                });
            });
            document.getElementById('topic-search').addEventListener('input', applyFilters);
            document.getElementById('topic-sort').addEventListener('change', applyFilters);

            /* ---------- Tópico: novo / editar ---------- */
            function openTopicForm(topic, keepValues = false) {
                const form = document.getElementById('topic-form');
                form.action = topic ? routes.topicUpdate.replace('__ID__', topic.id) : routes.topicStore;
                document.getElementById('topic-form-method').value = topic ? 'PUT' : 'POST';
                document.getElementById('topic-form-id').value = topic ? topic.id : '';
                document.getElementById('topic-modal-title').textContent = topic ? 'Editar modelo' : 'Novo tópico';
                document.getElementById('topic-modal-description').textContent = topic
                    ? 'As alterações valem para as próximas petições.'
                    : 'Este modelo ficará disponível para novas petições.';

                if (!keepValues) {
                    document.getElementById('topic-title').value = topic ? topic.title : '';
                    document.getElementById('topic-group').value = topic ? topic.group_id : (activeGroup || '');
                    document.getElementById('topic-content').value = topic ? topic.content : '';
                    form.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));
                }

                topicModal.show();
            }

            document.querySelectorAll('.js-new-topic').forEach(button => button.addEventListener('click', () => openTopicForm(null)));

            /* ---------- Tópico: prévia ---------- */
            function openPreview(topic) {
                previewTopicId = topic.id;
                const fields = new Set(topic.content.match(/\[[^\]]+\]/g) || []);
                document.getElementById('preview-modal-group').textContent = groupNames.get(topic.group_id) || '';
                document.getElementById('preview-modal-title').textContent = topic.title;
                document.getElementById('preview-modal-meta').textContent = 'Prévia do modelo · ' +
                    (fields.size === 0 ? 'nenhum campo a completar' : fields.size + (fields.size === 1 ? ' campo a completar' : ' campos a completar'));

                const content = document.getElementById('preview-modal-content');
                content.textContent = topic.content;
                content.innerHTML = content.innerHTML.replace(/\[[^\]]+\]/g, match => `<mark>${match}</mark>`);

                const notes = document.getElementById('preview-modal-notes');
                notes.hidden = !topic.notes;
                notes.textContent = topic.notes ? 'Orientação interna: ' + topic.notes : '';

                previewModal.show();
            }

            document.getElementById('preview-modal-edit').addEventListener('click', () => {
                previewModal.hide();
                openTopicForm(topics.get(previewTopicId));
            });

            /* ---------- Tópico: excluir ---------- */
            function deleteTopic(topic) {
                Swal.fire({
                    title: 'Excluir tópico?',
                    text: `“${topic.title}” deixará de aparecer na montagem das petições.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--df-danger)',
                    confirmButtonText: 'Excluir',
                    cancelButtonText: 'Cancelar',
                }).then(result => {
                    if (!result.isConfirmed) { return; }
                    fetch(routes.topicDestroy.replace('__ID__', topic.id), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    })
                    .then(response => response.json())
                    .then(data => data.success ? location.reload() : alertError(data.message))
                    .catch(() => alertError('Erro de conexão. Tente novamente.'));
                });
            }

            list.addEventListener('click', event => {
                const item = event.target.closest('.gb-library-item');
                if (!item) { return; }
                const topic = topics.get(Number(item.dataset.id));

                if (event.target.closest('.js-preview-topic')) { openPreview(topic); }
                if (event.target.closest('.js-edit-topic')) { openTopicForm(topic); }
                if (event.target.closest('.js-delete-topic')) { deleteTopic(topic); }
            });

            /* ---------- Grupos ---------- */
            function openGroupForm(id = null, name = '') {
                editingGroupId = id;
                const input = document.getElementById('group-name');
                input.value = name;
                input.classList.remove('is-invalid');
                document.getElementById('group-modal-title').textContent = id ? 'Renomear grupo' : 'Novo grupo';
                document.getElementById('group-form-submit').innerHTML = id
                    ? '<i data-lucide="check"></i> Salvar grupo'
                    : '<i data-lucide="plus"></i> Criar grupo';
                lucide.createIcons();
                groupModal.show();
            }

            document.getElementById('group-modal').addEventListener('shown.bs.modal', () => document.getElementById('group-name').focus());
            document.querySelector('.js-new-group').addEventListener('click', () => openGroupForm());
            document.querySelectorAll('.js-edit-group').forEach(button => {
                button.addEventListener('click', () => openGroupForm(Number(button.dataset.id), button.dataset.name));
            });

            document.getElementById('group-form').addEventListener('submit', event => {
                event.preventDefault();
                const input = document.getElementById('group-name');
                const error = document.getElementById('group-name-error');
                const name = input.value.trim();

                const showError = message => {
                    input.classList.add('is-invalid');
                    error.textContent = message;
                    input.focus();
                };

                if (!name) { return showError('Informe o nome do grupo.'); }
                const duplicate = Array.from(groupNames.entries())
                    .some(([id, existing]) => id !== editingGroupId && existing.toLowerCase() === name.toLowerCase());
                if (duplicate) { return showError('Já existe um grupo com esse nome.'); }

                fetch(editingGroupId ? routes.groupUpdate.replace('__ID__', editingGroupId) : routes.groupStore, {
                    method: editingGroupId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ name }),
                })
                .then(response => response.json().then(data => ({ ok: response.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data.success) { return location.reload(); }
                    showError(data.errors?.name?.[0] || data.message || 'Não foi possível salvar o grupo.');
                })
                .catch(() => showError('Erro de conexão. Tente novamente.'));
            });

            document.querySelectorAll('.js-delete-group').forEach(button => {
                button.addEventListener('click', () => {
                    Swal.fire({
                        title: 'Excluir grupo?',
                        text: `O grupo “${button.dataset.name}” será excluído.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--df-danger)',
                        confirmButtonText: 'Excluir',
                        cancelButtonText: 'Cancelar',
                    }).then(result => {
                        if (!result.isConfirmed) { return; }
                        fetch(routes.groupDestroy.replace('__ID__', button.dataset.id), {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        })
                        .then(response => response.json())
                        .then(data => data.success ? location.reload() : alertError(data.message))
                        .catch(() => alertError('Erro de conexão. Tente novamente.'));
                    });
                });
            });

            /* ---------- Abrir modal pela URL ou após erro de validação ---------- */
            const params = new URLSearchParams(location.search);
            @if ($errors->any())
                openTopicForm(topics.get(Number(document.getElementById('topic-form-id').value)) || null, true);
            @else
                if (params.has('novo')) { openTopicForm(null); }
                if (params.has('editar') && topics.has(Number(params.get('editar')))) { openTopicForm(topics.get(Number(params.get('editar')))); }
            @endif
        })();
    </script>
@endsection
