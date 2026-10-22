@extends('layouts.app')

@section('title', 'Gerar documento')

@section('page-header')
    <x-page-header title="Gerar documento"
                   description="Sua petição foi revisada. Prepare o arquivo para download."
                   :breadcrumbs="['Petições' => route('petition.index'), $petition->name]">
        <x-slot:actions>
            <button type="button" class="btn btn-white js-cancel-flow" data-item="a petição"
                    data-list-url="{{ route('petition.index') }}"
                    @if ($petition->exists) data-delete-url="{{ route('petition.destroy', $petition) }}" @endif
                    @cannot('petitions.delete') data-can-delete="false" @endcannot>
                <i data-lucide="x"></i> Cancelar
            </button>
            <span class="gb-badge {{ $petition->isGenerated() ? 'gb-badge-success' : 'gb-badge-neutral' }}" id="status-badge">
                {{ $petition->isGenerated() ? 'Gerada' : 'Rascunho' }}
            </span>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <x-petition-steps :current="3" :petition="$petition" />

    <div class="row g-3 align-items-start">
        <div class="col-12 col-lg-6">
            <form class="card" id="generate-form" method="POST" action="{{ route('petition.generate', $petition) }}" novalidate>
                @csrf
                <div class="card-header">
                    <h2 class="card-title">Configurações do arquivo</h2>
                    <p class="card-description">Conteúdo e ordem preservados da revisão.</p>
                </div>
                <div class="card-body d-flex flex-column gap-3 p-4">
                    <div>
                        <label for="file-name" class="form-label">Nome do arquivo</label>
                        <div class="input-group">
                            <input type="text" id="file-name" name="file_name" class="form-control" maxlength="120"
                                   value="{{ $petition->file_name ? \Illuminate\Support\Str::beforeLast($petition->file_name, '.docx') : \Illuminate\Support\Str::slug($petition->name) }}">
                            <span class="input-group-text">.docx</span>
                        </div>
                        <div class="invalid-feedback d-block" id="file-name-error" hidden>Informe o nome do arquivo para gerar o documento.</div>
                        <p class="gb-field-hint">Você poderá renomear o arquivo depois de baixar.</p>
                    </div>

                    <div>
                        <p class="form-label">Formato do documento</p>
                        <div class="gb-format-option is-selected">
                            <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="file-pen-line"></i></span>
                            <div class="flex-grow-1">
                                <p class="gb-petition-title">Word (.docx)</p>
                                <p class="gb-petition-meta">Continue editando no Word. Para PDF, use “Salvar como PDF” no Word.</p>
                            </div>
                            <span class="gb-badge gb-badge-neutral">Recomendado</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="layout-template"></i></span>
                        <div>
                            <p class="gb-petition-title">Modelo GB Advocacia</p>
                            <p class="gb-petition-meta">Timbrado do escritório · numeração dos tópicos</p>
                        </div>
                    </div>

                    <div class="gb-ready-box" id="ready-box" role="status" @if ($petition->isGenerated()) hidden @endif>
                        <i data-lucide="circle-check"></i>
                        <div>
                            <p class="gb-petition-title">Tudo pronto para gerar</p>
                            <p class="gb-petition-meta">{{ $petition->items->count() }} tópicos revisados · nenhuma informação pendente</p>
                        </div>
                    </div>

                    <div class="gb-ready-box gb-download-box" id="download-box" role="status" @unless ($petition->isGenerated()) hidden @endunless>
                        <i data-lucide="circle-check"></i>
                        <div class="flex-grow-1">
                            <p class="gb-petition-title">Documento gerado e baixado</p>
                            <p class="gb-petition-meta" id="download-name">{{ $petition->file_name }}</p>
                            <p class="gb-petition-meta" id="download-date">
                                @if ($petition->generated_at)
                                    Gerado em {{ $petition->generated_at->locale('pt_BR')->format('d/m/Y').' às '.$petition->generated_at->format('H:i') }} · Word (.docx)
                                @endif
                            </p>
                            <p class="gb-petition-meta mt-1">O arquivo não fica guardado no sistema. Para baixar de novo, use “Gerar novamente”.</p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" id="generate-button" @if ($petition->isGenerated()) hidden @endif>
                        <i data-lucide="file-text"></i> Gerar documento
                    </button>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2" id="regenerate-row" @unless ($petition->isGenerated()) hidden @endunless>
                        <button type="submit" class="btn btn-white" id="regenerate-button"><i data-lucide="refresh-cw"></i> Gerar novamente</button>
                        <span class="gb-field-hint m-0">O rascunho continua disponível para ajustes.</span>
                    </div>
                    <p class="gb-field-hint justify-content-center m-0" id="generate-hint" @if ($petition->isGenerated()) hidden @endif>
                        A geração leva cerca de um minuto. O rascunho continua disponível para ajustes.
                    </p>
                </div>
            </form>
        </div>

        <div class="col-12 col-lg-6">
            <section class="card" aria-labelledby="summary-title">
                <div class="card-header d-flex align-items-center gap-3">
                    <div class="flex-grow-1">
                        <h2 class="card-title" id="summary-title">Resumo da petição</h2>
                        <p class="card-description">{{ $petition->name }}</p>
                    </div>
                    <span class="gb-badge gb-badge-brand">{{ $petition->items->count() }} tópicos</span>
                </div>
                <p class="gb-list-label">Ordem do documento</p>
                <ol class="gb-selected-list">
                    @foreach ($petition->items as $item)
                        <li class="gb-selected-item">
                            <span class="gb-order {{ $item->kind === \App\Models\PetitionItem::KIND_PRELIMINAR ? 'gb-order-muted' : '' }}">{{ $loop->iteration }}</span>
                            <div class="gb-selected-text">
                                <p class="gb-selected-title">{{ $item->title }}</p>
                                <p class="gb-selected-meta">{{ $item->group_name }}</p>
                            </div>
                            <i data-lucide="check" class="text-success"></i>
                        </li>
                    @endforeach
                </ol>
                <div class="card-body">
                    <button type="button" class="btn btn-white w-100" data-bs-toggle="modal" data-bs-target="#full-petition">
                        <i data-lucide="eye"></i> Ver conteúdo revisado
                    </button>
                </div>
            </section>
        </div>
    </div>

    <div class="gb-form-actions">
        <a href="{{ route('petition.review', $petition) }}" class="btn btn-white"><i data-lucide="arrow-left"></i> Voltar à revisão</a>
        <span class="gb-field-hint m-0">Seleção, ordem e textos preservados no rascunho.</span>
    </div>

    <div class="modal fade" id="full-petition" tabindex="-1" aria-labelledby="full-petition-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="card-title" id="full-petition-title">Conteúdo revisado</h2>
                        <p class="card-description">{{ $petition->name }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="gb-document-preview">
                        @foreach ($petition->items as $item)
                            <p class="gb-document-title">{{ $item->title }}</p>
                            @if (trim((string) $item->topic?->notes) !== '')
                                <p class="gb-document-note"><strong>Orientação interna:</strong> {!! nl2br(e(trim($item->topic->notes))) !!}</p>
                            @endif
                            <div class="gb-rich-text">{!! $item->renderedContent() !!}</div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-white" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const form = document.getElementById('generate-form');
            const buttons = [document.getElementById('generate-button'), document.getElementById('regenerate-button')];
            const show = (id, visible) => { document.getElementById(id).hidden = !visible; };
            const fileName = document.getElementById('file-name');
            const fileNameInput = () => fileName.value.trim();
            let generating = false;
            const refreshButtons = () => {
                const empty = fileName.value.trim() === '';
                buttons.forEach(button => {
                    button.disabled = generating || empty;
                    button.title = empty ? 'Informe o nome do arquivo' : '';
                });
                fileName.classList.toggle('is-invalid', empty);
                document.getElementById('file-name-error').hidden = !empty;
            };
            fileName.addEventListener('input', refreshButtons);
            refreshButtons();
            window.gbIsDirty = () => generating;

            form.addEventListener('submit', event => {
                event.preventDefault();
                const labels = buttons.map(button => button.innerHTML);
                if (fileName.value.trim() === '') { return; }
                generating = true;
                form.setAttribute('aria-busy', 'true');
                buttons.forEach(button => {
                    button.disabled = true;
                    button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Gerando documento…';
                });

                gbGeneration.run(form.action, new FormData(form), new FormData(form).get('_token'))
                .then(({ result }) => gbGeneration.download(result.download_url, result.file_name).then(() => ({ fileName: result.file_name, generatedAt: result.generated_at })))
                .then(({ fileName, generatedAt }) => {
                    document.getElementById('download-name').textContent = fileName;
                    document.getElementById('download-date').textContent = generatedAt ? `Gerado em ${generatedAt} · Word (.docx)` : 'Word (.docx)';
                    document.getElementById('status-badge').textContent = 'Gerada';
                    document.getElementById('status-badge').className = 'gb-badge gb-badge-success';
                    show('ready-box', false);
                    show('generate-button', false);
                    show('generate-hint', false);
                    show('download-box', true);
                    show('regenerate-row', true);
                    document.getElementById('regenerate-button').focus();
                })
                .catch(error => Swal.fire({ icon: 'error', title: 'Não foi possível gerar', text: error.message, confirmButtonColor: 'var(--df-brand-01)' }))
                .finally(() => {
                    form.removeAttribute('aria-busy');
                    generating = false;
                    buttons.forEach((button, index) => { button.innerHTML = labels[index]; });
                    refreshButtons();
                    lucide.createIcons();
                });
            });
        })();
    </script>
@endsection
