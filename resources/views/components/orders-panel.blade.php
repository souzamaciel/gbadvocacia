@props(['pedidos' => []])

<section class="card h-100 no-print" aria-labelledby="pedidos-title">
    <div class="card-header">
        <h3 id="pedidos-title" class="card-title">Pedidos</h3>
        <p id="pedidos-count" class="card-description">0 pedidos selecionados</p>
    </div>

    <div class="card-body">
        <div class="gb-input-icon position-relative">
            <i data-lucide="search"></i>
            <label for="pedidos-search" class="visually-hidden">Pesquisar pedido</label>
            <input type="text"
                   id="pedidos-search"
                   class="form-control"
                   placeholder="Pesquisar pedido para adicionar"
                   autocomplete="off"
                   role="combobox"
                   aria-expanded="false"
                   aria-controls="pedidos-suggestions"
                   oninput="onPedidoSearch(this.value)"
                   onfocus="onPedidoSearch(this.value)"
                   onblur="setTimeout(hideSuggestions, 150)">
            <ul id="pedidos-suggestions" class="gb-suggestions d-none" role="listbox"></ul>
        </div>
    </div>

    <p class="gb-list-label pt-0">Ordem dos pedidos</p>
    <ul id="pedidos-list-container" class="gb-selected-list" aria-live="polite"></ul>
    <p id="pedidos-empty" class="gb-selected-empty">Nenhum pedido adicionado. Pesquise acima para adicionar.</p>
</section>

<script>
    const allPedidos = @json($pedidos);
    let selectedPedidos = [];

    function escapePedidoText(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
            const item = document.createElement('li');
            item.textContent = pedido.title;
            item.setAttribute('role', 'option');
            item.addEventListener('mousedown', () => addPedido(pedido));
            suggestions.appendChild(item);
        });

        suggestions.classList.remove('d-none');
        document.getElementById('pedidos-search').setAttribute('aria-expanded', 'true');
    }

    function hideSuggestions() {
        document.getElementById('pedidos-suggestions').classList.add('d-none');
        document.getElementById('pedidos-search').setAttribute('aria-expanded', 'false');
    }

    function addPedido(pedido) {
        if (selectedPedidos.some(s => s.id === pedido.id)) { return; }
        selectedPedidos.push(pedido);
        document.getElementById('pedidos-search').value = '';
        hideSuggestions();
        renderPedidos();
    }

    function removePedido(index) {
        selectedPedidos.splice(index, 1);
        renderPedidos();
    }

    function movePedido(index, offset) {
        const target = index + offset;
        if (target < 0 || target >= selectedPedidos.length) { return; }
        selectedPedidos.splice(target, 0, selectedPedidos.splice(index, 1)[0]);
        renderPedidos();
    }

    function renderPedidos() {
        const total = selectedPedidos.length;
        document.getElementById('pedidos-list-container').innerHTML = selectedPedidos.map((p, i) => {
            const title = escapePedidoText(p.title);
            return `
                <li class="gb-selected-item">
                    <span class="gb-order">${i + 1}</span>
                    <div class="gb-selected-text"><p class="gb-selected-title">${title}</p></div>
                    <div class="gb-selected-actions">
                        <button type="button" class="gb-icon-action" onclick="movePedido(${i}, -1)" ${i === 0 ? 'disabled' : ''} aria-label="Subir ${title}"><i data-lucide="arrow-up"></i></button>
                        <button type="button" class="gb-icon-action" onclick="movePedido(${i}, 1)" ${i === total - 1 ? 'disabled' : ''} aria-label="Descer ${title}"><i data-lucide="arrow-down"></i></button>
                        <button type="button" class="gb-icon-action gb-icon-action-danger" onclick="removePedido(${i})" aria-label="Remover ${title}"><i data-lucide="trash-2"></i></button>
                    </div>
                    <input type="hidden" name="topics[]" value="${p.id}">
                </li>`;
        }).join('');

        document.getElementById('pedidos-empty').hidden = total > 0;
        document.getElementById('pedidos-count').textContent =
            total + ' pedido' + (total !== 1 ? 's' : '') + ' selecionado' + (total !== 1 ? 's' : '');

        if (window.lucide) { lucide.createIcons(); }
    }
</script>
