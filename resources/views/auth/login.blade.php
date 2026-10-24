<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — GB Advocacia</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('vendor/dashforge/dashforge.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gb-tokens.css').'?v='.filemtime(public_path('css/gb-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/gb-app.css').'?v='.filemtime(public_path('css/gb-app.css')) }}">
</head>
<body class="gb-login">
    <main class="gb-login-main">
        <form method="POST" action="{{ route('login') }}" class="gb-login-card" novalidate>
            @csrf

            <div>
                <h1 class="gb-login-title">Entrar</h1>
                <p class="gb-login-subtitle">Bem-vindo de volta! Entre para continuar.</p>
            </div>

            <div>
                <label for="email" class="form-label">E-mail</label>
                <div class="gb-input-icon">
                    <i data-lucide="mail"></i>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           placeholder="seu@email.com" autocomplete="email" inputmode="email" required autofocus
                           class="form-control @error('email') is-invalid @enderror"
                           @error('email') aria-describedby="email-error" @enderror>
                </div>
                @error('email')
                    <div id="email-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="password" class="form-label">Senha</label>
                <div class="gb-input-icon">
                    <i data-lucide="lock"></i>
                    <input id="password" type="password" name="password" placeholder="••••••••"
                           autocomplete="current-password" required
                           class="form-control @error('password') is-invalid @enderror"
                           @error('password') aria-describedby="password-error" @enderror>
                    <button type="button" class="gb-input-action" id="toggle-password"
                            aria-label="Mostrar senha" aria-pressed="false">
                        <i data-lucide="eye"></i>
                    </button>
                </div>
                @error('password')
                    <div id="password-error" class="invalid-feedback d-block" role="alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label" for="remember">Lembrar-me</label>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="login-submit" disabled>Entrar</button>

            <p class="gb-login-note">
                <i data-lucide="info"></i>
                Acesso restrito à equipe do escritório. Por segurança, os acessos e as ações no sistema são registrados (data, IP e localização aproximada).
            </p>
        </form>
    </main>

    @include('layouts.partials.footer')

    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script>
        lucide.createIcons();

        (function () {
            const fields = ['email', 'password'].map(id => document.getElementById(id));
            const filled = field => field.value.trim() !== '' || field.matches(':-webkit-autofill');
            const refresh = () => { document.getElementById('login-submit').disabled = !fields.every(filled); };
            fields.forEach(field => {
                field.addEventListener('input', refresh);
                field.addEventListener('change', refresh);
            });
            refresh();
            // O preenchimento automático do navegador nem sempre dispara eventos.
            let checks = 0;
            const timer = setInterval(() => { refresh(); if (++checks > 12) { clearInterval(timer); } }, 250);
        })();

        document.getElementById('toggle-password').addEventListener('click', function () {
            const input = document.getElementById('password');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';
            this.setAttribute('aria-pressed', String(isHidden));
            this.setAttribute('aria-label', isHidden ? 'Ocultar senha' : 'Mostrar senha');
            this.innerHTML = `<i data-lucide="${isHidden ? 'eye-off' : 'eye'}"></i>`;
            lucide.createIcons();
        });
    </script>
</body>
</html>
