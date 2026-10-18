@extends('layouts.app')

@php
    $isNew = ! $role->exists;
    $locked = $role->isAdministrator();
    $checked = old('permissions', array_map(fn ($permission) => $permission->value, $role->grantedPermissions()));
    $dependencies = collect(\App\Enums\Permission::cases())
        ->filter(fn ($permission) => $permission->requires() !== null)
        ->mapWithKeys(fn ($permission) => [$permission->value => $permission->requires()->value]);
@endphp

@section('title', $isNew ? 'Novo perfil' : $role->name)

@section('page-header')
    <x-page-header :title="$isNew ? 'Novo perfil' : $role->name"
                   :description="$locked ? 'Perfil fixo: tem sempre todas as permissões.' : 'Marque o que quem tem este perfil pode fazer.'"
                   :breadcrumbs="['Perfis de permissão' => route('admin.roles.index'), $isNew ? 'Novo perfil' : ($locked ? 'Ver perfil' : 'Editar perfil')]" />
@endsection

@section('content')
    <form method="POST" action="{{ $isNew ? route('admin.roles.store') : route('admin.roles.update', $role) }}" id="role-form" novalidate>
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        @if ($locked)
            <div class="alert alert-info" role="status">
                <i data-lucide="lock"></i>
                <span>O perfil Administrador não pode ser alterado, para que sempre exista alguém com acesso à administração.</span>
            </div>
        @elseif (! $isNew && $role->users_count > 0)
            <div class="alert alert-warning" role="status">
                <i data-lucide="users"></i>
                <span>{{ $role->users_count }} {{ $role->users_count === 1 ? 'usuário usa' : 'usuários usam' }} este perfil. As mudanças valem na hora para {{ $role->users_count === 1 ? 'ele' : 'todos' }}.</span>
            </div>
        @endif

        <div class="row g-3 align-items-start">
            <div class="col-12 col-lg-4">
                <section class="card" aria-labelledby="role-data-title">
                    <div class="card-header d-flex align-items-center gap-3">
                        <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="shield"></i></span>
                        <div>
                            <h2 id="role-data-title" class="card-title">Perfil</h2>
                            <p class="card-description">Nome que aparece no cadastro de usuários.</p>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column gap-3 p-4">
                        <div>
                            <label for="name" class="form-label">Nome</label>
                            <input type="text" id="name" name="name" maxlength="60" autocomplete="off" placeholder="Ex.: Estagiário"
                                   value="{{ old('name', $role->name) }}" @disabled($locked)
                                   class="form-control @error('name') is-invalid @enderror"
                                   @error('name') aria-describedby="name-error" @enderror>
                            @error('name')
                                <div id="name-error" class="invalid-feedback" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="description" class="form-label">Descrição · opcional</label>
                            <textarea id="description" name="description" rows="3" maxlength="255" placeholder="Para quem é este perfil"
                                      @disabled($locked)
                                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $role->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-lg-8">
                <section class="card" aria-labelledby="permissions-title">
                    <div class="card-header d-flex align-items-center gap-3">
                        <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="list-checks"></i></span>
                        <div class="flex-grow-1">
                            <h2 id="permissions-title" class="card-title">Permissões</h2>
                            <p class="card-description">O que não estiver marcado some das telas para quem tem este perfil.</p>
                        </div>
                        <span class="gb-badge gb-badge-neutral" id="permissions-count"></span>
                    </div>
                    <div class="card-body p-0">
                        @foreach (\App\Enums\Permission::byArea() as $area => $permissions)
                            <div class="gb-permission-group" role="group" aria-labelledby="area-{{ $loop->index }}">
                                <p class="gb-list-label" id="area-{{ $loop->index }}">{{ $area }}</p>
                                @foreach ($permissions as $permission)
                                    <div class="form-check gb-permission-item @if ($permission->requires()) is-child @endif">
                                        <input class="form-check-input js-permission" type="checkbox" name="permissions[]"
                                               id="permission-{{ $loop->parent->index }}-{{ $loop->index }}" value="{{ $permission->value }}"
                                               @checked(in_array($permission->value, $checked, true)) @disabled($locked)>
                                        <label class="form-check-label" for="permission-{{ $loop->parent->index }}-{{ $loop->index }}">
                                            <span class="gb-petition-title">{{ $permission->label() }}</span>
                                            <span class="gb-petition-meta d-block">
                                                {{ $permission->description() }}
                                                @if ($permission->requires())
                                                    Inclui “{{ $permission->requires()->label() }}”.
                                                @endif
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>

        <div class="card gb-review-summary mt-3">
            <p class="gb-footer-hint m-0 me-auto" id="role-hint" aria-live="polite"></p>
            <div class="gb-page-actions">
                <a href="{{ route('admin.roles.index') }}" class="btn btn-white">{{ $locked ? 'Voltar' : 'Cancelar' }}</a>
                @unless ($locked)
                    <button type="submit" class="btn btn-primary" id="role-submit" disabled>
                        <i data-lucide="check"></i> {{ $isNew ? 'Criar perfil' : 'Salvar perfil' }}
                    </button>
                @endunless
            </div>
        </div>
    </form>
@endsection

@section('scripts')
    <script>
        (function () {
            const requires = {{ Js::from($dependencies) }};
            const boxes = Array.from(document.querySelectorAll('.js-permission'));
            const byValue = new Map(boxes.map(box => [box.value, box]));
            const name = document.getElementById('name');
            const description = document.getElementById('description');
            const submit = document.getElementById('role-submit');
            const isNew = @json($isNew);
            const snapshot = () => JSON.stringify([name.value.trim(), description.value.trim(), boxes.filter(box => box.checked).map(box => box.value)]);
            const initial = snapshot();

            function refresh() {
                const total = boxes.filter(box => box.checked).length;
                document.getElementById('permissions-count').textContent = `${total} de ${boxes.length}`;
                if (!submit) { return; }
                const changed = isNew || snapshot() !== initial;
                const hint = !name.value.trim() ? 'Para salvar, informe o nome do perfil.' : '';
                const box = document.getElementById('role-hint');
                submit.disabled = hint !== '' || !changed;
                box.classList.toggle('is-warning', hint !== '');
                box.innerHTML = hint
                    ? `<i data-lucide="circle-alert"></i><span>${hint}</span>`
                    : (changed ? `<i data-lucide="circle-check"></i><span>${total === 0 ? 'Pronto para salvar. Sem permissões, o usuário só acessa “Meu perfil”.' : 'Pronto para salvar.'}</span>` : '');
                lucide.createIcons();
            }

            boxes.forEach(box => box.addEventListener('change', () => {
                if (box.checked) {
                    let parent = requires[box.value];
                    while (parent) { byValue.get(parent).checked = true; parent = requires[parent]; }
                } else {
                    const uncheckChildren = value => Object.entries(requires)
                        .filter(([, parent]) => parent === value)
                        .forEach(([child]) => { byValue.get(child).checked = false; uncheckChildren(child); });
                    uncheckChildren(box.value);
                }
                refresh();
            }));
            [name, description].forEach(field => field.addEventListener('input', refresh));
            refresh();
        })();
    </script>
@endsection
