@extends('layouts.app')

@section('title', 'Perfis de permissão')

@section('page-header')
    <x-page-header title="Perfis de permissão"
                   description="Defina o que cada tipo de usuário pode fazer no sistema."
                   :breadcrumbs="auth()->user()->can('users.manage') ? ['Usuários' => route('admin.users.index'), 'Perfis de permissão'] : ['Administração', 'Perfis de permissão']">
        <x-slot:actions>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary"><i data-lucide="plus"></i> Novo perfil</a>
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

    <section class="card" aria-labelledby="roles-title">
        <div class="card-header d-flex align-items-center gap-3">
            <div class="flex-grow-1">
                <h2 id="roles-title" class="card-title">Perfis</h2>
                <p class="card-description">Cada usuário tem um perfil. Mudar um perfil vale na hora para todos que o usam.</p>
            </div>
            <span class="gb-badge gb-badge-neutral">{{ $roles->count() }} {{ $roles->count() === 1 ? 'perfil' : 'perfis' }}</span>
        </div>

        <table class="gb-table">
            <thead>
                <tr>
                    <th scope="col">Perfil</th>
                    <th scope="col">Permissões</th>
                    <th scope="col">Usuários</th>
                    <th scope="col" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    @php($total = count(\App\Enums\Permission::cases()))
                    @php($granted = count($role->grantedPermissions()))
                    <tr>
                        <td>
                            <div class="gb-petition-name">
                                <span class="gb-icon-tile"><i data-lucide="{{ $role->isAdministrator() ? 'shield-check' : 'shield' }}"></i></span>
                                <div>
                                    <p class="gb-petition-title">
                                        {{ $role->name }}
                                        @if ($role->isAdministrator()) <span class="gb-badge gb-badge-neutral ms-1">Fixo</span> @endif
                                    </p>
                                    <p class="gb-petition-meta">{{ $role->description ?: 'Sem descrição' }}</p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $granted === $total ? 'Todas' : "{$granted} de {$total}" }}</td>
                        <td>{{ $role->users_count }}</td>
                        <td>
                            <div class="d-flex justify-content-md-end align-items-center gap-1">
                                @if ($role->isAdministrator())
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-white btn-sm"><i data-lucide="eye"></i> Ver</a>
                                @else
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-white btn-sm"><i data-lucide="pencil"></i> Editar</a>
                                @endif
                                <a href="{{ route('admin.roles.create', ['from' => $role->id]) }}" class="gb-icon-action" aria-label="Duplicar {{ $role->name }}" title="Duplicar"><i data-lucide="copy"></i></a>
                                @unless ($role->isAdministrator())
                                    @if ($role->users_count === 0)
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="js-delete-role" data-name="{{ $role->name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="gb-icon-action gb-icon-action-danger" aria-label="Excluir {{ $role->name }}" title="Excluir"><i data-lucide="trash-2"></i></button>
                                        </form>
                                    @else
                                        <button type="button" class="gb-icon-action" disabled aria-label="{{ $role->name }} está em uso e não pode ser excluído" title="Em uso: troque o perfil dos usuários para excluir"><i data-lucide="trash-2"></i></button>
                                    @endif
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="gb-list-summary gb-list-summary-footer">
            <span>O perfil Administrador tem sempre todas as permissões e não pode ser alterado.</span>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.querySelectorAll('.js-delete-role').forEach(form => form.addEventListener('submit', event => {
            event.preventDefault();
            Swal.fire({
                title: 'Excluir perfil?',
                text: `O perfil “${form.dataset.name}” será excluído.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'var(--df-danger)',
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
            }).then(result => { if (result.isConfirmed) { form.submit(); } });
        }));
    </script>
@endsection
