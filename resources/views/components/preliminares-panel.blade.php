@props(['preliminares' => []])

<div class="card shadow-lg border-0 rounded-4 overflow-hidden no-print"
     style="font-family: 'Inter', sans-serif;">
    <div class="card-body p-4 d-flex flex-column gap-3">

        {{-- Header --}}
        <div>
            <h5 class="fw-bold mb-1" style="color: #1E293B;">Preliminares</h5>
            <small id="preliminares-count" class="text-secondary">{{ count($preliminares) }} {{ count($preliminares) === 1 ? 'preliminar selecionada' : 'preliminares selecionadas' }}</small>
        </div>

        {{-- Lista de checkboxes --}}
        <div class="d-flex flex-column gap-2">
            @foreach ($preliminares as $preliminar)
                <label class="d-flex align-items-center gap-2 m-0"
                       style="cursor: pointer; font-size: 13px; color: #314158; font-weight: 500;">
                    <input type="checkbox"
                           name="preliminares[]"
                           value="{{ $preliminar->id }}"
                           class="preliminar-checkbox"
                           checked
                           onchange="onPreliminarChange()"
                           style="width: 15px; height: 15px; accent-color: var(--gb-navy);">
                    {{ $preliminar->title }}
                </label>
            @endforeach
        </div>

    </div>
</div>

<script>
    function onPreliminarChange() {
        const total = document.querySelectorAll('.preliminar-checkbox:checked').length;
        document.getElementById('preliminares-count').textContent =
            total + ' preliminar' + (total !== 1 ? 'es' : '') + ' selecionada' + (total !== 1 ? 's' : '');
    }
</script>
