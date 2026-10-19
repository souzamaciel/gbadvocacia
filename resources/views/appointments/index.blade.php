@extends('layouts.app')

@section('title', 'Atendimentos')

@section('page-header')
    <x-page-header title="Atendimentos"
                   description="Comece um atendimento ou continue um rascunho."
                   :breadcrumbs="['Atendimentos']">
        <x-slot:actions>
            @can('appointment_form.manage')
                <a href="{{ route('admin.form.index') }}" class="btn btn-white"><i data-lucide="settings-2"></i> Configurar ficha</a>
            @endcan
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
                <h2 id="appointments-title" class="card-title">Rascunhos de atendimento</h2>
                <p class="card-description">Salve o atendimento a qualquer momento e continue depois. A senha GOV não fica salva no rascunho.</p>
            </div>
            <span class="gb-badge gb-badge-neutral">{{ $appointments->count() }} {{ $appointments->count() === 1 ? 'rascunho' : 'rascunhos' }}</span>
        </div>

        @if ($appointments->isNotEmpty())
            <div class="gb-library-filters justify-content-end">
                <div class="gb-input-icon gb-library-group">
                    <i data-lucide="search"></i>
                    <label for="appointment-search" class="visually-hidden">Buscar rascunho</label>
                    <input type="search" id="appointment-search" class="form-control" placeholder="Buscar pelo nome do cliente…" autocomplete="off">
                </div>
            </div>

            <table class="gb-table">
                <thead>
                    <tr>
                        <th scope="col">Cliente</th>
                        <th scope="col">Última atualização</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody id="appointment-rows">
                    @foreach ($appointments as $appointment)
                        <tr data-search="{{ mb_strtolower($appointment->displayName()) }}">
                            <td>
                                <div class="gb-petition-name">
                                    <span class="gb-icon-tile"><i data-lucide="clipboard-pen-line"></i></span>
                                    <div>
                                        <p class="gb-petition-title">{{ $appointment->displayName() }}</p>
                                        <p class="gb-petition-meta">
                                            {{ count($appointment->pedidos ?? []) }} {{ count($appointment->pedidos ?? []) === 1 ? 'pedido' : 'pedidos' }}
                                            @if ($appointment->generated_at) · Petição gerada em {{ $appointment->generated_at->format('d/m/Y') }} @endif
                                            @if ($appointment->user) · {{ $appointment->user->name }} @endif
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $appointment->updated_at->locale('pt_BR')->calendar() }}</td>
                            <td>
                                <div class="d-flex justify-content-md-end align-items-center gap-1">
                                    <a href="{{ route('appointment.edit', $appointment) }}" class="btn btn-white btn-sm">Continuar <i data-lucide="arrow-right"></i></a>
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
            <p class="gb-library-empty" id="appointments-empty" hidden>Nenhum rascunho encontrado.</p>
        @else
            <div class="gb-library-empty">
                <i data-lucide="clipboard-list"></i>
                <p class="gb-hint-title">Nenhum rascunho de atendimento</p>
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
            document.getElementById('appointment-search')?.addEventListener('input', event => {
                const query = event.target.value.trim().toLowerCase();
                let visible = 0;
                rows.forEach(row => {
                    row.hidden = query !== '' && !row.dataset.search.includes(query);
                    visible += row.hidden ? 0 : 1;
                });
                document.getElementById('appointments-empty').hidden = visible > 0;
            });

            document.querySelectorAll('.js-delete-appointment').forEach(form => form.addEventListener('submit', event => {
                event.preventDefault();
                Swal.fire({
                    title: 'Excluir rascunho?',
                    text: `O rascunho do atendimento “${form.dataset.name}” será excluído.`,
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
