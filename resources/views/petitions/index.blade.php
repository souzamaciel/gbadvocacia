@extends('layouts.app')

@section('title', 'Petições iniciais')

@php
    /**
     * Where to continue a petition and how to describe its stage.
     *
     * @return array{0: string, 1: string}
     */
    $stage = function (\App\Models\Petition $petition): array {
        if ($petition->isGenerated()) {
            return [route('petition.generate.show', $petition), 'Documento gerado'];
        }

        if ($petition->items_count > 0 && $petition->reviewed_items_count === $petition->items_count) {
            return [route('petition.generate.show', $petition), 'Pronta para gerar'];
        }

        if ($petition->reviewed_items_count > 0) {
            return [route('petition.review', $petition), 'Revisar conteúdo'];
        }

        return [route('petition.edit', $petition), 'Selecionar e organizar'];
    };
    $drafts = $petitions->where('status', \App\Models\Petition::STATUS_DRAFT)->count();
    $generated = $petitions->count() - $drafts;
@endphp

@section('page-header')
    <x-page-header title="Petições iniciais"
                   description="Comece uma petição ou continue de onde parou."
                   :breadcrumbs="['Petições', 'Petições iniciais']">
        <x-slot:actions>
            <a href="{{ route('petition.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Nova petição</a>
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

    @if ($continue)
        @php([$continueUrl, $continueStage] = $stage($continue))
        <section class="card gb-continue-card" aria-label="Continue de onde parou">
            <span class="gb-icon-tile"><i data-lucide="file-pen-line"></i></span>
            <div class="flex-grow-1">
                <p class="gb-step-eyebrow">Continue de onde parou</p>
                <h2 class="gb-petition-title fs-6">{{ $continue->name }}</h2>
                <p class="gb-petition-meta">{{ $continueStage }} · {{ $continue->items_count }} {{ $continue->items_count === 1 ? 'tópico selecionado' : 'tópicos selecionados' }}</p>
                <ul class="gb-continue-progress">
                    <li class="is-done"><i data-lucide="check"></i> Seleção concluída</li>
                    <li class="{{ $continue->reviewed_items_count === $continue->items_count && $continue->items_count > 0 ? 'is-done' : 'is-current' }}">
                        <i data-lucide="{{ $continue->reviewed_items_count === $continue->items_count && $continue->items_count > 0 ? 'check' : 'clock' }}"></i>
                        Revisão {{ $continue->reviewed_items_count }} de {{ $continue->items_count }}
                    </li>
                    <li><i data-lucide="file-text"></i> Gerar documento</li>
                </ul>
            </div>
            <a href="{{ $continueUrl }}" class="btn btn-primary">Continuar <i data-lucide="arrow-right"></i></a>
        </section>
    @endif

    <section class="card" aria-labelledby="petitions-title">
        <div class="card-header d-flex align-items-center gap-3">
            <div class="flex-grow-1">
                <h2 id="petitions-title" class="card-title">Suas petições</h2>
                <p class="card-description">Acompanhe rascunhos e documentos gerados</p>
            </div>
            <span class="gb-badge gb-badge-neutral">{{ $petitions->count() }} {{ $petitions->count() === 1 ? 'petição' : 'petições' }}</span>
        </div>

        <div class="gb-library-filters justify-content-between">
            <div class="btn-group" role="group" aria-label="Filtrar petições">
                <button type="button" class="btn btn-white active" data-filter="" aria-pressed="true">Todas · {{ $petitions->count() }}</button>
                <button type="button" class="btn btn-white" data-filter="draft" aria-pressed="false">Rascunhos · {{ $drafts }}</button>
                <button type="button" class="btn btn-white" data-filter="generated" aria-pressed="false">Geradas · {{ $generated }}</button>
            </div>
            <div class="gb-input-icon gb-library-group">
                <i data-lucide="search"></i>
                <label for="petition-search" class="visually-hidden">Buscar petição</label>
                <input type="search" id="petition-search" class="form-control" placeholder="Buscar petição…" autocomplete="off">
            </div>
        </div>

        @if ($petitions->isEmpty())
            <div class="gb-library-empty">
                <i data-lucide="file-plus"></i>
                <p class="gb-hint-title">Nenhuma petição ainda</p>
                <p class="m-0">Clique em “Nova petição” para começar.</p>
            </div>
        @else
            <table class="gb-table">
                <thead>
                    <tr>
                        <th scope="col">Petição</th>
                        <th scope="col">Status</th>
                        <th scope="col">Última atualização</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody id="petition-rows">
                    @foreach ($petitions as $petition)
                        @php([$url, $stageLabel] = $stage($petition))
                        <tr data-status="{{ $petition->status }}" data-search="{{ mb_strtolower($petition->name) }}">
                            <td>
                                <div class="gb-petition-name">
                                    <span class="gb-icon-tile"><i data-lucide="{{ $petition->isGenerated() ? 'file-text' : 'file-pen-line' }}"></i></span>
                                    <div>
                                        <p class="gb-petition-title">{{ $petition->name }}</p>
                                        <p class="gb-petition-meta">
                                            {{ $petition->items_count }} {{ $petition->items_count === 1 ? 'tópico' : 'tópicos' }} · {{ $stageLabel }}
                                            @if ($petition->user) · {{ $petition->user->name }} @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($petition->isGenerated())
                                    <span class="gb-badge gb-badge-success">Gerada</span>
                                @else
                                    <span class="gb-badge gb-badge-warning">Rascunho</span>
                                @endif
                            </td>
                            <td>{{ $petition->updated_at->locale('pt_BR')->calendar() }}</td>
                            <td>
                                <div class="d-flex justify-content-md-end align-items-center gap-1">
                                    @if ($petition->isGenerated())
                                        <a href="{{ route('petition.generate.show', $petition) }}" class="btn btn-white btn-sm"><i data-lucide="refresh-cw"></i> Gerar novamente</a>
                                        <a href="{{ route('petition.review', $petition) }}" class="gb-icon-action" aria-label="Revisar {{ $petition->name }}"><i data-lucide="pencil"></i></a>
                                    @else
                                        <a href="{{ $url }}" class="btn btn-white btn-sm">Continuar <i data-lucide="arrow-right"></i></a>
                                    @endif
                                    <form method="POST" action="{{ route('petition.destroy', $petition) }}" class="js-delete-petition" data-name="{{ $petition->name }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="gb-icon-action gb-icon-action-danger" aria-label="Excluir {{ $petition->name }}"><i data-lucide="trash-2"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="gb-library-empty" id="petitions-empty" hidden>Nenhuma petição encontrada.</p>
        @endif

        <div class="gb-list-summary gb-list-summary-footer">
            <span>Ordenadas pela atualização mais recente</span>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('#petition-rows tr'));
            let status = '';

            function apply() {
                const query = (document.getElementById('petition-search')?.value || '').trim().toLowerCase();
                let visible = 0;
                rows.forEach(row => {
                    const show = (!status || row.dataset.status === status) && (!query || row.dataset.search.includes(query));
                    row.hidden = !show;
                    visible += show ? 1 : 0;
                });
                const empty = document.getElementById('petitions-empty');
                if (empty) { empty.hidden = visible > 0; }
            }

            document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
                status = button.dataset.filter;
                document.querySelectorAll('[data-filter]').forEach(other => {
                    other.classList.toggle('active', other === button);
                    other.setAttribute('aria-pressed', String(other === button));
                });
                apply();
            }));
            document.getElementById('petition-search')?.addEventListener('input', apply);

            document.querySelectorAll('.js-delete-petition').forEach(form => form.addEventListener('submit', event => {
                event.preventDefault();
                Swal.fire({
                    title: 'Excluir petição?',
                    text: `O rascunho “${form.dataset.name}” será excluído.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--df-danger)',
                    confirmButtonText: 'Excluir',
                    cancelButtonText: 'Cancelar',
                }).then(result => { if (result.isConfirmed) { form.submit(); } });
            }));
        })();
    </script>
@endsection
