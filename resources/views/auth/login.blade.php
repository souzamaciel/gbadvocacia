<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — GB Advocacia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body class="min-h-screen flex items-center justify-center py-8 px-4" style="background: linear-gradient(180deg, #0F172A 0%, #1E293B 50%, #3A4B6B 100%); font-family: 'Inter', sans-serif;">

    <div class="flex flex-col items-center gap-6 w-full max-w-[448px]">

        {{-- Card --}}
        <div class="w-full bg-white rounded-2xl shadow-[0px_25px_50px_-12px_rgba(0,0,0,0.25)] p-6 sm:p-8">

            {{-- Logo --}}
            <div class="flex justify-center pb-6">
                <img src="{{ asset('images/login_logo.png') }}" alt="GB Advocacia" class="w-[140px] sm:w-[190px] h-auto object-contain">
            </div>

            {{-- Formulário --}}
            <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
                @csrf

                {{-- E-mail --}}
                <div class="flex flex-col gap-2">
                    <label for="email" class="text-sm font-medium" style="color: #232A54;">
                        E-mail
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="1.67" y="3.33" width="16.67" height="13.33" rx="2" stroke="#A48860" stroke-width="1.67"/>
                                <path d="M1.67 5.83 10 11.67l8.33-5.84" stroke="#A48860" stroke-width="1.67" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="seu@email.com"
                            autocomplete="email"
                            class="w-full h-[50px] pl-11 pr-4 rounded-[10px] border border-[#D1D5DC] text-base text-gray-800 placeholder-black/50 outline-none focus:border-[#232A54] focus:ring-1 focus:ring-[#232A54] transition @error('email') border-red-500 @enderror"
                        >
                    </div>
                    @error('email')
                        <p class="text-red-500 text-xs">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Senha --}}
                <div class="flex flex-col gap-2">
                    <label for="password" class="text-sm font-medium" style="color: #232A54;">
                        Senha
                    </label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="2.5" y="9.17" width="15" height="9.17" rx="2" stroke="#A48860" stroke-width="1.67"/>
                                <path d="M5.83 9.17V6.67a4.17 4.17 0 0 1 8.33 0v2.5" stroke="#A48860" stroke-width="1.67" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            class="w-full h-[50px] pl-11 pr-12 rounded-[10px] border border-[#D1D5DC] text-base text-gray-800 placeholder-black/50 outline-none focus:border-[#232A54] focus:ring-1 focus:ring-[#232A54] transition"
                        >
                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 cursor-pointer"
                            aria-label="Mostrar/ocultar senha"
                        >
                            <svg id="eye-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.67 10S4.17 4.17 10 4.17 18.33 10 18.33 10 15.83 15.83 10 15.83 1.67 10 1.67 10Z" stroke="#A48860" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="10" cy="10" r="2.5" stroke="#A48860" stroke-width="1.67"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lembrar-me + Esqueceu a senha --}}
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="remember"
                            class="w-5 h-5 rounded-[3px] border-2 accent-[#232A54] cursor-pointer"
                            style="border-color: #232A54;"
                        >
                        <span class="text-sm font-medium" style="color: #232A54;">Lembrar-me</span>
                    </label>
                    <a href="#" class="text-sm" style="color: #A48860;">Esqueceu a senha?</a>
                </div>

                {{-- Botão Entrar --}}
                <button
                    type="submit"
                    class="w-full h-14 rounded-[10px] text-white text-base font-medium shadow-[0px_4px_6px_-4px_rgba(0,0,0,0.10),0px_10px_15px_-3px_rgba(0,0,0,0.10)] transition hover:opacity-90 active:opacity-80 cursor-pointer"
                    style="background-color: #232A54;"
                >
                    Entrar
                </button>
            </form>

            {{-- Rodapé do card --}}
            <p class="mt-5 text-center text-sm" style="color: #4A5565;">
                Não tem uma conta?
                <a href="#" style="color: #A48860;">Entre em contato</a>
            </p>
        </div>

        {{-- Texto abaixo do card --}}
        <p class="text-xs text-center" style="color: #A48860;">Acesso seguro e criptografado</p>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            const isHidden = input.type === 'password';

            input.type = isHidden ? 'text' : 'password';

            icon.innerHTML = isHidden
                ? `<path d="M3 3l14 14M8.5 8.7A3.5 3.5 0 0 0 10 13.5a3.5 3.5 0 0 0 3.5-3.5 3.5 3.5 0 0 0-.7-2.1M6.1 6.1C3.7 7.4 1.67 10 1.67 10S4.17 15.83 10 15.83c1.9 0 3.6-.6 5-1.6" stroke="#A48860" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M10.9 4.2C10.6 4.18 10.3 4.17 10 4.17 4.17 4.17 1.67 10 1.67 10s.6 1.2 1.7 2.4" stroke="#A48860" stroke-width="1.67" stroke-linecap="round"/>`
                : `<path d="M1.67 10S4.17 4.17 10 4.17 18.33 10 18.33 10 15.83 15.83 10 15.83 1.67 10 1.67 10Z" stroke="#A48860" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                   <circle cx="10" cy="10" r="2.5" stroke="#A48860" stroke-width="1.67"/>`;
        }
    </script>
</body>
</html>
