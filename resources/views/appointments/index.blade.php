@extends('layouts.app')

@section('title', 'Atendimentos')

@php
    $generated = $appointments->whereNotNull('generated_at')->count();
    $drafts = $appointments->count() - $generated;
@endphp

@section('page-header')
    <x-page-header title="Atendimentos"
                   description="Comece um atendimento ou continue um rascunho."
                   :breadcrumbs="['Atendimentos']">
        <x-slot:actions>
            @can('appointment_form.manage')
                <a href="{{ route('admin.form.index') }}" class="btn btn-white"><i data-lucide="settings-2"></i> Configurar ficha</a>
            @endcan
            <a href="{{ route('appointment.import') }}" class="btn btn-white"><i data-lucide="file-up"></i> Importar ficha (Word)</a>
            <a href="{{ route('appointment.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Novo atendimento</a>
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

    <section class="card" aria-labelledby="appointments-title">
        <div class="card-header d-flex align-items-center gap-3">
            <div class="flex-grow-1">
                <h2 id="appointments-title" class="card-title">Seus atendimentos</h2>
                <p class="card-description">Rascunhos e atendimentos com petição gerada. O Word não fica guardado e a senha GOV não é salva.</p>
            </div>
            <span class="gb-badge gb-badge-neutral">{{ $appointments->count() }} {{ $appointments->count() === 1 ? 'atendimento' : 'atendimentos' }}</span>
        </div>

        @if ($appointments->isNotEmpty())
            <div class="gb-library-filters justify-content-between">
                <div class="btn-group" role="group" aria-label="Filtrar atendimentos">
                    <button type="button" class="btn btn-white active" data-filter="" aria-pressed="true">Todos · {{ $appointments->count() }}</button>
                    <button type="button" class="btn btn-white" data-filter="draft" aria-pressed="false" @disabled($drafts === 0)>Rascunhos · {{ $drafts }}</button>
                    <button type="button" class="btn btn-white" data-filter="generated" aria-pressed="false" @disabled($generated === 0)>Geradas · {{ $generated }}</button>
                </div>
                <div class="gb-input-icon gb-library-group">
                    <i data-lucide="search"></i>
                    <label for="appointment-search" class="visually-hidden">Buscar atendimento</label>
                    <input type="search" id="appointment-search" class="form-control" placeholder="Buscar pelo nome do cliente…" autocomplete="off">
                </div>
            </div>

            <table class="gb-table">
                <thead>
                    <tr>
                        <th scope="col">Cliente</th>
                        <th scope="col">Situação</th>
                        <th scope="col">Última atualização</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody id="appointment-rows">
                    @foreach ($appointments as $appointment)
                        <tr data-status="{{ $appointment->generated_at ? 'generated' : 'draft' }}" data-search="{{ mb_strtolower($appointment->displayName()) }}">
                            <td>
                                <div class="gb-petition-name">
                                    <span class="gb-icon-tile"><i data-lucide="{{ $appointment->generated_at ? 'clipboard-check' : 'clipboard-pen-line' }}"></i></span>
                                    <div>
                                        <p class="gb-petition-title">{{ $appointment->displayName() }}</p>
                                        <p class="gb-petition-meta">
                                            {{ count($appointment->pedidos ?? []) }} {{ count($appointment->pedidos ?? []) === 1 ? 'pedido' : 'pedidos' }}
                                            @if ($appointment->generated_at) · Petição gerada em {{ $appointment->generated_at->format('d/m/Y') }} às {{ $appointment->generated_at->format('H:i') }} @endif
                                            @if ($appointment->import_file_name) · Importado do Word @endif
                                            @if ($appointment->user) · {{ $appointment->user->name }} @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($appointment->generated_at)
                                    <span class="gb-badge gb-badge-success">Gerada</span>
                                @else
                                    <span class="gb-badge gb-badge-warning">Rascunho</span>
                                @endif
                            </td>
                            <td>{{ $appointment->updated_at->locale('pt_BR')->calendar() }}</td>
                            <td>
                                <div class="d-flex justify-content-md-end align-items-center gap-1">
                                    @if ($appointment->petition)
                                        @can('petitions.edit')
                                            <a href="{{ route('petition.review', $appointment->petition) }}" class="btn btn-white btn-sm"><i data-lucide="file-text"></i> Abrir petição</a>
                                        @endcan
                                    @endif
                                    <a href="{{ route('appointment.edit', $appointment) }}" class="btn btn-white btn-sm">{{ $appointment->generated_at ? 'Abrir' : 'Continuar' }} <i data-lucide="arrow-right"></i></a>
                                    <form method="POST" action="{{ route('appointment.destroy', $appointment) }}" class="js-delete-appointment" data-name="{{ $appointment->displayName() }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="gb-icon-action gb-icon-action-danger" aria-label="Excluir {{ $appointment->displayName() }}"><i data-lucide="trash-2"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="gb-library-empty" id="appointments-empty" hidden>Nenhum atendimento encontrado.</p>
        @else
            <div class="gb-library-empty">
                <i data-lucide="clipboard-list"></i>
                <p class="gb-hint-title">Nenhum atendimento ainda</p>
                <p class="m-0">Clique em “Novo atendimento” para começar. Use “Salvar rascunho” para continuar depois.</p>
            </div>
        @endif

        <div class="gb-list-summary gb-list-summary-footer">
            <span>Ordenados pela atualização mais recente</span>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('#appointment-rows tr'));
            let status = '';

            function apply() {
                const query = (document.getElementById('appointment-search')?.value || '').trim().toLowerCase();
                let visible = 0;
                rows.forEach(row => {
                    row.hidden = (status !== '' && row.dataset.status !== status) || (query !== '' && !row.dataset.search.includes(query));
                    visible += row.hidden ? 0 : 1;
                });
                const empty = document.getElementById('appointments-empty');
                if (empty) { empty.hidden = visible > 0; }
            }

            document.getElementById('appointment-search')?.addEventListener('input', apply);
            document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
                status = button.dataset.filter;
                document.querySelectorAll('[data-filter]').forEach(other => {
                    other.classList.toggle('active', other === button);
                    other.setAttribute('aria-pressed', String(other === button));
                });
                apply();
            }));

            document.querySelectorAll('.js-delete-appointment').forEach(form => form.addEventListener('submit', event => {
                event.preventDefault();
                Swal.fire({
                    title: 'Excluir atendimento?',
                    text: `O atendimento “${form.dataset.name}” será excluído do sistema.`,
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
