@extends('layouts.app')

@section('title', 'Monte sua petição')

@section('page-header')
    <x-page-header title="Monte sua petição inicial"
                   description="Escolha os tópicos e organize a sequência do documento."
                   :breadcrumbs="['Petições' => route('petition.index'), $petition?->name ?? 'Nova petição']">
        <x-slot:actions>
            <button type="submit" form="petition-form" name="action" value="save" class="btn btn-white js-save-button" disabled>
                <i data-lucide="save"></i> Salvar
            </button>
            <button type="submit" form="petition-form" name="action" value="review" class="btn btn-primary js-review-button d-none d-lg-inline-flex" disabled>
                Revisar petição <i data-lucide="arrow-right"></i>
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

    <x-petition-steps :current="1" :petition="$petition" />

    <form id="petition-form" method="POST" action="{{ $petition ? route('petition.update', $petition) : route('petition.store') }}">
        @csrf
        @if ($petition)
            @method('PUT')
        @endif

        <div class="row g-3 align-items-start">
            {{-- Biblioteca de tópicos --}}
            <div class="col-12 col-lg-7">
                <section class="card" aria-labelledby="library-title">
                    <div class="card-header">
                        <h2 id="library-title" class="card-title">Biblioteca de tópicos</h2>
                        <p class="card-description">Modelos do escritório disponíveis para esta petição</p>
                    </div>

                    <div class="gb-library-filters">
                        <div class="gb-input-icon flex-grow-1">
                            <i data-lucide="search"></i>
                            <label for="library-search" class="visually-hidden">Pesquisar tópico</label>
                            <input type="search" id="library-search" class="form-control" placeholder="Pesquisar tópico…" autocomplete="off">
                        </div>
                        <label for="library-group" class="visually-hidden">Filtrar por grupo</label>
                        <select id="library-group" class="form-select gb-library-group">
                            <option value="">Todos os grupos</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <ul class="gb-library-list" id="library-list">
                        @foreach ($groups as $group)
                            @foreach ($group->topics as $topic)
                                <li class="gb-library-item"
                                    data-id="{{ $topic->id }}"
                                    data-group="{{ $group->id }}"
                                    data-kind="{{ $group->id === \App\Models\Group::PRELIMINARES_ID ? 'preliminar' : 'pedido' }}"
                                    data-search="{{ mb_strtolower($topic->title.' '.$group->name) }}">
                                    <div class="gb-library-item-body">
                                        <p class="gb-library-item-group">{{ $group->name }}</p>
                                        <h3 class="gb-library-item-title">{{ $topic->title }}</h3>
                                        <div class="gb-library-item-content gb-rich-text" id="topic-content-{{ $topic->id }}">{!! $topic->contentHtml() !!}</div>
                                        <button type="button" class="gb-link js-toggle-content"
                                                aria-expanded="false" aria-controls="topic-content-{{ $topic->id }}">
                                            Ver conteúdo
                                        </button>
                                    </div>
                                    <div class="gb-library-item-action">
                                        <button type="button" class="btn btn-white btn-sm js-add">
                                            <i data-lucide="plus"></i> Adicionar
                                        </button>
                                        <div class="gb-library-item-added">
                                            <span class="gb-text-success">Adicionado</span>
                                            <button type="button" class="gb-link js-remove">Remover</button>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>

                    <p class="gb-library-empty" id="library-empty" hidden>
                        Nenhum tópico encontrado. Tente outro termo ou grupo.
                    </p>
                </section>
            </div>

            {{-- Minha petição --}}
            <div class="col-12 col-lg-5 gb-sticky-column">
                <section class="card" aria-labelledby="petition-title">
                    <div class="card-header d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                            <h2 id="petition-title" class="card-title">Minha petição</h2>
                            <p class="card-description">A ordem abaixo será a ordem do documento.</p>
                        </div>
                        <span class="gb-badge gb-badge-brand" id="selected-count">0 tópicos</span>
                    </div>

                    <div class="gb-draft-name">
                        <label for="petition-name" class="form-label">Nome do rascunho</label>
                        <input type="text" id="petition-name" name="name" class="form-control" maxlength="255"
                               value="{{ old('name', $petition?->name) }}" placeholder="Ex.: Petição inicial — Maria Aparecida">
                    </div>

                    <div id="selected-preliminares-block" hidden>
                        <p class="gb-list-label">Preliminares</p>
                        <ul class="gb-selected-list" id="selected-preliminares"></ul>
                    </div>

                    <p class="gb-list-label">Ordem dos pedidos</p>
                    <ul class="gb-selected-list" id="selected-pedidos" aria-live="polite"></ul>
                    <p class="gb-selected-empty" id="selected-empty">
                        Nenhum pedido adicionado. Use “Adicionar” na biblioteca ao lado.
                    </p>

                    <div id="selected-inputs"></div>

                    <div class="gb-card-footer">
                        <div>
                            <p class="gb-list-label p-0 m-0">Total</p>
                            <p class="gb-total" id="selected-total">0 tópicos na petição</p>
                        </div>
                        <button type="submit" name="action" value="review" class="btn btn-primary js-review-button" disabled>
                            Revisar petição <i data-lucide="arrow-right"></i>
                        </button>
                    </div>
                </section>

                <div class="card gb-hint-card">
                    <i data-lucide="file-text"></i>
                    <div>
                        <p class="gb-hint-title">Revise antes de gerar o documento</p>
                        <p class="gb-hint-text">Na próxima etapa você confere o texto de cada tópico e preenche as informações do caso. O documento é gerado em Word.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="gb-mobile-bar d-lg-none">
            <a href="#petition-title" class="gb-mobile-bar-summary">
                <span class="gb-list-label p-0">Minha petição</span>
                <span class="gb-total js-selected-label">0 tópicos</span>
            </a>
            <button type="submit" name="action" value="review" class="btn btn-primary js-review-button" disabled>
                Revisar petição <i data-lucide="arrow-right"></i>
            </button>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        (function () {
            const form = document.getElementById('petition-form');
            const items = Array.from(document.querySelectorAll('.gb-library-item'));
            const itemsById = new Map(items.map(item => [Number(item.dataset.id), item]));
            const reviewButtons = document.querySelectorAll('.js-review-button');
            const saveButton = document.querySelector('.js-save-button');
            const isSavedDraft = {{ $petition ? 'true' : 'false' }};

            const savedSelection = {{ Js::from($selection) }};
            const state = {
                preliminares: savedSelection
                    ? savedSelection.preliminares.filter(id => itemsById.has(id))
                    : items.filter(item => item.dataset.kind === 'preliminar').map(item => Number(item.dataset.id)),
                pedidos: savedSelection ? savedSelection.pedidos.filter(id => itemsById.has(id)) : [],
            };
            const nameInput = document.getElementById('petition-name');
            const snapshot = () => JSON.stringify([state.preliminares, state.pedidos, nameInput.value.trim()]);
            const savedSnapshot = snapshot();

            function refreshSave() {
                const hasItems = state.preliminares.length + state.pedidos.length > 0;
                const changed = !isSavedDraft || snapshot() !== savedSnapshot;
                saveButton.disabled = !hasItems || !changed;
                saveButton.title = !hasItems ? 'Adicione ao menos um tópico' : (!changed ? 'Nenhuma alteração para salvar' : '');
            }

            nameInput.addEventListener('input', refreshSave);

            function topicTitle(id) {
                return itemsById.get(id).querySelector('.gb-library-item-title').textContent.trim();
            }

            function topicGroup(id) {
                return itemsById.get(id).querySelector('.gb-library-item-group').textContent.trim();
            }

            function isSelected(id) {
                return state.preliminares.includes(id) || state.pedidos.includes(id);
            }

            function add(id) {
                const kind = itemsById.get(id).dataset.kind;
                if (isSelected(id)) { return; }
                if (kind === 'preliminar') {
                    state.preliminares = items
                        .filter(item => item.dataset.kind === 'preliminar')
                        .map(item => Number(item.dataset.id))
                        .filter(itemId => itemId === id || state.preliminares.includes(itemId));
                } else {
                    state.pedidos.push(id);
                }
                render();
            }

            function remove(id) {
                state.preliminares = state.preliminares.filter(itemId => itemId !== id);
                state.pedidos = state.pedidos.filter(itemId => itemId !== id);
                render();
            }

            function move(id, offset) {
                const from = state.pedidos.indexOf(id);
                const to = from + offset;
                if (from < 0 || to < 0 || to >= state.pedidos.length) { return; }
                state.pedidos.splice(to, 0, state.pedidos.splice(from, 1)[0]);
                render();
                document.querySelector(`[data-move="${offset}"][data-id="${id}"]`)?.focus();
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function selectedRow(id, position, total, reorderable) {
                const title = escapeHtml(topicTitle(id));
                const arrows = reorderable ? `
                    <button type="button" class="gb-icon-action" data-move="-1" data-id="${id}" ${position === 1 ? 'disabled' : ''} aria-label="Subir ${title}">
                        <i data-lucide="arrow-up"></i>
                    </button>
                    <button type="button" class="gb-icon-action" data-move="1" data-id="${id}" ${position === total ? 'disabled' : ''} aria-label="Descer ${title}">
                        <i data-lucide="arrow-down"></i>
                    </button>` : '';

                return `
                    <li class="gb-selected-item">
                        <span class="gb-order ${reorderable ? '' : 'gb-order-muted'}">${reorderable ? position : '<i data-lucide="check"></i>'}</span>
                        <div class="gb-selected-text">
                            <p class="gb-selected-title">${title}</p>
                            <p class="gb-selected-meta">${escapeHtml(topicGroup(id))}${reorderable ? ` · pedido ${position} de ${total}` : ''}</p>
                        </div>
                        <div class="gb-selected-actions">
                            ${arrows}
                            <button type="button" class="gb-icon-action gb-icon-action-danger" data-remove="${id}" aria-label="Remover ${title}">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </li>`;
            }

            function render() {
                items.forEach(item => item.classList.toggle('is-added', isSelected(Number(item.dataset.id))));

                document.getElementById('selected-preliminares-block').hidden = state.preliminares.length === 0;
                document.getElementById('selected-preliminares').innerHTML = state.preliminares
                    .map(id => selectedRow(id, 0, 0, false)).join('');
                document.getElementById('selected-pedidos').innerHTML = state.pedidos
                    .map((id, index) => selectedRow(id, index + 1, state.pedidos.length, true)).join('');
                document.getElementById('selected-empty').hidden = state.pedidos.length > 0;

                document.getElementById('selected-inputs').innerHTML =
                    state.preliminares.map(id => `<input type="hidden" name="preliminares[]" value="${id}">`).join('') +
                    state.pedidos.map(id => `<input type="hidden" name="topics[]" value="${id}">`).join('');

                const total = state.preliminares.length + state.pedidos.length;
                const label = total + (total === 1 ? ' tópico' : ' tópicos');
                document.getElementById('selected-count').textContent = label;
                document.getElementById('selected-total').textContent = label + ' na petição';
                document.querySelectorAll('.js-selected-label').forEach(element => { element.textContent = label; });
                reviewButtons.forEach(button => {
                    button.disabled = state.pedidos.length === 0;
                    button.title = state.pedidos.length === 0 ? 'Adicione ao menos um pedido' : '';
                });

                refreshSave();
                lucide.createIcons();
            }

            function filterLibrary() {
                const query = document.getElementById('library-search').value.trim().toLowerCase();
                const group = document.getElementById('library-group').value;
                let visible = 0;

                items.forEach(item => {
                    const matches = (!query || item.dataset.search.includes(query)) && (!group || item.dataset.group === group);
                    item.hidden = !matches;
                    visible += matches ? 1 : 0;
                });

                document.getElementById('library-empty').hidden = visible > 0;
            }

            document.getElementById('library-list').addEventListener('click', event => {
                const item = event.target.closest('.gb-library-item');
                if (!item) { return; }
                const id = Number(item.dataset.id);

                if (event.target.closest('.js-add')) { add(id); }
                if (event.target.closest('.js-remove')) { remove(id); }

                const toggle = event.target.closest('.js-toggle-content');
                if (toggle) {
                    const expanded = item.classList.toggle('is-expanded');
                    toggle.setAttribute('aria-expanded', String(expanded));
                    toggle.textContent = expanded ? 'Ocultar conteúdo' : 'Ver conteúdo';
                }
            });

            document.querySelector('.gb-sticky-column').addEventListener('click', event => {
                const moveButton = event.target.closest('[data-move]');
                if (moveButton) { move(Number(moveButton.dataset.id), Number(moveButton.dataset.move)); }

                const removeButton = event.target.closest('[data-remove]');
                if (removeButton) { remove(Number(removeButton.dataset.remove)); }
            });

            document.getElementById('library-search').addEventListener('input', filterLibrary);
            document.getElementById('library-group').addEventListener('change', filterLibrary);

            window.gbIsDirty = () => !saveButton.disabled;

            render();
        })();
    </script>
@endsection
