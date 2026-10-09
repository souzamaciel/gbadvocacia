@extends('layouts.app')

@section('title', 'Grupos')

@section('fonts')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
@endsection

@section('navbar-class', 'bg-dark bg-opacity-10')

@section('body-font', "'Inter', sans-serif")

@section('page-header')
    <div class="container-fluid px-4 px-sm-5 py-4 d-flex align-items-center gap-2">
        <div class="flex-shrink-0" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
            <a href="{{ route('home') }}">
                <i class="fa-regular fa-circle-left text-white fs-5"></i>
            </a>
        </div>

        <div class="flex-grow-1 d-flex flex-column gap-2">
            <h1 style="color: var(--gb-gold);font-size:24px;font-family:'Roboto',sans-serif;font-weight:500;line-height:26.15px;margin:0;">
                Grupos
            </h1>
            <p style="color:white;font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:13px;margin:0;">
                Gerencie os grupos de pedidos disponíveis no sistema.
            </p>
        </div>

        <button type="button" onclick="createGroup()"
                class="btn btn-sm d-inline-flex align-items-center gap-2 flex-shrink-0"
                style="background-color:var(--gb-navy);color:#fff;font-size:13px;font-family:'Roboto',sans-serif;">
            <i class="fa-solid fa-plus"></i> Novo grupo
        </button>

        <nav class="d-none d-sm-flex align-items-center gap-1 flex-shrink-0 ps-4"
             style="font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:20px;color:white;">
            <a href="{{ route('home') }}" class="text-decoration-none" style="color:white;">Home</a>
            <span class="px-1" style="color:white;">/</span>
            <span style="color:white;">Grupos</span>
        </nav>
    </div>
@endsection

@section('styles')
<style>
    .section-card { background:#fff; border:1px solid #e2e8f0; border-radius:.5rem; margin-bottom:1rem; }
    .section-header { padding:.75rem 1.25rem; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:.75rem; }
    .section-badge { width:28px;height:28px;min-width:28px; border-radius:4px; background-color:var(--gb-navy); color:white; font-size:11px;font-weight:700; display:flex;align-items:center;justify-content:center; }
    .section-title { font-size:12px;font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#1e293b; margin:0; }
    .table-settings { font-size:13px; color:#314158; margin-bottom:0; }
    .table-settings thead th { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#64748b; border-bottom:2px solid #e2e8f0; padding:.75rem 1rem; }
    .table-settings tbody td { padding:.75rem 1rem; vertical-align:middle; border-bottom:1px solid #f1f5f9; }
    .table-settings tbody tr:hover { background:#f8fafc; }
    .btn-action { background:none; border:none; padding:4px 8px; cursor:pointer; font-size:14px; border-radius:4px; transition:background .15s; }
    .btn-action:hover { background:#f1f5f9; }
    .btn-action.edit { color:var(--gb-navy); }
    .btn-action.delete { color:#dc3545; }
    .badge-count { background:#f1f5f9; color:#64748b; font-size:11px; font-weight:500; padding:3px 8px; border-radius:10px; }
    .empty-state { text-align:center; padding:3rem 1rem; color:#94a3b8; }
    .empty-state i { font-size:2rem; margin-bottom:.75rem; display:block; }
</style>
@endsection

@section('content')
    <div class="container-fluid px-4 px-sm-5 pb-5">
        <div class="mx-auto" style="max-width: 800px;">
            <div class="section-card">
                <div class="section-header">
                    <span class="section-badge"><i class="fa-solid fa-layer-group" style="font-size:12px;"></i></span>
                    <h6 class="section-title">Grupos cadastrados</h6>
                    <span class="badge-count ms-auto">{{ $groups->count() }} grupo(s)</span>
                </div>

                @if ($groups->isEmpty())
                    <div class="empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <p class="mb-1 fw-medium" style="color:#64748b;">Nenhum grupo cadastrado</p>
                        <small>Clique em "Novo grupo" para começar.</small>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-settings">
                            <thead>
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Nome</th>
                                    <th style="width:100px;" class="text-center">Tópicos</th>
                                    <th style="width:100px;" class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups as $index => $group)
                                    <tr id="group-row-{{ $group->id }}">
                                        <td style="color:#94a3b8;">{{ $index + 1 }}</td>
                                        <td class="fw-medium" id="group-name-{{ $group->id }}">{{ $group->name }}</td>
                                        <td class="text-center">
                                            <span class="badge-count">{{ $group->topics_count }}</span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button"
                                                    class="btn-action edit"
                                                    onclick="editGroup({{ $group->id }}, '{{ addslashes($group->name) }}')"
                                                    title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn-action delete"
                                                    onclick="deleteGroup({{ $group->id }}, '{{ addslashes($group->name) }}')"
                                                    title="Excluir">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    var csrfToken = '{{ csrf_token() }}';

    function createGroup() {
        Swal.fire({
            title: 'Novo grupo',
            input: 'text',
            inputLabel: 'Nome do grupo',
            inputPlaceholder: 'Ex: Verbas Rescisórias',
            inputAttributes: { maxlength: 100 },
            showCancelButton: true,
            confirmButtonColor: '#232A54',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Salvar',
            cancelButtonText: 'Cancelar',
            inputValidator: function (value) {
                if (!value || !value.trim()) {
                    return 'Informe o nome do grupo.';
                }
            },
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            fetch('{{ route('settings.groups.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name: result.value.trim() }),
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        confirmButtonColor: '#232A54',
                    }).then(function () { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message, confirmButtonColor: '#232A54' });
                }
            })
            .catch(function (err) {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão. Tente novamente.', confirmButtonColor: '#232A54' });
            });
        });
    }

    function editGroup(id, currentName) {
        Swal.fire({
            title: 'Editar grupo',
            input: 'text',
            inputLabel: 'Nome do grupo',
            inputValue: currentName,
            inputAttributes: { maxlength: 100 },
            showCancelButton: true,
            confirmButtonColor: '#232A54',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Salvar',
            cancelButtonText: 'Cancelar',
            inputValidator: function (value) {
                if (!value || !value.trim()) {
                    return 'Informe o nome do grupo.';
                }
            },
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            fetch('/settings/groups/' + id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name: result.value.trim() }),
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        confirmButtonColor: '#232A54',
                    }).then(function () { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message, confirmButtonColor: '#232A54' });
                }
            })
            .catch(function () {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão. Tente novamente.', confirmButtonColor: '#232A54' });
            });
        });
    }

    function deleteGroup(id, name) {
        Swal.fire({
            title: 'Excluir grupo',
            html: 'Tem certeza que deseja excluir o grupo <strong>' + name + '</strong>?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            fetch('/settings/groups/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Excluído!',
                        text: data.message,
                        confirmButtonColor: '#232A54',
                    }).then(function () { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.message, confirmButtonColor: '#232A54' });
                }
            })
            .catch(function () {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de conexão. Tente novamente.', confirmButtonColor: '#232A54' });
            });
        });
    }
</script>
@endsection
