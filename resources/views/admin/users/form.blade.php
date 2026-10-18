@extends('layouts.app')

@php
    $isNew = ! $user->exists;
    $isMe = $user->is(auth()->user());
    $rolesData = $roles->map(fn ($role) => [
        'id' => $role->id,
        'description' => $role->description,
        'permissions' => array_map(fn ($permission) => $permission->label(), $role->grantedPermissions()),
    ])->values();
@endphp

@section('title', $isNew ? 'Novo usuário' : 'Editar usuário')

@section('page-header')
    <x-page-header :title="$isNew ? 'Novo usuário' : $user->name"
                   :description="$isNew ? 'Cadastre o acesso e escolha o perfil de permissão.' : 'Altere os dados, o perfil ou defina uma senha provisória.'"
                   :breadcrumbs="['Usuários' => route('admin.users.index'), $isNew ? 'Novo usuário' : 'Editar usuário']" />
@endsection

@section('content')
    <div class="row g-3 align-items-start">
        <div class="col-12 col-lg-7 d-flex flex-column gap-3">
            <form method="POST" action="{{ $isNew ? route('admin.users.store') : route('admin.users.update', $user) }}" class="card" id="user-form" novalidate>
                @csrf
                @unless ($isNew) @method('PUT') @endunless

                <div class="card-header d-flex align-items-center gap-3">
                    <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="{{ $isNew ? 'user-plus' : 'user-round' }}"></i></span>
                    <div>
                        <h2 class="card-title">Dados de acesso</h2>
                        <p class="card-description">O e-mail é usado para entrar no sistema.</p>
                    </div>
                </div>

                <div class="card-body d-flex flex-column gap-3 p-4">
                    <div>
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" id="name" name="name" maxlength="255" autocomplete="off"
                               value="{{ old('name', $user->name) }}" placeholder="Nome completo"
                               class="form-control @error('name') is-invalid @enderror"
                               @error('name') aria-describedby="name-error" @enderror>
                        @error('name')
                            <div id="name-error" class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="form-label">E-mail</label>
                        <div class="gb-input-icon">
                            <i data-lucide="mail"></i>
                            <input type="email" id="email" name="email" maxlength="255" autocomplete="off"
                                   value="{{ old('email', $user->email) }}" placeholder="nome@exemplo.com.br"
                                   class="form-control @error('email') is-invalid @enderror"
                                   @error('email') aria-describedby="email-error" @enderror>
                        </div>
                        @error('email')
                            <div id="email-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label for="role_id" class="form-label">Perfil de permissão</label>
                        @if ($isMe)
                            <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                        @endif
                        <select id="role_id" @unless ($isMe) name="role_id" @endunless class="form-select @error('role_id') is-invalid @enderror"
                                @disabled($isMe) @error('role_id') aria-describedby="role_id-error" @enderror>
                            <option value="">Escolha o perfil</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('role_id', $user->role_id) === (string) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div id="role_id-error" class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                        @if ($isMe)
                            <p class="gb-field-hint"><i data-lucide="info"></i> Você não pode alterar o seu próprio perfil.</p>
                        @endif
                    </div>

                    @if ($isNew)
                        <div>
                            <label for="password" class="form-label">Senha provisória</label>
                            <div class="d-flex gap-2">
                                <div class="gb-input-icon flex-grow-1">
                                    <i data-lucide="lock"></i>
                                    <input type="text" id="password" name="password" maxlength="100" autocomplete="new-password"
                                           value="{{ old('password') }}" placeholder="Mínimo de 8 caracteres"
                                           class="form-control @error('password') is-invalid @enderror"
                                           @error('password') aria-describedby="password-error" @enderror>
                                </div>
                                <button type="button" class="btn btn-white js-generate-password" data-target="password"><i data-lucide="wand-sparkles"></i> Gerar</button>
                            </div>
                            @error('password')
                                <div id="password-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                            @enderror
                            <p class="gb-field-hint"><i data-lucide="info"></i> Anote e informe ao usuário. No primeiro acesso, o sistema pede para ele criar a própria senha.</p>
                        </div>
                    @endif
                </div>

                <div class="card-footer d-flex flex-wrap align-items-center justify-content-end gap-2 bg-white">
                    <p class="gb-footer-hint me-auto m-0" id="user-hint" aria-live="polite"></p>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-white">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="user-submit" disabled>
                        <i data-lucide="check"></i> {{ $isNew ? 'Cadastrar usuário' : 'Salvar alterações' }}
                    </button>
                </div>
            </form>

            @unless ($isNew || $isMe)
                <form method="POST" action="{{ route('admin.users.password', $user) }}" class="card" id="senha" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="card-header d-flex align-items-center gap-3">
                        <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="key-round"></i></span>
                        <div>
                            <h2 class="card-title">Redefinir senha</h2>
                            <p class="card-description">Para quem esqueceu a senha. A troca será pedida no próximo acesso.</p>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <label for="reset-password" class="form-label">Nova senha provisória</label>
                        <div class="d-flex gap-2">
                            <div class="gb-input-icon flex-grow-1">
                                <i data-lucide="lock"></i>
                                <input type="text" id="reset-password" name="password" maxlength="100" autocomplete="new-password"
                                       placeholder="Mínimo de 8 caracteres"
                                       class="form-control @error('password') is-invalid @enderror"
                                       @error('password') aria-describedby="reset-password-error" @enderror>
                            </div>
                            <button type="button" class="btn btn-white js-generate-password" data-target="reset-password"><i data-lucide="wand-sparkles"></i> Gerar</button>
                        </div>
                        @error('password')
                            <div id="reset-password-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="card-footer d-flex flex-wrap align-items-center justify-content-end gap-2 bg-white">
                        <p class="gb-footer-hint me-auto m-0" id="reset-hint" aria-live="polite"></p>
                        <button type="submit" class="btn btn-primary" id="reset-submit" disabled><i data-lucide="key-round"></i> Definir senha provisória</button>
                    </div>
                </form>
            @endunless
        </div>

        <div class="col-12 col-lg-5">
            <section class="card" aria-labelledby="role-preview-title">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="shield-check"></i></span>
                    <div>
                        <h2 id="role-preview-title" class="card-title">O que este perfil permite</h2>
                        <p class="card-description" id="role-description">Escolha um perfil para ver as permissões.</p>
                    </div>
                </div>
                <div class="card-body p-4">
                    <ul class="gb-permission-summary" id="role-permissions"></ul>
                    @can('roles.manage')
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-white btn-sm mt-3"><i data-lucide="shield-check"></i> Gerenciar perfis</a>
                    @endcan
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const roles = new Map({{ Js::from($rolesData) }}.map(role => [String(role.id), role]));
            const isNew = @json($isNew);
            const fields = {
                name: document.getElementById('name'),
                email: document.getElementById('email'),
                role: document.getElementById('role_id'),
                password: document.getElementById('password'),
            };
            const initial = JSON.stringify([fields.name.value, fields.email.value, fields.role.value]);
            const escapeHtml = text => text.replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);

            function renderRole() {
                const role = roles.get(fields.role.value);
                document.getElementById('role-description').textContent = role ? (role.description || 'Sem descrição.') : 'Escolha um perfil para ver as permissões.';
                document.getElementById('role-permissions').innerHTML = role
                    ? (role.permissions.length
                        ? role.permissions.map(label => `<li><i data-lucide="check"></i> ${escapeHtml(label)}</li>`).join('')
                        : '<li class="is-empty">Este perfil não tem permissões.</li>')
                    : '';
                lucide.createIcons();
            }

            function refresh() {
                const missing = [
                    !fields.name.value.trim() && 'nome',
                    (!fields.email.value.trim() || !fields.email.checkValidity()) && 'e-mail válido',
                    !fields.role.value && 'perfil',
                    isNew && fields.password.value.length < 8 && 'senha provisória com 8 caracteres ou mais',
                ].filter(Boolean);
                const changed = isNew || JSON.stringify([fields.name.value, fields.email.value, fields.role.value]) !== initial;
                const box = document.getElementById('user-hint');
                document.getElementById('user-submit').disabled = missing.length > 0 || !changed;
                box.classList.toggle('is-warning', missing.length > 0);
                box.innerHTML = missing.length
                    ? `<i data-lucide="circle-alert"></i><span>Para salvar, preencha ${missing.length > 1 ? missing.slice(0, -1).join(', ') + ' e ' + missing[missing.length - 1] : missing[0]}.</span>`
                    : (changed ? '<i data-lucide="circle-check"></i><span>Pronto para salvar.</span>' : '');
                lucide.createIcons();
            }

            Object.values(fields).filter(Boolean).forEach(field => field.addEventListener('input', refresh));
            fields.role.addEventListener('change', () => { renderRole(); refresh(); });
            renderRole();
            refresh();

            const reset = document.getElementById('reset-password');
            function refreshReset() {
                const ok = reset.value.length >= 8;
                const box = document.getElementById('reset-hint');
                document.getElementById('reset-submit').disabled = !ok;
                box.classList.toggle('is-warning', reset.value !== '' && !ok);
                box.innerHTML = reset.value === ''
                    ? ''
                    : (ok ? '<i data-lucide="circle-check"></i><span>Anote a senha antes de confirmar.</span>' : '<i data-lucide="circle-alert"></i><span>A senha provisória precisa ter pelo menos 8 caracteres.</span>');
                lucide.createIcons();
            }
            reset?.addEventListener('input', refreshReset);

            document.querySelectorAll('.js-generate-password').forEach(button => button.addEventListener('click', () => {
                const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
                const values = crypto.getRandomValues(new Uint32Array(10));
                const input = document.getElementById(button.dataset.target);
                input.value = Array.from(values, value => alphabet[value % alphabet.length]).join('');
                input.dispatchEvent(new Event('input'));
                input.focus();
                input.select();
            }));

            @if ($errors->has('password') && ! $isNew)
                document.getElementById('senha')?.scrollIntoView({ block: 'center' });
            @endif
            if (location.hash === '#senha') { reset?.focus(); }
        })();
    </script>
@endsection
