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
            return [route('petition.generate.show', $petition), 'Último Word gerado em '.$petition->generated_at?->format('d/m/Y')];
        }

        if ($petition->items_count > 0 && $petition->reviewed_items_count === $petition->items_count) {
            return [route('petition.generate.show', $petition), 'Pronta para gerar'];
        }

        if ($petition->reviewed_items_count > 0) {
            return [route('petition.review', $petition), 'Revisar conteúdo'];
        }

        return [route('petition.edit', $petition), 'Selecionar e organizar'];
    };
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
                <h2 id="petitions-title" class="card-title">Rascunhos</h2>
                <p class="card-description">Os rascunhos ficam salvos. O documento Word é baixado ao gerar e não fica guardado.</p>
            </div>
            <span class="gb-badge gb-badge-neutral">{{ $petitions->count() }} {{ $petitions->count() === 1 ? 'rascunho' : 'rascunhos' }}</span>
        </div>

        @if ($petitions->isNotEmpty())
            <div class="gb-library-filters justify-content-end">
                <div class="gb-input-icon gb-library-group">
                    <i data-lucide="search"></i>
                    <label for="petition-search" class="visually-hidden">Buscar rascunho</label>
                    <input type="search" id="petition-search" class="form-control" placeholder="Buscar rascunho…" autocomplete="off">
                </div>
            </div>
        @endif

        @if ($petitions->isEmpty())
            <div class="gb-library-empty">
                <i data-lucide="file-plus"></i>
                <p class="gb-hint-title">Nenhum rascunho ainda</p>
                <p class="m-0">Clique em “Nova petição” para começar.</p>
            </div>
        @else
            <table class="gb-table">
                <thead>
                    <tr>
                        <th scope="col">Petição</th>
                        <th scope="col">Última atualização</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody id="petition-rows">
                    @foreach ($petitions as $petition)
                        @php([$url, $stageLabel] = $stage($petition))
                        <tr data-search="{{ mb_strtolower($petition->name) }}">
                            <td>
                                <div class="gb-petition-name">
                                    <span class="gb-icon-tile"><i data-lucide="file-pen-line"></i></span>
                                    <div>
                                        <p class="gb-petition-title">{{ $petition->name }}</p>
                                        <p class="gb-petition-meta">
                                            {{ $petition->items_count }} {{ $petition->items_count === 1 ? 'tópico' : 'tópicos' }} · {{ $stageLabel }}
                                            @if ($petition->user) · {{ $petition->user->name }} @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $petition->updated_at->locale('pt_BR')->calendar() }}</td>
                            <td>
                                <div class="d-flex justify-content-md-end align-items-center gap-1">
                                    <a href="{{ $url }}" class="btn btn-white btn-sm">Continuar <i data-lucide="arrow-right"></i></a>
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
            <p class="gb-library-empty" id="petitions-empty" hidden>Nenhum rascunho encontrado.</p>
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
            function apply() {
                const query = (document.getElementById('petition-search')?.value || '').trim().toLowerCase();
                let visible = 0;
                rows.forEach(row => {
                    const show = !query || row.dataset.search.includes(query);
                    row.hidden = !show;
                    visible += show ? 1 : 0;
                });
                const empty = document.getElementById('petitions-empty');
                if (empty) { empty.hidden = visible > 0; }
            }

            document.getElementById('petition-search')?.addEventListener('input', apply);

            document.querySelectorAll('.js-delete-petition').forEach(form => form.addEventListener('submit', event => {
                event.preventDefault();
                Swal.fire({
                    title: 'Excluir rascunho?',
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
