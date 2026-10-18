@extends('layouts.app')

@section('title', 'Usuários')

@php
    $active = $users->where('is_active', true)->count();
    $inactive = $users->count() - $active;
@endphp

@section('page-header')
    <x-page-header title="Usuários"
                   description="Quem acessa o sistema e o que cada um pode fazer."
                   :breadcrumbs="['Administração', 'Usuários']">
        <x-slot:actions>
            @can('roles.manage')
                <a href="{{ route('admin.roles.index') }}" class="btn btn-white"><i data-lucide="shield-check"></i> Perfis de permissão</a>
            @endcan
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i data-lucide="user-plus"></i> Novo usuário</a>
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
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            <i data-lucide="circle-alert"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar aviso"></button>
        </div>
    @endif

    <section class="card" aria-labelledby="users-title">
        <div class="card-header d-flex align-items-center gap-3">
            <div class="flex-grow-1">
                <h2 id="users-title" class="card-title">Usuários do escritório</h2>
                <p class="card-description">Usuários desativados não entram no sistema, mas as petições deles continuam salvas.</p>
            </div>
            <span class="gb-badge gb-badge-neutral">{{ $users->count() }} {{ $users->count() === 1 ? 'usuário' : 'usuários' }}</span>
        </div>

        <div class="gb-library-filters justify-content-between">
            <div class="btn-group" role="group" aria-label="Filtrar usuários">
                <button type="button" class="btn btn-white active" data-filter="active" aria-pressed="true">Ativos · {{ $active }}</button>
                <button type="button" class="btn btn-white" data-filter="inactive" aria-pressed="false" @disabled($inactive === 0)>Desativados · {{ $inactive }}</button>
            </div>
            <div class="gb-input-icon gb-library-group">
                <i data-lucide="search"></i>
                <label for="user-search" class="visually-hidden">Buscar usuário</label>
                <input type="search" id="user-search" class="form-control" placeholder="Buscar por nome ou e-mail…" autocomplete="off">
            </div>
        </div>

        <table class="gb-table">
            <thead>
                <tr>
                    <th scope="col">Usuário</th>
                    <th scope="col">Perfil</th>
                    <th scope="col">Último acesso</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody id="user-rows">
                @foreach ($users as $user)
                    @php($isMe = $user->is(auth()->user()))
                    @php($canManage = auth()->user()->canManageUser($user))
                    <tr data-status="{{ $user->is_active ? 'active' : 'inactive' }}" data-search="{{ mb_strtolower($user->name.' '.$user->email) }}" @unless ($user->is_active) hidden @endunless>
                        <td>
                            <div class="gb-petition-name">
                                <span class="gb-icon-tile"><i data-lucide="{{ $user->is_active ? 'user-round' : 'user-round-x' }}"></i></span>
                                <div>
                                    <p class="gb-petition-title">
                                        {{ $user->name }}
                                        @if ($isMe) <span class="gb-badge gb-badge-neutral ms-1">Você</span> @endif
                                        @unless ($user->is_active) <span class="gb-badge gb-badge-warning ms-1">Desativado</span> @endunless
                                        @if ($user->is_active && $user->must_change_password) <span class="gb-badge gb-badge-warning ms-1">Senha provisória</span> @endif
                                    </p>
                                    <p class="gb-petition-meta">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->role?->name ?? 'Sem perfil' }}</td>
                        <td>{{ $user->last_login_at?->locale('pt_BR')->calendar() ?? 'Nunca acessou' }}</td>
                        <td>
                            <div class="d-flex justify-content-md-end align-items-center gap-1">
                                @if ($isMe)
                                    <a href="{{ route('profile.edit') }}" class="btn btn-white btn-sm">Meu perfil <i data-lucide="arrow-right"></i></a>
                                @elseif ($canManage)
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-white btn-sm"><i data-lucide="pencil"></i> Editar</a>
                                    <a href="{{ route('admin.users.edit', $user) }}#senha" class="gb-icon-action" aria-label="Redefinir a senha de {{ $user->name }}" title="Redefinir senha"><i data-lucide="key-round"></i></a>
                                    <form method="POST" action="{{ route('admin.users.status', $user) }}" class="js-toggle-user" data-name="{{ $user->name }}" data-active="{{ $user->is_active ? '1' : '0' }}">
                                        @csrf
                                        @method('PUT')
                                        @if ($user->is_active)
                                            <button type="submit" class="gb-icon-action gb-icon-action-danger" aria-label="Desativar {{ $user->name }}" title="Desativar acesso"><i data-lucide="user-x"></i></button>
                                        @else
                                            <button type="submit" class="gb-icon-action" aria-label="Reativar {{ $user->name }}" title="Reativar acesso"><i data-lucide="user-check"></i></button>
                                        @endif
                                    </form>
                                @else
                                    <span class="gb-petition-meta">Só administradores alteram</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="gb-library-empty" id="users-empty" hidden>Nenhum usuário encontrado.</p>

        <div class="gb-list-summary gb-list-summary-footer">
            <span>Ordenados por nome</span>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('#user-rows tr'));
            let status = 'active';

            function apply() {
                const query = document.getElementById('user-search').value.trim().toLowerCase();
                let visible = 0;
                rows.forEach(row => {
                    const show = row.dataset.status === status && (!query || row.dataset.search.includes(query));
                    row.hidden = !show;
                    visible += show ? 1 : 0;
                });
                document.getElementById('users-empty').hidden = visible > 0;
            }

            document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
                status = button.dataset.filter;
                document.querySelectorAll('[data-filter]').forEach(other => {
                    other.classList.toggle('active', other === button);
                    other.setAttribute('aria-pressed', String(other === button));
                });
                apply();
            }));
            document.getElementById('user-search').addEventListener('input', apply);

            document.querySelectorAll('.js-toggle-user').forEach(form => form.addEventListener('submit', event => {
                event.preventDefault();
                const deactivating = form.dataset.active === '1';
                Swal.fire({
                    title: deactivating ? 'Desativar acesso?' : 'Reativar acesso?',
                    text: deactivating
                        ? `${form.dataset.name} não conseguirá mais entrar no sistema. As petições feitas por ele continuam salvas.`
                        : `${form.dataset.name} voltará a entrar no sistema com a senha atual.`,
                    icon: deactivating ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: deactivating ? 'var(--df-danger)' : 'var(--df-brand-01)',
                    confirmButtonText: deactivating ? 'Desativar' : 'Reativar',
                    cancelButtonText: 'Cancelar',
                }).then(result => { if (result.isConfirmed) { form.submit(); } });
            }));
        })();
    </script>
@endsection
