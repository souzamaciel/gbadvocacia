<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — GB Advocacia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @yield('fonts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        :root {
            --gb-gold:  #C49A6C;
            --gb-navy:  #232A54;
            --gb-text:  #314158;
        }

        body {
            background: linear-gradient(180deg, #0F172A 0%, #1E293B 50%, #3A4B6B 100%);
            min-height: 100vh;
            font-family: @yield('body-font', "'Inter', sans-serif");
        }

        /* Accordion customization */
        .accordion-button,
        .accordion-button:not(.collapsed) {
            background-color: #F8FAFC;
            color: #1E293B;
            font-weight: 700;
            box-shadow: none;
        }
        .accordion-button::after {
            filter: none;
        }
    </style>
    @yield('styles')
</head>
<body>

    <nav class="navbar px-3 px-sm-4 @yield('navbar-class')">
        <a class="navbar-brand" href="{{ route('home') }}">
            <img src="{{ asset('images/navbar_logo.png') }}" alt="GB Advocacia" height="51">
        </a>
        @auth
            <div class="d-flex align-items-center gap-2 ms-auto">
                {{-- Configurações --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-link text-decoration-none fw-normal p-2 d-inline-flex align-items-center gap-2"
                            style="color: var(--gb-gold); font-family: 'Montserrat', sans-serif; font-size: 14px;"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <i class="fa-solid fa-gear"></i>
                        <span class="d-none d-sm-inline">Configurações</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0"
                        style="font-family: 'Montserrat', sans-serif; min-width: 200px;">
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2"
                               href="{{ route('settings.groups.index') }}"
                               style="color: var(--gb-navy); font-size: 14px;">
                                <i class="fa-solid fa-layer-group" style="color: var(--gb-gold);"></i>
                                Grupos
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2"
                               href="{{ route('settings.topics.index') }}"
                               style="color: var(--gb-navy); font-size: 14px;">
                                <i class="fa-solid fa-file-lines" style="color: var(--gb-gold);"></i>
                                Tópicos
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Usuário --}}
                <div class="dropdown">
                    <button type="button"
                            class="btn btn-link text-decoration-none fw-normal p-2 d-inline-flex align-items-center gap-2 dropdown-toggle"
                            style="color: var(--gb-gold); font-family: 'Montserrat', sans-serif;"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <i class="fa-regular fa-circle-user"></i>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0"
                        style="font-family: 'Montserrat', sans-serif; min-width: 200px;">
                        <li>
                            <a class="dropdown-item py-2 d-flex align-items-center gap-2"
                               href="{{ route('password.edit') }}"
                               style="color: var(--gb-navy); font-size: 14px;">
                                <i class="fa-solid fa-key" style="color: var(--gb-gold);"></i>
                                Editar senha
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="dropdown-item py-2 d-flex align-items-center gap-2"
                                        style="color: var(--gb-navy); font-size: 14px;">
                                    <i class="fa-solid fa-right-from-bracket" style="color: var(--gb-gold);"></i>
                                    Sair
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        @endauth
    </nav>

    @yield('page-header')

    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @yield('scripts')

</body>
</html>
