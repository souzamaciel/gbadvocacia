@extends('layouts.app')

@section('title', 'Tópicos')

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
                Tópicos
            </h1>
            <p style="color:white;font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:13px;margin:0;">
                Gerencie os tópicos de pedidos vinculados aos grupos.
            </p>
        </div>

        <a href="{{ route('settings.topics.create') }}"
           class="btn btn-sm d-inline-flex align-items-center gap-2 flex-shrink-0"
           style="background-color:var(--gb-navy);color:#fff;font-size:13px;font-family:'Roboto',sans-serif;">
            <i class="fa-solid fa-plus"></i> Novo tópico
        </a>

        <nav class="d-none d-sm-flex align-items-center gap-1 flex-shrink-0 ps-4"
             style="font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:20px;color:white;">
            <a href="{{ route('home') }}" class="text-decoration-none" style="color:white;">Home</a>
            <span class="px-1" style="color:white;">/</span>
            <span style="color:white;">Tópicos</span>
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
    .btn-action { background:none; border:none; padding:4px 8px; cursor:pointer; font-size:14px; border-radius:4px; transition:background .15s; text-decoration:none; }
    .btn-action:hover { background:#f1f5f9; }
    .btn-action.edit { color:var(--gb-navy); }
    .btn-action.delete { color:#dc3545; }
    .badge-group { background:#eef2ff; color:var(--gb-navy); font-size:11px; font-weight:500; padding:3px 8px; border-radius:10px; }
    .badge-count { background:#f1f5f9; color:#64748b; font-size:11px; font-weight:500; padding:3px 8px; border-radius:10px; }
    .empty-state { text-align:center; padding:3rem 1rem; color:#94a3b8; }
    .empty-state i { font-size:2rem; margin-bottom:.75rem; display:block; }
    .topic-content-preview { color:#94a3b8; font-size:12px; max-width:300px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
</style>
@endsection

@section('content')
    <div class="container-fluid px-4 px-sm-5 pb-5">
        <div class="mx-auto" style="max-width: 1000px;">

            @if (session('status'))
                <div class="alert alert-success d-flex align-items-center gap-2 border-0 shadow-sm"
                     style="background:#ecfdf5; color:#065f46; font-size:13px;">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <div class="section-card">
                <div class="section-header">
                    <span class="section-badge"><i class="fa-solid fa-file-lines" style="font-size:12px;"></i></span>
                    <h6 class="section-title">Tópicos cadastrados</h6>
                    <span class="badge-count ms-auto">{{ $topics->count() }} tópico(s)</span>
                </div>

                @if ($topics->isEmpty())
                    <div class="empty-state">
                        <i class="fa-regular fa-file"></i>
                        <p class="mb-1 fw-medium" style="color:#64748b;">Nenhum tópico cadastrado</p>
                        <small>Clique em "Novo tópico" para começar.</small>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-settings">
                            <thead>
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Título</th>
                                    <th>Grupo</th>
                                    <th>Conteúdo</th>
                                    <th style="width:100px;" class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topics as $index => $topic)
                                    <tr>
                                        <td style="color:#94a3b8;">{{ $index + 1 }}</td>
                                        <td class="fw-medium">{{ $topic->title }}</td>
                                        <td>
                                            <span class="badge-group">{{ $topic->group->name }}</span>
                                        </td>
                                        <td>
                                            <div class="topic-content-preview">{{ strip_tags($topic->content) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('settings.topics.edit', $topic) }}"
                                               class="btn-action edit"
                                               title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn-action delete"
                                                    onclick="deleteTopic({{ $topic->id }}, '{{ addslashes($topic->title) }}')"
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

    function deleteTopic(id, title) {
        Swal.fire({
            title: 'Excluir tópico',
            html: 'Tem certeza que deseja excluir o tópico <strong>' + title + '</strong>?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            fetch('/settings/topics/' + id, {
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
