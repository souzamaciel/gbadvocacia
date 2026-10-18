/*
 * Novo tópico com IA: colar o texto → revisar as variáveis sugeridas → salvar o modelo.
 * Nenhuma variável entra no modelo sem aprovação do advogado.
 */
window.gbTopicVariables = (function () {
    const $ = id => document.getElementById(id);
    const PLACEHOLDER = /\[[^\[\]<>]+\]/g;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function cleanName(name) {
        return (name || '').replace(/[\[\]]/g, '').trim();
    }

    /** Walk the text nodes of an HTML string, letting `split` replace each node with new nodes. */
    function transformText(html, split) {
        const root = document.createElement('div');
        root.innerHTML = html;
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        const nodes = [];
        while (walker.nextNode()) { nodes.push(walker.currentNode); }
        nodes.forEach(node => {
            const replacement = split(node.nodeValue);
            if (replacement) { node.replaceWith(replacement); }
        });
        return root.innerHTML;
    }

    /** Find occurrences of the given passages in a text, longest first, without overlaps. */
    function occurrences(text, items) {
        const found = [];
        let index = 0;
        while (index < text.length) {
            let best = null;
            items.forEach(item => {
                const position = text.indexOf(item.trecho, index);
                if (position === -1) { return; }
                if (!best || position < best.position || (position === best.position && item.trecho.length > best.item.trecho.length)) {
                    best = { position, item };
                }
            });
            if (!best) { break; }
            found.push(best);
            index = best.position + best.item.trecho.length;
        }
        return found;
    }

    function splitWith(text, items, build) {
        const found = occurrences(text, items);
        if (!found.length) { return null; }
        const fragment = document.createDocumentFragment();
        let cursor = 0;
        found.forEach(({ position, item }) => {
            fragment.append(text.slice(cursor, position), build(item));
            cursor = position + item.trecho.length;
        });
        fragment.append(text.slice(cursor));
        return fragment;
    }

    function init({ editor, suggestUrl, csrfToken, canUseAi = true }) {
        const state = { step: 1, original: '', suggestions: [], selection: '', busy: false, editorRange: null, touched: new Set() };
        const identity = $('topic-identity');
        const notesBlock = $('topic-notes-block');

        function setAiChip(field, visible) {
            $(`${field}-ai-chip`).hidden = !visible;
        }
        const dialog = document.querySelector('#topic-modal .modal-dialog');

        /* ---------- Etapa 1 ---------- */
        function updateCount() {
            const names = new Set((editor.getText().match(PLACEHOLDER) || []));
            $('variables-count').textContent = names.size === 0
                ? 'Nenhuma variável no modelo ainda.'
                : `${names.size} ${names.size === 1 ? 'variável' : 'variáveis'} no modelo`;
        }

        /* Botões só ficam habilitados quando a ação pode ser executada. */
        function refreshButtons() {
            const hasText = editor.getText().trim() !== '';
            const hasTitle = $('topic-title').value.trim() !== '';
            const hasGroup = $('topic-group').value !== '';
            const hasSelection = !!(state.editorRange && state.editorRange.length > 0 && editor.getText(state.editorRange.index, state.editorRange.length).trim());

            $('ai-identify').disabled = state.busy || !hasText || !canUseAi;
            $('manual-variable-step1').disabled = state.busy || !hasText || !hasSelection;
            $('topic-save').disabled = state.busy || !hasTitle || !hasGroup || !hasText;

            const missing = [
                !hasTitle && { field: 'topic-title', label: 'título' },
                !hasGroup && { field: 'topic-group', label: 'grupo' },
                !hasText && { field: 'topic-editor', label: 'conteúdo do modelo' },
            ].filter(Boolean);
            $('step1-hint').textContent = !hasText
                ? (canUseAi ? 'Cole ou digite o texto para pedir sugestões à IA.' : 'Cole ou digite o texto do modelo.')
                : (!hasSelection ? 'Para criar uma variável manualmente, selecione um trecho no texto.' : '');

            const footer = $('topic-footer-hint');
            if (state.step !== 2) {
                footer.classList.toggle('is-warning', missing.length > 0);
                if (missing.length) {
                    const links = missing.map(item => `<button type="button" class="gb-hint-link" data-focus="${item.field}">${item.label}</button>`);
                    const list = links.length > 1 ? `${links.slice(0, -1).join(', ')} e ${links[links.length - 1]}` : links[0];
                    footer.innerHTML = `<i data-lucide="circle-alert"></i><span>Para salvar, preencha ${list}.</span>`;
                } else {
                    footer.innerHTML = `<i data-lucide="circle-check"></i><span>${state.step === 1 ? (canUseAi ? 'Pronto para salvar. Você também pode pedir sugestões de variáveis à IA.' : 'Pronto para salvar.') : 'Pronto para salvar. O modelo fica disponível para novas petições.'}</span>`;
                }
                lucide.createIcons();
            }

            // Campos obrigatórios em vermelho só depois de tocados ou na etapa final.
            $('topic-title').classList.toggle('is-invalid', !hasTitle && (state.step === 3 || state.touched.has('topic-title')));
            $('topic-group').classList.toggle('is-invalid', !hasGroup && (state.step === 3 || state.touched.has('topic-group')));
        }

        editor.on('text-change', () => { if (state.step === 1) { updateCount(); } refreshButtons(); });
        editor.on('selection-change', range => { state.editorRange = range; refreshButtons(); });
        $('topic-title').addEventListener('input', () => { setAiChip('title', false); refreshButtons(); });
        $('topic-group').addEventListener('change', () => { setAiChip('group', false); refreshButtons(); });
        ['topic-title', 'topic-group'].forEach(id => $(id).addEventListener('blur', () => { state.touched.add(id); refreshButtons(); }));

        $('topic-footer-hint').addEventListener('click', event => {
            const link = event.target.closest('[data-focus]');
            if (!link) { return; }
            const target = link.dataset.focus;
            state.touched.add(target);
            if (target === 'topic-editor') {
                if (state.step === 3) { goTo(1, { keepEditor: true }); }
                editor.focus();
            } else {
                $(target).focus();
                $(target).scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
            refreshButtons();
        });

        $('manual-variable-step1').addEventListener('mousedown', event => event.preventDefault());
        $('manual-variable-step1').addEventListener('click', () => {
            const range = editor.getSelection() || state.editorRange;
            if (!range || range.length === 0) { return; }
            const passage = editor.getText(range.index, range.length).trim();
            Swal.fire({
                title: 'Criar variável',
                html: `<p class="gb-ai-quote text-start">“${escapeHtml(passage)}”</p>`,
                input: 'text',
                inputLabel: 'Nome da variável',
                inputPlaceholder: 'Ex.: Salário',
                showCancelButton: true,
                confirmButtonText: 'Criar variável',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: 'var(--df-brand-01)',
                inputValidator: value => (cleanName(value) ? undefined : 'Informe o nome da variável.'),
            }).then(result => {
                if (!result.isConfirmed) { return; }
                editor.deleteText(range.index, range.length, 'user');
                editor.insertText(range.index, `[${cleanName(result.value)}]`, 'user');
            });
        });

        $('ai-identify').addEventListener('click', () => {
            const html = gbEditor.getHtml(editor);
            if (!html) { return; }

            const button = $('ai-identify');
            const label = button.innerHTML;
            state.busy = true;
            refreshButtons();
            button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Analisando o texto…';
            $('topic-step-1').setAttribute('aria-busy', 'true');
            editor.enable(false);

            fetch(suggestUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ content: html }),
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) { throw new Error(data.message || 'Não foi possível analisar o texto.'); }
                state.original = html;
                if (!$('topic-title').value.trim() && data.title) {
                    $('topic-title').value = data.title;
                    setAiChip('title', true);
                }
                if (!$('topic-group').value && data.group_id) {
                    $('topic-group').value = String(data.group_id);
                    setAiChip('group', true);
                }
                state.suggestions = data.suggestions.map((suggestion, index) => ({
                    ...suggestion,
                    id: index + 1,
                    name: cleanName(suggestion.nome),
                    status: 'pending',
                    manual: false,
                }));
                goTo(2);
            })
            .catch(error => Swal.fire({ icon: 'error', title: 'Não foi possível analisar', text: error.message, confirmButtonColor: 'var(--df-brand-01)' }))
            .finally(() => {
                state.busy = false;
                button.innerHTML = label;
                $('topic-step-1').removeAttribute('aria-busy');
                editor.enable(true);
                refreshButtons();
                lucide.createIcons();
            });
        });

        /* ---------- Etapa 2 ---------- */
        const pending = () => state.suggestions.filter(item => item.status === 'pending');

        function renderText() {
            const items = state.suggestions.map(item => ({ ...item }));
            $('ai-text').innerHTML = transformText(state.original, text => splitWith(text, items, item => {
                const mark = document.createElement('mark');
                mark.className = `gb-ai-mark is-${item.manual ? 'manual' : item.status}${item.atencao && item.status === 'pending' ? ' is-attention' : ''}`;
                mark.dataset.id = item.id;
                mark.textContent = item.trecho;
                const badge = document.createElement('sup');
                badge.className = 'gb-ai-badge';
                badge.textContent = item.id;
                mark.append(badge);
                return mark;
            }));
        }

        function card(item) {
            const statusLabel = {
                accepted: '<span class="gb-text-success"><i data-lucide="check"></i> Aceita</span>',
                kept: '<span class="gb-petition-meta">Texto mantido</span>',
                pending: item.atencao ? '<span class="gb-ai-attention"><i data-lucide="triangle-alert"></i> Confira</span>' : '<span class="gb-petition-meta">Pendente</span>',
            }[item.status];

            let body = `<p class="gb-ai-quote">“${escapeHtml(item.trecho)}”</p>`;
            if (item.status === 'pending') {
                body += `
                    <label class="form-label" for="ai-name-${item.id}">Nome da variável</label>
                    <div class="gb-input-icon">
                        <i data-lucide="braces"></i>
                        <input type="text" class="form-control js-ai-name" id="ai-name-${item.id}" data-id="${item.id}" maxlength="80" value="${escapeHtml(item.name)}">
                    </div>`;
                if (item.atencao) {
                    body += `<p class="gb-ai-warning"><i data-lucide="triangle-alert"></i> ${escapeHtml(item.motivo || 'Pode ser uma referência jurídica. Recomendamos manter o texto original.')}</p>
                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-primary btn-sm flex-fill" data-action="keep" data-id="${item.id}">Manter original</button>
                            <button type="button" class="btn btn-white btn-sm flex-fill" data-action="accept" data-id="${item.id}" ${cleanName(item.name) ? '' : 'disabled'}><i data-lucide="braces"></i> Usar variável</button>
                        </div>`;
                } else {
                    body += `<div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-primary btn-sm flex-fill" data-action="accept" data-id="${item.id}" ${cleanName(item.name) ? '' : 'disabled'}><i data-lucide="check"></i> Aceitar</button>
                            <button type="button" class="btn btn-white btn-sm flex-fill" data-action="keep" data-id="${item.id}">Manter original</button>
                        </div>`;
                }
            } else if (item.status === 'accepted') {
                body += `<p class="gb-ai-variable"><i data-lucide="braces"></i> ${escapeHtml(item.name)}</p>
                    <button type="button" class="btn btn-white btn-sm" data-action="undo" data-id="${item.id}"><i data-lucide="undo-2"></i> Desfazer</button>`;
            } else {
                body += `<p class="gb-petition-meta mb-2">O trecho continuará igual em todas as petições.</p>
                    <button type="button" class="btn btn-white btn-sm" data-action="undo" data-id="${item.id}"><i data-lucide="undo-2"></i> Desfazer</button>`;
            }

            return `<li class="gb-ai-card is-${item.status}${item.atencao && item.status === 'pending' ? ' is-attention' : ''}" data-id="${item.id}">
                <div class="gb-ai-card-header">
                    <span class="gb-ai-number">${item.id}</span>
                    <span class="gb-list-label p-0 flex-grow-1">${escapeHtml(item.manual ? 'Manual' : item.categoria)}</span>
                    ${statusLabel}
                </div>
                ${body}
            </li>`;
        }

        function renderSuggestions() {
            const total = state.suggestions.length;
            const reviewed = total - pending().length;
            $('ai-count').textContent = total;
            $('ai-suggestions').innerHTML = state.suggestions.map(card).join('');

            const banner = $('ai-banner');
            banner.classList.toggle('is-done', total > 0 && reviewed === total);
            if (total === 0) {
                banner.innerHTML = '<i data-lucide="sparkles"></i><span class="flex-grow-1">A IA não encontrou informações específicas do caso. Selecione um trecho no texto para criar uma variável manualmente.</span>';
            } else if (reviewed === total) {
                banner.innerHTML = `<i data-lucide="circle-check"></i><span class="flex-grow-1">Todas as sugestões foram revisadas. Selecione no texto um trecho que a IA não identificou para criar uma variável.</span><span class="gb-list-label p-0">${reviewed} de ${total} revisadas</span>`;
            } else {
                banner.innerHTML = `<i data-lucide="sparkles"></i><span class="flex-grow-1">A IA encontrou ${total} ${total === 1 ? 'informação específica' : 'informações específicas'} deste caso. Confira cada sugestão: aceite, renomeie ou mantenha o texto original.</span><span class="gb-list-label p-0">${reviewed} de ${total} revisadas</span>`;
            }

            renderText();
            updateFooter();
            lucide.createIcons();
        }

        $('ai-suggestions').addEventListener('input', event => {
            const input = event.target.closest('.js-ai-name');
            if (!input) { return; }
            state.suggestions.find(item => item.id === Number(input.dataset.id)).name = input.value;
            const accept = $('ai-suggestions').querySelector(`[data-action="accept"][data-id="${input.dataset.id}"]`);
            if (accept) { accept.disabled = !cleanName(input.value); }
        });

        $('ai-suggestions').addEventListener('click', event => {
            const button = event.target.closest('[data-action]');
            if (!button) { return; }
            const item = state.suggestions.find(entry => entry.id === Number(button.dataset.id));

            if (button.dataset.action === 'accept') {
                if (!cleanName(item.name)) { return; }
                item.name = cleanName(item.name);
                item.status = 'accepted';
            }
            if (button.dataset.action === 'keep') { item.status = 'kept'; }
            if (button.dataset.action === 'undo') {
                if (item.manual) {
                    state.suggestions = state.suggestions.filter(entry => entry !== item);
                } else {
                    item.status = 'pending';
                }
            }
            renderSuggestions();
        });

        /* Seleção manual no texto */
        function hidePopover() {
            $('ai-popover').hidden = true;
            state.selection = '';
        }

        function openPopover(passage) {
            state.selection = passage;
            $('ai-popover-text').textContent = `“${passage}”`;
            $('ai-popover-name').value = '';
            $('ai-popover-create').disabled = true;
            $('ai-popover').hidden = false;
            $('ai-popover-name').focus();
            lucide.createIcons();
        }

        $('ai-text').addEventListener('mouseup', () => {
            const selection = window.getSelection();
            const passage = selection ? selection.toString().trim() : '';
            if (!passage || !$('ai-text').contains(selection.anchorNode)) { return; }
            if (state.suggestions.some(item => item.trecho === passage)) { return; }
            openPopover(passage);
        });

        function selectedPassage() {
            const selection = window.getSelection();
            const passage = selection ? selection.toString().trim() : '';
            if (!passage || !$('ai-text').contains(selection.anchorNode) || state.suggestions.some(item => item.trecho === passage)) { return ''; }
            return passage;
        }

        document.addEventListener('selectionchange', () => {
            if (state.step === 2) { $('manual-variable-step2').disabled = selectedPassage() === ''; }
        });
        $('manual-variable-step2').addEventListener('mousedown', event => event.preventDefault());
        $('manual-variable-step2').addEventListener('click', () => {
            const passage = selectedPassage();
            if (passage) { openPopover(passage); }
        });

        $('ai-popover-cancel').addEventListener('click', hidePopover);
        $('ai-popover-name').addEventListener('input', () => {
            $('ai-popover-create').disabled = cleanName($('ai-popover-name').value) === '';
        });
        $('ai-popover-create').addEventListener('click', () => {
            const name = cleanName($('ai-popover-name').value);
            if (!name) { return; }
            const nextId = state.suggestions.reduce((max, item) => Math.max(max, item.id), 0) + 1;
            state.suggestions.push({ id: nextId, trecho: state.selection, name, categoria: 'MANUAL', atencao: false, motivo: '', status: 'accepted', manual: true });
            state.suggestions.sort((a, b) => state.original.indexOf(escapeHtml(a.trecho)) - state.original.indexOf(escapeHtml(b.trecho)));
            hidePopover();
            renderSuggestions();
        });
        $('ai-popover-name').addEventListener('keydown', event => {
            if (event.key === 'Enter') { event.preventDefault(); if (!$('ai-popover-create').disabled) { $('ai-popover-create').click(); } }
            if (event.key === 'Escape') { event.stopPropagation(); hidePopover(); }
        });

        /* ---------- Etapa 3 ---------- */
        function finalHtml() {
            const accepted = state.suggestions.filter(item => item.status === 'accepted');
            return transformText(state.original, text => splitWith(text, accepted, item => document.createTextNode(`[${item.name}]`)));
        }

        function renderFinal() {
            const accepted = state.suggestions.filter(item => item.status === 'accepted');
            const kept = state.suggestions.filter(item => item.status === 'kept');
            const html = finalHtml();
            gbEditor.setHtml(editor, html);

            $('ai-final').innerHTML = transformText(html, text => {
                const matches = [...text.matchAll(PLACEHOLDER)].map(match => ({ trecho: match[0] }));
                return splitWith(text, matches, item => {
                    const span = document.createElement('span');
                    span.className = 'gb-ai-var';
                    span.textContent = item.trecho;
                    return span;
                });
            });

            const names = [...new Set((editor.getText().match(PLACEHOLDER) || []).map(name => name.slice(1, -1)))];
            const aiFilled = !$('title-ai-chip').hidden || !$('group-ai-chip').hidden;
            $('ai-ready').innerHTML = `<i data-lucide="circle-check"></i><span>Modelo pronto. ${accepted.length} ${accepted.length === 1 ? 'variável aprovada' : 'variáveis aprovadas'}${kept.length ? ` e ${kept.length} ${kept.length === 1 ? 'trecho mantido' : 'trechos mantidos'} como texto original` : ''}. ${aiFilled ? 'Confira os dados do tópico abaixo antes de salvar.' : 'Confira os dados do tópico antes de salvar.'}</span>`;
            $('ai-final-count').textContent = names.length;
            $('ai-final-variables').innerHTML = accepted.length
                ? accepted.map(item => `<li><i data-lucide="braces"></i><div><p class="gb-petition-title">${escapeHtml(item.name)}</p><p class="gb-petition-meta">${escapeHtml(item.manual ? 'MANUAL' : item.categoria)} · ex.: ${escapeHtml(item.trecho)}</p></div></li>`).join('')
                : '<li class="gb-petition-meta">Nenhuma variável aprovada nesta análise.</li>';
            $('ai-final-kept').innerHTML = kept.map(item => `<p class="gb-preview-note"><i data-lucide="info"></i> “${escapeHtml(item.trecho)}” foi mantido como texto.</p>`).join('');
            $('ai-final-chips').innerHTML = names.length
                ? names.map(name => `<span class="gb-badge gb-badge-neutral text-none">${escapeHtml(name)}</span>`).join('')
                : '<span class="gb-petition-meta">Nenhum campo para preencher.</span>';
            lucide.createIcons();
        }

        $('ai-edit-text').addEventListener('click', () => goTo(1, { keepEditor: true }));

        /* ---------- Navegação ---------- */
        function updateFooter() {
            const hint = $('topic-footer-hint');
            $('topic-back').hidden = state.step === 1;
            $('topic-next').hidden = state.step !== 2;
            $('topic-save').hidden = state.step === 2;

            if (state.step === 2) {
                $('manual-variable-step2').disabled = selectedPassage() === '';
                const left = pending().length;
                $('topic-next').disabled = left > 0;
                hint.classList.toggle('is-warning', left > 0);
                hint.innerHTML = left > 0
                    ? `<i data-lucide="circle-alert"></i><span>Revise ${left === 1 ? 'a sugestão pendente' : `as ${left} sugestões pendentes`} para continuar.</span>`
                    : '<i data-lucide="circle-check"></i><span>Tudo revisado. Continue para ver o modelo.</span>';
                lucide.createIcons();
            }

            refreshButtons();
        }

        function goTo(step, { keepEditor = false } = {}) {
            if (step === 1 && state.step === 3 && !keepEditor) { gbEditor.setHtml(editor, state.original); }
            if (step === 2 && state.step === 3) { gbEditor.setHtml(editor, state.original); }
            if (step === 1) { state.suggestions = []; }

            state.step = step;
            hidePopover();
            if (step === 3) {
                $('ai-ready').after(identity, notesBlock);
                identity.classList.add('mb-3');
                notesBlock.classList.add('mb-3');
            } else if (identity.parentElement !== $('topic-step-1')) {
                $('topic-step-1').prepend(identity);
                $('topic-step-1').append(notesBlock);
                identity.classList.remove('mb-3');
                notesBlock.classList.remove('mb-3');
            }
            [1, 2, 3].forEach(number => { $(`topic-step-${number}`).hidden = number !== step; });
            document.querySelectorAll('#topic-steps li').forEach(item => {
                const number = Number(item.dataset.step);
                item.classList.toggle('is-active', number === step);
                item.classList.toggle('is-complete', number < step);
                item.querySelector('.gb-modal-step-number').innerHTML = number < step ? '<i data-lucide="check"></i>' : number;
            });
            dialog.classList.toggle('modal-xl', step > 1);
            dialog.classList.toggle('modal-lg', step === 1);

            if (step === 1) { updateCount(); }
            if (step === 2) { renderSuggestions(); }
            if (step === 3) { renderFinal(); }
            updateFooter();
            lucide.createIcons();
        }

        $('topic-form').addEventListener('keydown', event => {
            if (state.step === 2 && event.key === 'Enter' && event.target.tagName === 'INPUT') { event.preventDefault(); }
        });

        $('topic-back').addEventListener('click', () => goTo(state.step - 1));
        $('topic-next').addEventListener('click', () => goTo(3));

        return {
            reset() {
                state.step = 1;
                state.original = '';
                state.touched.clear();
                setAiChip('title', false);
                setAiChip('group', false);
                goTo(1, { keepEditor: true });
            },
        };
    }

    return { init };
})();
