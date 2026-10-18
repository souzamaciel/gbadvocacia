@extends('layouts.app')

@section('title', 'Meu perfil')

@section('page-header')
    <x-page-header title="Meu perfil"
                   description="Seus dados de acesso e o que o seu perfil permite fazer."
                   :breadcrumbs="['Minha conta', 'Meu perfil']" />
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success alert-dismissible" role="status">
            <i data-lucide="circle-check"></i>
            <span>{{ session('status') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar aviso"></button>
        </div>
    @endif

    @if ($user->must_change_password)
        <div class="alert alert-warning" role="alert">
            <i data-lucide="key-round"></i>
            <span>Defina uma nova senha para continuar. Em “Senha atual”, use a senha provisória que o administrador informou.</span>
        </div>
    @endif

    <div class="row g-3 align-items-start">
        <div class="col-12 col-lg-7 d-flex flex-column gap-3">
            <form method="POST" action="{{ route('profile.update') }}" class="card @if ($user->must_change_password) order-2 @endif" id="profile-form" novalidate>
                @csrf
                @method('PUT')

                <div class="card-header d-flex align-items-center gap-3">
                    <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="user-round"></i></span>
                    <div>
                        <h2 class="card-title">Dados pessoais</h2>
                        <p class="card-description">O e-mail é usado para entrar no sistema.</p>
                    </div>
                </div>

                <div class="card-body d-flex flex-column gap-3 p-4">
                    <div>
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" id="name" name="name" maxlength="255" autocomplete="name"
                               value="{{ old('name', $user->name) }}" data-initial="{{ $user->name }}"
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
                            <input type="email" id="email" name="email" maxlength="255" autocomplete="email"
                                   value="{{ old('email', $user->email) }}" data-initial="{{ $user->email }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   @error('email') aria-describedby="email-error" @enderror>
                        </div>
                        @error('email')
                            <div id="email-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="card-footer d-flex flex-wrap align-items-center justify-content-end gap-2 bg-white">
                    <p class="gb-footer-hint me-auto m-0" id="profile-hint" aria-live="polite"></p>
                    <button type="submit" class="btn btn-primary" id="profile-submit" disabled><i data-lucide="check"></i> Salvar dados</button>
                </div>
            </form>

            <form method="POST" action="{{ route('password.update') }}" class="card @if ($user->must_change_password) order-1 @endif" id="senha" novalidate>
                @csrf
                @method('PUT')

                <div class="card-header d-flex align-items-center gap-3">
                    <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="key-round"></i></span>
                    <div>
                        <h2 class="card-title">Alterar senha</h2>
                        <p class="card-description">Use pelo menos 8 caracteres.</p>
                    </div>
                </div>

                <div class="card-body d-flex flex-column gap-3 p-4">
                    @foreach ([
                        ['current_password', $user->must_change_password ? 'Senha atual (provisória)' : 'Senha atual', 'current-password', 'Informe sua senha atual'],
                        ['password', 'Nova senha', 'new-password', 'Mínimo de 8 caracteres'],
                        ['password_confirmation', 'Confirmar nova senha', 'new-password', 'Repita a nova senha'],
                    ] as [$field, $label, $autocomplete, $placeholder])
                        <div>
                            <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                            <div class="gb-input-icon">
                                <i data-lucide="lock"></i>
                                <input type="password" id="{{ $field }}" name="{{ $field }}"
                                       autocomplete="{{ $autocomplete }}" placeholder="{{ $placeholder }}"
                                       class="form-control @error($field) is-invalid @enderror"
                                       @error($field) aria-describedby="{{ $field }}-error" @enderror>
                                <button type="button" class="gb-input-action js-toggle-password" data-target="{{ $field }}"
                                        aria-label="Mostrar senha" aria-pressed="false">
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                            @error($field)
                                <div id="{{ $field }}-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="card-footer d-flex flex-wrap align-items-center justify-content-end gap-2 bg-white">
                    <p class="gb-footer-hint me-auto m-0" id="password-hint" aria-live="polite"></p>
                    <button type="submit" class="btn btn-primary" id="password-submit" disabled><i data-lucide="check"></i> Salvar nova senha</button>
                </div>
            </form>
        </div>

        <div class="col-12 col-lg-5">
            <section class="card" aria-labelledby="access-title">
                <div class="card-header d-flex align-items-center gap-3">
                    <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="shield-check"></i></span>
                    <div class="flex-grow-1">
                        <h2 id="access-title" class="card-title">Seu acesso</h2>
                        <p class="card-description">
                            @if ($user->last_login_at)
                                Último acesso {{ $user->last_login_at->locale('pt_BR')->calendar() }}
                            @else
                                Definido pelo administrador
                            @endif
                        </p>
                    </div>
                    <span class="gb-badge gb-badge-neutral">{{ $user->role?->name ?? 'Sem perfil' }}</span>
                </div>
                <div class="card-body p-4">
                    @php($granted = $user->role?->grantedPermissions() ?? [])
                    @foreach (\App\Enums\Permission::byArea() as $area => $permissions)
                        @php($allowed = array_filter($permissions, fn ($permission) => in_array($permission, $granted, true)))
                        @if ($allowed)
                            <p class="gb-list-label px-0">{{ $area }}</p>
                            <ul class="gb-permission-summary">
                                @foreach ($allowed as $permission)
                                    <li><i data-lucide="check"></i> {{ $permission->label() }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                    @if (! $granted)
                        <p class="gb-petition-meta m-0">Seu perfil ainda não tem permissões. Fale com o administrador do sistema.</p>
                    @endif
                    @unless ($user->isAdministrator())
                        <p class="gb-field-hint mb-0 mt-3"><i data-lucide="info"></i> Para mudar suas permissões, fale com o administrador do sistema.</p>
                    @endunless
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const refreshProfile = () => {
                const changed = name.value.trim() !== name.dataset.initial || email.value.trim().toLowerCase() !== email.dataset.initial;
                let hint = '';
                if (!name.value.trim()) { hint = 'Informe seu nome.'; }
                else if (!email.value.trim() || !email.checkValidity()) { hint = 'Informe um e-mail válido.'; }
                const box = document.getElementById('profile-hint');
                document.getElementById('profile-submit').disabled = hint !== '' || !changed;
                box.classList.toggle('is-warning', hint !== '');
                box.innerHTML = hint
                    ? `<i data-lucide="circle-alert"></i><span>${hint}</span>`
                    : (changed ? '<i data-lucide="circle-check"></i><span>Pronto para salvar.</span>' : '');
                lucide.createIcons();
            };
            [name, email].forEach(field => field.addEventListener('input', refreshProfile));
            refreshProfile();

            const current = document.getElementById('current_password');
            const password = document.getElementById('password');
            const confirmation = document.getElementById('password_confirmation');
            const refreshPassword = () => {
                let hint = '';
                if (!current.value) { hint = 'Informe a senha atual.'; }
                else if (password.value.length < 8) { hint = 'A nova senha precisa ter pelo menos 8 caracteres.'; }
                else if (password.value === current.value) { hint = 'A nova senha precisa ser diferente da atual.'; }
                else if (confirmation.value !== password.value) { hint = 'A confirmação precisa ser igual à nova senha.'; }
                const box = document.getElementById('password-hint');
                document.getElementById('password-submit').disabled = hint !== '';
                box.classList.toggle('is-warning', hint !== '');
                box.innerHTML = hint
                    ? `<i data-lucide="circle-alert"></i><span>${hint}</span>`
                    : '<i data-lucide="circle-check"></i><span>Pronto para salvar a nova senha.</span>';
                lucide.createIcons();
            };
            [current, password, confirmation].forEach(field => field.addEventListener('input', refreshPassword));
            refreshPassword();

            @if ($user->must_change_password || $errors->hasAny(['current_password', 'password']))
                current.focus();
            @endif
        })();

        document.querySelectorAll('.js-toggle-password').forEach(button => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                const isHidden = input.type === 'password';

                input.type = isHidden ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(isHidden));
                button.setAttribute('aria-label', isHidden ? 'Ocultar senha' : 'Mostrar senha');
                button.innerHTML = `<i data-lucide="${isHidden ? 'eye-off' : 'eye'}"></i>`;
                lucide.createIcons();
            });
        });
    </script>
@endsection
