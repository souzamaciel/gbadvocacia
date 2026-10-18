<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — GB Advocacia</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('vendor/dashforge/dashforge.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/gb-tokens.css').'?v='.filemtime(public_path('css/gb-tokens.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/gb-app.css').'?v='.filemtime(public_path('css/gb-app.css')) }}">
    @yield('styles')
</head>
<body>
    @auth
        @include('layouts.partials.header')
    @endauth

    <main class="gb-content">
        @yield('page-header')

        @yield('content')
    </main>

    @include('layouts.partials.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5"></script>
    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
    @yield('scripts')
</body>
</html>
