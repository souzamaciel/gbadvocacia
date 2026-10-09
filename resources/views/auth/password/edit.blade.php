@extends('layouts.app')

@section('title', 'Editar senha')

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
                Editar senha
            </h1>
            <p style="color:white;font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:13px;margin:0;">
                Informe sua senha atual e defina uma nova senha de acesso.
            </p>
        </div>

        <nav class="d-none d-sm-flex align-items-center gap-1 flex-shrink-0 ps-4"
             style="font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:20px;color:white;">
            <a href="{{ route('home') }}" class="text-decoration-none" style="color:white;">Home</a>
            <span class="px-1" style="color:white;">/</span>
            <span style="color:white;">Editar senha</span>
        </nav>
    </div>
@endsection

@section('styles')
<style>
    .section-card { background:#fff; border:1px solid #e2e8f0; border-radius:.5rem; margin-bottom:1rem; }
    .section-header { padding:.75rem 1.25rem; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:.75rem; }
    .section-badge { width:28px;height:28px;min-width:28px; border-radius:4px; background-color:var(--gb-navy); color:white; font-size:11px;font-weight:700; display:flex;align-items:center;justify-content:center; }
    .section-title { font-size:12px;font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#1e293b; margin:0; }
    .section-body { padding:1.5rem; display:flex; flex-direction:column; gap:1rem; }
    .form-label { font-size:12px;font-weight:500; color:#64748b; margin-bottom:4px; }
    .form-control { font-size:13px;color:#314158; }
    .form-control:focus { border-color:var(--gb-navy); box-shadow:0 0 0 .2rem rgba(35,42,84,.15); }
    .form-control::placeholder { color:rgba(10,10,10,.35); }
    .btn-submit { background-color:var(--gb-navy); color:#fff; font-size:13px; font-weight:500; padding:.6rem 1.5rem; border:none; border-radius:6px; font-family:'Roboto',sans-serif; }
    .btn-submit:hover { opacity:.9; color:#fff; }
    .password-wrapper { position:relative; }
    .password-wrapper .toggle-password { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; color:#94a3b8; padding:4px; cursor:pointer; font-size:14px; }
    .password-wrapper .toggle-password:hover { color:var(--gb-navy); }
    .password-wrapper .form-control { padding-right:36px; }
</style>
@endsection

@section('content')
    <div class="container-fluid px-4 px-sm-5 pb-5">
        <div class="mx-auto" style="max-width: 560px;">

            @if (session('status'))
                <div class="alert alert-success d-flex align-items-center gap-2 border-0 shadow-sm"
                     style="background:#ecfdf5; color:#065f46; font-size:13px;">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                @method('PUT')

                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge"><i class="fa-solid fa-key"></i></span>
                        <h6 class="section-title">Alterar senha</h6>
                    </div>
                    <div class="section-body">

                        {{-- Senha atual --}}
                        <div>
                            <label for="current_password" class="form-label">Senha atual</label>
                            <div class="password-wrapper">
                                <input type="password"
                                       id="current_password"
                                       name="current_password"
                                       autocomplete="current-password"
                                       class="form-control form-control-sm @error('current_password') is-invalid @enderror"
                                       placeholder="Informe sua senha atual">
                                <button type="button" class="toggle-password" data-target="current_password" aria-label="Mostrar/ocultar senha">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="text-danger mt-1" style="font-size:11px;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Nova senha --}}
                        <div>
                            <label for="password" class="form-label">Nova senha</label>
                            <div class="password-wrapper">
                                <input type="password"
                                       id="password"
                                       name="password"
                                       autocomplete="new-password"
                                       class="form-control form-control-sm @error('password') is-invalid @enderror"
                                       placeholder="Mínimo de 8 caracteres">
                                <button type="button" class="toggle-password" data-target="password" aria-label="Mostrar/ocultar senha">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="text-danger mt-1" style="font-size:11px;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Confirmar nova senha --}}
                        <div>
                            <label for="password_confirmation" class="form-label">Confirmar nova senha</label>
                            <div class="password-wrapper">
                                <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       autocomplete="new-password"
                                       class="form-control form-control-sm"
                                       placeholder="Repita a nova senha">
                                <button type="button" class="toggle-password" data-target="password_confirmation" aria-label="Mostrar/ocultar senha">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2">
                            <a href="{{ route('home') }}" class="btn btn-sm"
                               style="color:#64748b;font-size:13px;font-family:'Roboto',sans-serif;">
                                Cancelar
                            </a>
                            <button type="submit" class="btn-submit">
                                Salvar nova senha
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: 'Sucesso!',
                    text: '{{ session('status') }}',
                    icon: 'success',
                    confirmButtonColor: '#232A54',
                    confirmButtonText: 'OK',
                });
            });
        </script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggles = document.querySelectorAll('.toggle-password');
            toggles.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var targetId = btn.getAttribute('data-target');
                    var input = document.getElementById(targetId);
                    if (!input) { return; }
                    var icon = btn.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
        });
    </script>
@endsection
