@props(['pedidos' => [], 'preliminares' => null, 'buttonText' => 'Finalizar atendimento'])

<div class="flex-shrink-0 no-print" style="width: 100%; max-width: 453px;">

    <div class="card shadow-lg border-0 rounded-4 overflow-hidden"
         style="min-height: 780px; max-height: 780px; font-family: 'Inter', sans-serif;">
        <div class="card-body p-4 d-flex flex-column gap-4" style="height: 100%; min-height: 0;">

            {{-- Header --}}
            <div>
                <h5 class="fw-bold mb-1" style="color: #1E293B;">Pedidos</h5>
                <small id="pedidos-count" class="text-secondary">0 pedidos selecionados</small>
            </div>

            {{-- Campo de busca com autocomplete --}}
            <div class="position-relative" id="pedidos-search-content">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-color: #D1D5DC;">
                        <i class="fa-solid fa-magnifying-glass" style="color: var(--gb-gold);"></i>
                    </span>
                    <input
                        type="text"
                        id="pedidos-search"
                        class="form-control border-start-0 ps-0"
                        placeholder="Pesquisar pedido"
                        autocomplete="off"
                        oninput="onPedidoSearch(this.value)"
                        onfocus="onPedidoSearch(this.value)"
                        onblur="setTimeout(hideSuggestions, 150)"
                        style="border-color: #D1D5DC;"
                    >
                </div>
                {{-- Dropdown de sugestões --}}
                <div id="pedidos-suggestions"
                     class="position-absolute w-100 bg-white rounded-3 shadow border d-none"
                     style="top: calc(100% + 4px); z-index: 50; max-height: 220px; overflow-y: auto; border-color: #D1D5DC !important;">
                </div>
            </div>

            {{-- Itens selecionados --}}
            <div class="d-flex flex-column flex-grow-1" style="overflow-y: auto; min-height: 0;">
                @if ($preliminares !== null)
                    <div id="preliminares-selected" class="d-none mb-3">
                        <small class="fw-semibold text-uppercase text-secondary" style="font-size: 11px; letter-spacing: .05em;">Preliminares</small>
                        <div id="preliminares-list-container" class="d-flex flex-column"></div>
                    </div>
                @endif
                <div id="pedidos-list-container" class="d-flex flex-column"></div>
            </div>

            {{-- Botão --}}
            <button type="submit"
                    id="btn-submit-appointment"
                    class="btn w-100 py-3 mt-auto text-white fw-medium"
                    style="background-color: var(--gb-navy);"
                    onmouseover="this.style.opacity='.9'"
                    onmouseout="this.style.opacity='1'">
                {{ $buttonText }}
            </button>

        </div>
    </div>
</div>

<script>
    const allPedidos = @json($pedidos);
    let selectedPedidos = [];
    let dragSrcIndex = null;

    const allPreliminares = @json($preliminares ?? []);
    let selectedPreliminares = [];

    function togglePreliminar(id, checked) {
        selectedPreliminares = checked
            ? [...new Set([...selectedPreliminares, id])]
            : selectedPreliminares.filter(selectedId => selectedId !== id);
        renderPreliminares();
    }

    function removePreliminar(id) {
        const checkbox = document.querySelector(`.preliminar-checkbox[data-id="${id}"]`);
        if (checkbox) { checkbox.checked = false; }
        togglePreliminar(id, false);
    }

    function renderPreliminares() {
        const block = document.getElementById('preliminares-selected');
        if (!block) { return; }

        const chosen = allPreliminares.filter(p => selectedPreliminares.includes(p.id));
        block.classList.toggle('d-none', chosen.length === 0);
        document.getElementById('preliminares-list-container').innerHTML = chosen.map(p => `
            <div class="d-flex align-items-center gap-2 py-2 border-bottom preliminar-item">
                <span class="flex-shrink-0" style="width: 20px;"></span>
                <div class="d-flex gap-2 flex-grow-1 align-items-start">
                    <span class="fw-bold flex-shrink-0" style="color: var(--gb-gold); font-size: 16px;">&bull;</span>
                    <span style="color: var(--gb-text); font-size: 15px; font-weight: 500; line-height: 1.5;">${p.title}</span>
                </div>
                <i class="fa-solid fa-xmark flex-shrink-0"
                   onclick="removePreliminar(${p.id})"
                   style="color: #C0392B; font-size: 16px; width: 20px; text-align: center; cursor: pointer;"></i>
                <input type="hidden" name="preliminares[]" value="${p.id}">
            </div>
        `).join('');
    }

    function onPedidoSearch(query) {
        const q = query.trim().toLowerCase();
        const suggestions = document.getElementById('pedidos-suggestions');

        const available = allPedidos.filter(p =>
            !selectedPedidos.some(s => s.id === p.id) && (q === '' || p.title.toLowerCase().includes(q))
        );

        if (available.length === 0) {
            hideSuggestions();
            return;
        }

        suggestions.innerHTML = '';
        available.forEach(pedido => {
            const div = document.createElement('div');
            div.textContent = pedido.title;
            div.className = 'px-3 py-2 cursor-pointer border-bottom';
            div.style.cssText = 'font-size: 13px; color: #314158; cursor: pointer;';
            div.addEventListener('mouseover', () => div.style.background = '#F8FAFC');
            div.addEventListener('mouseout',  () => div.style.background = 'white');
            div.addEventListener('mousedown', () => addPedido(pedido));
            suggestions.appendChild(div);
        });

        suggestions.classList.remove('d-none');
    }

    function hideSuggestions() {
        document.getElementById('pedidos-suggestions').classList.add('d-none');
    }

    function addPedido(pedido) {
        if (selectedPedidos.some(s => s.id === pedido.id)) { return; }
        selectedPedidos.push(pedido);
        document.getElementById('pedidos-search').value = '';
        hideSuggestions();
        renderPedidos();
    }

    function removePedido(index) {
        const [removed] = selectedPedidos.splice(index, 1);
        const checkbox = document.querySelector(`.pedido-checkbox[data-id="${removed.id}"]`);
        if (checkbox) { checkbox.checked = false; }
        renderPedidos();
    }

    function renderPedidos() {
        const container = document.getElementById('pedidos-list-container');
        container.innerHTML = selectedPedidos.map((p, i) => `
            <div class="d-flex align-items-center gap-2 py-2 border-bottom pedido-item"
                 draggable="true"
                 data-index="${i}">
                <i class="fa-solid fa-up-down flex-shrink-0 text-secondary"
                   style="font-size: 13px; width: 20px; text-align: center; cursor: grab;"></i>
                <div class="d-flex gap-2 flex-grow-1 align-items-start">
                    <span class="fw-bold flex-shrink-0" style="color: var(--gb-gold); font-size: 16px;">${i + 1}.</span>
                    <span style="color: var(--gb-text); font-size: 15px; font-weight: 500; line-height: 1.5;">${p.title}</span>
                </div>
                <i class="fa-solid fa-xmark flex-shrink-0"
                   onclick="removePedido(${i})"
                   style="color: #C0392B; font-size: 16px; width: 20px; text-align: center; cursor: pointer;"></i>
                <input type="hidden" name="topics[]" value="${p.id}">
            </div>
        `).join('');

        attachDragEvents();
        updatePedidosCount();
    }

    function attachDragEvents() {
        document.querySelectorAll('.pedido-item').forEach(item => {
            item.addEventListener('dragstart', () => {
                dragSrcIndex = parseInt(item.dataset.index);
                setTimeout(() => item.style.opacity = '0.4', 0);
            });

            item.addEventListener('dragend', () => {
                item.style.opacity = '1';
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                item.style.background = '#F8FAFC';
            });

            item.addEventListener('dragleave', () => {
                item.style.background = '';
            });

            item.addEventListener('drop', (e) => {
                e.preventDefault();
                item.style.background = '';
                const targetIndex = parseInt(item.dataset.index);
                if (dragSrcIndex === null || dragSrcIndex === targetIndex) { return; }
                const moved = selectedPedidos.splice(dragSrcIndex, 1)[0];
                selectedPedidos.splice(targetIndex, 0, moved);
                dragSrcIndex = null;
                renderPedidos();
            });
        });
    }

    function updatePedidosCount() {
        const total = selectedPedidos.length;
        document.getElementById('pedidos-count').textContent =
            total + ' pedido' + (total !== 1 ? 's' : '') + ' selecionado' + (total !== 1 ? 's' : '');
    }
</script>
