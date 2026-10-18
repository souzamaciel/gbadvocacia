@extends('layouts.app')

@section('title', 'Alterar senha')

@section('page-header')
    <x-page-header title="Alterar senha"
                   description="Informe sua senha atual e defina uma nova senha de acesso."
                   :breadcrumbs="['Minha conta', 'Alterar senha']" />
@endsection

@section('content')
    <div class="gb-narrow">
        @if (session('status'))
            <div class="alert alert-success" role="status">
                <i data-lucide="circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="card" novalidate>
            @csrf
            @method('PUT')

            <div class="card-header d-flex align-items-center gap-3">
                <span class="gb-icon-tile gb-icon-tile-sm"><i data-lucide="key-round"></i></span>
                <div>
                    <h2 class="card-title">Nova senha</h2>
                    <p class="card-description">Use pelo menos 8 caracteres.</p>
                </div>
            </div>

            <div class="card-body d-flex flex-column gap-3 p-4">
                @foreach ([
                    ['current_password', 'Senha atual', 'current-password', 'Informe sua senha atual'],
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

            <div class="card-footer d-flex flex-wrap justify-content-end gap-2 bg-white">
                <a href="{{ route('home') }}" class="btn btn-white">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i data-lucide="check"></i> Salvar nova senha</button>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
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
