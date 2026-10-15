@extends('layouts.app')

@section('title', 'Gerar petição')

@section('fonts')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
@endsection

@section('styles')
<style>
    .section-card { background:#fff; border:1px solid #e2e8f0; border-radius:.5rem; margin-bottom:1rem; }
    .section-header { padding:.75rem 1.25rem; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:.75rem; cursor:pointer; user-select:none; }
    .section-header.collapsed { border-bottom:none; }
    .section-badge { width:28px;height:28px;min-width:28px; border-radius:4px; background-color:var(--gb-navy); color:white; font-size:11px;font-weight:700; display:flex;align-items:center;justify-content:center; }
    .section-title { font-size:12px;font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#1e293b; margin:0; flex-grow:1; }
    .section-body { padding:1.25rem; display:flex; flex-direction:column; gap:.5rem; }
    .section-toggle { font-size:11px; color:#94a3b8; transition:transform .2s; }
    .section-header.collapsed .section-toggle { transform:rotate(-90deg); }
    .check-item { display:flex; align-items:center; gap:6px; cursor:pointer; margin:0; font-size:13px; font-weight:500; color:#314158; }
    .check-item input { width:15px; height:15px; accent-color:var(--gb-navy); cursor:pointer; margin:0; flex-shrink:0; }
    #pedidos-search-content { display: none; }
</style>
@endsection

@section('navbar-class', 'bg-dark bg-opacity-10')
@section('body-font', "'Inter', sans-serif")

@section('page-header')
    {{-- Page Header --}}
    <div class="container-fluid px-4 px-sm-5 py-4 d-flex align-items-center gap-2">
        {{-- Ícone back step --}}
        <div class="flex-shrink-0" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
            <a href="{{ route('home') }}">
                <i class="fa-regular fa-circle-left text-white fs-5"></i>
            </a>
        </div>

        {{-- Título + Subtítulo --}}
        <div class="flex-grow-1 d-flex flex-column gap-2">
            <h1 style="color: var(--gb-gold);font-size:24px;font-family:'Roboto',sans-serif;font-weight:500;line-height:26.15px;margin:0;">
                Gerador de Petição Inicial
            </h1>
            <p style="color:white;font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:13px;margin:0;">
                Selecione os pedidos que irão compor a petição inicial e gere o documento automaticamente.
            </p>
        </div>

        {{-- Breadcrumbs --}}
        <nav class="d-none d-sm-flex align-items-center gap-1 flex-shrink-0 ps-4"
             style="font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:20px;color:white;">
            <a href="{{ route('home') }}" class="text-decoration-none" style="color:white;">Home</a>
            <span class="px-1" style="color:white;">/</span>
            <span style="color:white;">Gerar petição</span>
        </nav>
    </div>
@endsection

@section('content')
    <form id="petition-form" method="POST" action="{{ route('petition.store') }}">
    @csrf
    {{-- Content --}}
    <div class="container-fluid px-4 px-sm-5 pb-5 d-flex flex-column flex-lg-row gap-4 align-items-start">

        {{-- LEFT: Accordion sections --}}
        <div class="flex-grow-1 w-100">

            @foreach ($groups as $group)
            <div class="section-card">
                <div class="section-header" data-bs-toggle="collapse" data-bs-target="#sec-{{ $group->id }}" aria-expanded="true">
                    <span class="section-badge">{{ $loop->iteration }}</span>
                    <h6 class="section-title">{{ $group->name }}</h6>
                    <i class="fa-solid fa-chevron-down section-toggle"></i>
                </div>
                <div id="sec-{{ $group->id }}" class="collapse show">
                    <div class="section-body">
                        @foreach ($group->topics as $topic)
                        <label class="check-item">
                            @if ($group->id === \App\Models\Group::PRELIMINARES_ID)
                            <input type="checkbox" class="preliminar-checkbox" name="preliminares[]" value="{{ $topic->id }}">
                            @else
                            <input type="checkbox" class="pedido-checkbox"
                                   data-topico="{{ json_encode(['id' => $topic->id, 'title' => $topic->title]) }}"
                                   onchange="onPedidoCheck(this)">
                            @endif
                            {{ $topic->title }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach

        </div>

        {{-- RIGHT: Pedidos --}}
        <div class="d-flex flex-column gap-3 flex-shrink-0 w-100" style="max-width: 453px;">
            <x-orders-panel :pedidos="$pedidos" buttonText="Gerar Petição Inicial" />
        </div>

    </div>
    </form>
@endsection

@section('scripts')
    <script>
        function onPedidoCheck(checkbox) {
            var topico = JSON.parse(checkbox.dataset.topico);
            if (checkbox.checked) {
                addPedido(topico);
            } else {
                var index = selectedPedidos.findIndex(function (p) { return p.id === topico.id; });
                if (index !== -1) { removePedido(index); }
            }
        }

        document.querySelectorAll('.collapse').forEach(function (collapseEl) {
            var header = document.querySelector('[data-bs-target="#' + collapseEl.id + '"]');
            if (!header) { return; }

            collapseEl.addEventListener('hide.bs.collapse', function () {
                header.classList.add('collapsed');
            });
            collapseEl.addEventListener('show.bs.collapse', function () {
                header.classList.remove('collapsed');
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('petition-form');
            if (!form) { return; }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var btn = document.getElementById('btn-submit-appointment');
                var originalText = btn.innerHTML;

                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Carregando...';

                var formData = new FormData(form);

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': formData.get('_token'),
                    },
                    body: formData,
                })
                .then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (data) {
                            throw new Error(data.message || 'Erro ao gerar petição.');
                        });
                    }

                    var disposition = response.headers.get('Content-Disposition') || '';
                    var filenameMatch = disposition.match(/filename="?([^";\s]+)"?/);
                    var filename = filenameMatch ? filenameMatch[1] : 'peticao-merito.docx';

                    return response.blob().then(function (blob) {
                        var url = URL.createObjectURL(blob);
                        var link = document.createElement('a');
                        link.href = url;
                        link.download = filename;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);

                        btn.disabled = false;
                        btn.innerHTML = originalText;

                        Swal.fire({
                            title: 'Petição gerada com sucesso!',
                            text: 'Deseja gerar outra petição?',
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonColor: 'var(--gb-navy)',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Sim, gerar outra',
                            cancelButtonText: 'Não',
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                window.location.href = '{{ route('petition.create') }}';
                            }
                        });
                    });
                })
                .catch(function (err) {
                    alert(err.message || 'Erro de conexão. Tente novamente.');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
            });
        });
    </script>
@endsection
