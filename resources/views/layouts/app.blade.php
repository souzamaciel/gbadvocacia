<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

            /* Botão Cancelar: sai do fluxo e volta para a lista.
               data-list-url: para onde voltar · data-delete-url: exclui o item já salvo (opcional)
               data-item: "a petição" / "o atendimento" */
            function leaveTo(url) { window.gbLeaving = true; location.href = url; }

            function deleteAndLeave(url) {
                window.gbLeaving = true;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name=csrf-token]').content}"><input type="hidden" name="_method" value="DELETE">`;
                document.body.appendChild(form);
                form.submit();
            }

            document.addEventListener('click', event => {
                const button = event.target.closest('.js-cancel-flow');
                if (!button) { return; }
                event.preventDefault();
                const item = button.dataset.item || 'o item';
                const saved = !!button.dataset.deleteUrl;
                const canDelete = saved && button.dataset.canDelete !== 'false';
                const dirty = typeof window.gbIsDirty === 'function' && window.gbIsDirty();

                if (!saved && !dirty) { leaveTo(button.dataset.listUrl); return; }

                Swal.fire({
                    title: saved ? 'Cancelar e sair?' : 'Cancelar?',
                    text: saved
                        ? (dirty ? 'As alterações ainda não salvas serão perdidas. ' : '') + `Você pode sair mantendo ${item} na lista, para continuar depois${canDelete ? `, ou excluir ${item}` : ''}.`
                        : 'O que foi preenchido não será salvo.',
                    icon: 'warning',
                    showCancelButton: true,
                    showDenyButton: canDelete,
                    confirmButtonText: saved ? 'Sair e manter na lista' : 'Sim, cancelar',
                    denyButtonText: `Excluir ${item}`,
                    cancelButtonText: 'Continuar aqui',
                    confirmButtonColor: 'var(--df-brand-01)',
                    denyButtonColor: 'var(--df-danger)',
                    reverseButtons: false,
                }).then(result => {
                    if (result.isConfirmed) { leaveTo(button.dataset.listUrl); }
                    if (result.isDenied) { deleteAndLeave(button.dataset.deleteUrl); }
                });
            });
        })();
    </script>
    @yield('scripts')
</body>
</html>
