@props(['preliminares' => []])

<section class="card h-100 no-print" aria-labelledby="preliminares-title">
    <div class="card-header">
        <h3 id="preliminares-title" class="card-title">Preliminares</h3>
        <p id="preliminares-count" class="card-description">{{ count($preliminares) }} {{ count($preliminares) === 1 ? 'preliminar selecionada' : 'preliminares selecionadas' }}</p>
    </div>

    <div class="card-body d-flex flex-column gap-2">
        @foreach ($preliminares as $preliminar)
            <div class="form-check">
                <input type="checkbox"
                       name="preliminares[]"
                       value="{{ $preliminar->id }}"
                       id="preliminar-{{ $preliminar->id }}"
                       class="form-check-input preliminar-checkbox"
                       checked
                       onchange="onPreliminarChange()">
                <label class="form-check-label" for="preliminar-{{ $preliminar->id }}">{{ $preliminar->title }}</label>
            </div>
        @endforeach
    </div>
</section>

<script>
    function onPreliminarChange() {
        const total = document.querySelectorAll('.preliminar-checkbox:checked').length;
        document.getElementById('preliminares-count').textContent =
            total + ' preliminar' + (total !== 1 ? 'es' : '') + ' selecionada' + (total !== 1 ? 's' : '');
    }
</script>
