@php($suggestionArea = \App\Models\Suggestion::areaForRoute(request()->route()?->getName()))
<div class="modal fade" id="gb-suggestion" tabindex="-1" aria-labelledby="gb-suggestion-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="gb-suggestion-form" action="{{ route('suggestions.store') }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            <input type="hidden" name="page_url" value="{{ url()->current() }}">
            <input type="hidden" name="page_title" id="gb-suggestion-page">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title card-title" id="gb-suggestion-title">Sugerir melhoria</h2>
                    <p class="card-description m-0">Conte o que pode ficar melhor, o que falta ou o que não funciona. Leva um minuto.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <fieldset class="mb-3">
                    <legend class="form-label">Tipo</legend>
                    <div class="gb-suggestion-choices">
                        @foreach (\App\Models\Suggestion::TYPES as $value => $label)
                            <label class="gb-suggestion-choice">
                                <input type="radio" name="type" value="{{ $value }}" @checked($loop->first)>
                                <i data-lucide="{{ ['melhoria' => 'wand-sparkles', 'novo' => 'lightbulb', 'erro' => 'bug'][$value] }}"></i>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="gb-suggestion-area" class="form-label">Área do sistema</label>
                        <select id="gb-suggestion-area" name="area" class="form-select">
                            @foreach (\App\Models\Suggestion::AREAS as $value => $label)
                                <option value="{{ $value }}" @selected($value === $suggestionArea)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="gb-suggestion-impact" class="form-label">Quanto isso afeta o seu trabalho?</label>
                        <select id="gb-suggestion-impact" name="impact" class="form-select">
                            @foreach (\App\Models\Suggestion::IMPACTS as $value => $label)
                                <option value="{{ $value }}" @selected($value === 'ajudaria')>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="gb-suggestion-name" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" id="gb-suggestion-name" name="title" class="form-control" maxlength="150" required placeholder="Ex.: Poder duplicar uma petição já gerada">
                    </div>
                    <div class="col-12">
                        <label for="gb-suggestion-description" class="form-label">Descrição <span class="text-danger">*</span></label>
                        <textarea id="gb-suggestion-description" name="description" class="form-control" rows="5" maxlength="5000" required placeholder="O que você gostaria? Se for um erro: o que fez, o que esperava e o que aconteceu."></textarea>
                    </div>
                    <div class="col-12">
                        <label for="gb-suggestion-images" class="form-label" id="gb-suggestion-images-label">Prints da tela <span class="gb-field-hint d-inline">(opcional · até 3 imagens de 5 MB)</span></label>
                        <div class="gb-suggestion-files">
                            <input type="file" id="gb-suggestion-images" name="images[]" class="visually-hidden" accept="image/png,image/jpeg,image/webp,image/gif" multiple>
                            <label for="gb-suggestion-images" class="btn btn-white btn-sm m-0"><i data-lucide="image-plus"></i> Escolher imagens</label>
                            <span class="gb-field-hint m-0" id="gb-suggestion-files-name">Nenhuma imagem escolhida</span>
                        </div>
                    </div>
                </div>
                <p class="gb-field-hint mt-3 mb-0"><i data-lucide="info"></i> A sugestão vai para o responsável pelo sistema com o seu nome e a tela em que você está.</p>
                <p class="gb-footer-hint is-warning mt-2 mb-0" id="gb-suggestion-error" role="alert" hidden></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="gb-suggestion-submit"><i data-lucide="send"></i> Enviar sugestão</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function () {
        const form = document.getElementById('gb-suggestion-form');
        const modal = document.getElementById('gb-suggestion');
        const error = document.getElementById('gb-suggestion-error');
        const submit = document.getElementById('gb-suggestion-submit');

        modal.addEventListener('show.bs.modal', () => {
            document.getElementById('gb-suggestion-page').value = document.title.replace(/\s+—\s+GB Advocacia$/, '');
            error.hidden = true;
        });
        modal.addEventListener('shown.bs.modal', () => document.getElementById('gb-suggestion-name').focus());

        const fileInput = document.getElementById('gb-suggestion-images');
        const fileName = document.getElementById('gb-suggestion-files-name');
        function showFiles() {
            const names = [...fileInput.files].map(file => file.name);
            fileName.textContent = names.length === 0 ? 'Nenhuma imagem escolhida'
                : (names.length === 1 ? '1 imagem: ' : names.length + ' imagens: ') + names.join(', ');
        }
        fileInput.addEventListener('change', showFiles);
        form.addEventListener('reset', () => setTimeout(showFiles, 0));
        lucide.createIcons();

        function showError(text) {
            error.innerHTML = `<i data-lucide="circle-alert"></i><span></span>`;
            error.querySelector('span').textContent = text;
            error.hidden = false;
            lucide.createIcons();
        }

        form.addEventListener('submit', event => {
            event.preventDefault();
            event.stopPropagation();
            const files = document.getElementById('gb-suggestion-images').files;
            if (form.elements['title'].value.trim().length < 5) { showError('Dê um título curto para a sugestão (pelo menos 5 caracteres).'); return; }
            if (form.elements['description'].value.trim().length < 10) { showError('Conte um pouco mais na descrição (pelo menos 10 caracteres).'); return; }
            if (files.length > 3) { showError('Envie no máximo 3 imagens.'); return; }
            if ([...files].some(file => file.size > 5 * 1024 * 1024)) { showError('Cada imagem pode ter no máximo 5 MB.'); return; }

            const label = submit.innerHTML;
            submit.disabled = true;
            submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Enviando…';

            fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json' }, body: new FormData(form) })
                .then(response => response.json().catch(() => ({})).then(data => {
                    if (!response.ok) {
                        throw new Error(data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Não foi possível enviar agora. Tente novamente.'));
                    }
                    bootstrap.Modal.getOrCreateInstance(modal).hide();
                    form.reset();
                    Swal.fire({ icon: 'success', title: 'Sugestão enviada', text: data.message, confirmButtonColor: 'var(--df-brand-01)' });
                }))
                .catch(err => showError(err.message || 'Não foi possível enviar agora. Tente novamente.'))
                .finally(() => { submit.disabled = false; submit.innerHTML = label; lucide.createIcons(); });
        });
    })();
</script>
