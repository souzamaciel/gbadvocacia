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

        /* Aviso em português ao sair de uma tela com alterações não salvas.
           A página define window.gbIsDirty() quando tem algo a salvar. */
        (function () {
            window.gbLeaving = false;
            const isDirty = () => !window.gbLeaving && typeof window.gbIsDirty === 'function' && window.gbIsDirty();
            const confirmLeave = () => Swal.fire({
                title: 'Sair sem salvar?',
                text: 'As alterações feitas nesta tela ainda não foram salvas.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sair sem salvar',
                cancelButtonText: 'Continuar aqui',
                confirmButtonColor: 'var(--df-danger)',
            }).then(result => {
                if (result.isConfirmed) { window.gbLeaving = true; }
                return result.isConfirmed;
            });

            document.addEventListener('click', event => {
                const link = event.target.closest('a[href]');
                if (!link || event.defaultPrevented || link.target === '_blank' || event.ctrlKey || event.metaKey || event.shiftKey
                    || link.getAttribute('href').startsWith('#') || !isDirty()) { return; }
                event.preventDefault();
                confirmLeave().then(leave => { if (leave) { location.href = link.href; } });
            });

            document.addEventListener('submit', event => {
                const form = event.target;
                if (form.matches('[action$="/logout"]') && isDirty()) {
                    event.preventDefault();
                    confirmLeave().then(leave => { if (leave) { form.submit(); } });
                    return;
                }
                setTimeout(() => { if (!event.defaultPrevented) { window.gbLeaving = true; } }, 0);
            });

            window.addEventListener('beforeunload', event => {
                if (isDirty()) { event.preventDefault(); event.returnValue = ''; }
            });
        })();
    </script>
    @yield('scripts')
</body>
</html>
