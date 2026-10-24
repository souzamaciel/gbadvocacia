@extends('layouts.app')

@section('title', 'Logs de atividade')

@section('page-header')
    <x-page-header title="Logs de atividade"
                   description="Quem fez o quê, quando e de onde. Os registros ficam guardados por {{ \App\Models\ActivityLog::RETENTION_MONTHS }} meses."
                   :breadcrumbs="['Administração', 'Logs de atividade']" />
@endsection

@section('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
    <style>
        .flatpickr-calendar { font-family: var(--df-font); }
        .flatpickr-day.selected, .flatpickr-day.selected:hover, .flatpickr-day.today.selected { background: var(--df-brand-01); border-color: var(--df-brand-01); }
        .flatpickr-day.today { border-color: var(--df-brand-01); }
        .flatpickr-months .flatpickr-month, .flatpickr-current-month .flatpickr-monthDropdown-months { text-transform: capitalize; }
        .gb-logs-filters input[readonly] { background-color: #fff; cursor: pointer; }
    </style>
@endsection

@section('content')
    <section class="card" aria-labelledby="logs-title">
        <div class="card-header d-flex align-items-center gap-3">
            <div class="flex-grow-1">
                <h2 id="logs-title" class="card-title">Atividade no sistema</h2>
                <p class="card-description">Visível só para o superadmin. A localização é aproximada, calculada pelo IP.</p>
            </div>
            <span class="gb-badge gb-badge-neutral">{{ $logs->total() }} {{ $logs->total() === 1 ? 'registro' : 'registros' }}</span>
        </div>

        <form method="GET" action="{{ route('admin.logs.index') }}" class="gb-logs-filters">
            <div>
                <label for="log-de" class="form-label">De</label>
                <input type="text" id="log-de" name="de" class="form-control" data-log-date value="{{ $filters['de']?->format('Y-m-d') }}">
            </div>
            <div>
                <label for="log-ate" class="form-label">Até</label>
                <input type="text" id="log-ate" name="ate" class="form-control" data-log-date value="{{ $filters['ate']?->format('Y-m-d') }}">
            </div>
            <div>
                <label for="log-usuario" class="form-label">Usuário</label>
                <select id="log-usuario" name="usuario" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected($filters['usuario'] === $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="log-area" class="form-label">Área</label>
                <select id="log-area" name="area" class="form-select">
                    <option value="">Todas</option>
                    @foreach (array_keys(\App\Services\ActivityLogger::EVENTS) as $area)
                        <option value="{{ $area }}" @selected($filters['area'] === $area)>{{ $area }}</option>
                    @endforeach
                </select>
            </div>
            <div class="gb-logs-search">
                <label for="log-busca" class="form-label">Buscar</label>
                <input type="search" id="log-busca" name="busca" class="form-control" value="{{ $filters['busca'] }}" placeholder="Cliente, usuário, IP, cidade…">
            </div>
            <div class="gb-logs-actions">
                <button type="submit" class="btn btn-primary"><i data-lucide="filter"></i> Filtrar</button>
                <a href="{{ route('admin.logs.index') }}" class="btn btn-white">Limpar</a>
            </div>
        </form>

        @if ($logs->isEmpty())
            <div class="gb-library-empty">
                <i data-lucide="scroll-text"></i>
                <p class="gb-hint-title">Nenhum registro encontrado</p>
                <p class="m-0">Ajuste os filtros ou aguarde novas ações no sistema.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="gb-table gb-logs-table">
                    <thead>
                        <tr>
                            <th scope="col">Data e hora</th>
                            <th scope="col">Usuário</th>
                            <th scope="col">Ação</th>
                            <th scope="col">Origem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="text-nowrap">
                                    {{ $log->created_at->format('d/m/Y') }}<br>
                                    <span class="gb-petition-meta">{{ $log->created_at->format('H:i:s') }}</span>
                                </td>
                                <td>{{ $log->user_name ?? 'Não identificado' }}</td>
                                <td>
                                    <p class="gb-petition-title m-0">{{ \App\Services\ActivityLogger::label($log->event) }}</p>
                                    <p class="gb-petition-meta m-0">
                                        {{ $log->description }}
                                        @if ($log->subject_label) · <strong>{{ $log->subject_label }}</strong> @endif
                                    </p>
                                    @foreach ($log->properties ?? [] as $name => $value)
                                        <p class="gb-petition-meta m-0">{{ $name }}: {{ is_array($value) ? implode(', ', $value) : $value }}</p>
                                    @endforeach
                                </td>
                                <td>
                                    <p class="m-0"><i data-lucide="map-pin" class="gb-logs-icon"></i> {{ $log->location ?? 'Localizando…' }}</p>
                                    <p class="gb-petition-meta m-0">IP {{ $log->ip ?? '—' }}</p>
                                    <p class="gb-petition-meta m-0" title="{{ $log->user_agent }}">{{ collect([$log->browser, $log->platform, $log->device])->filter()->implode(' · ') ?: 'Navegador não identificado' }}</p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="gb-list-summary gb-list-summary-footer justify-content-between">
                <span>Página {{ $logs->currentPage() }} de {{ $logs->lastPage() }}</span>
                <span class="d-flex gap-2">
                    @if ($logs->onFirstPage())
                        <span class="btn btn-white btn-sm disabled" aria-disabled="true"><i data-lucide="arrow-left"></i> Anterior</span>
                    @else
                        <a href="{{ $logs->previousPageUrl() }}" class="btn btn-white btn-sm"><i data-lucide="arrow-left"></i> Anterior</a>
                    @endif
                    @if ($logs->hasMorePages())
                        <a href="{{ $logs->nextPageUrl() }}" class="btn btn-white btn-sm">Próxima <i data-lucide="arrow-right"></i></a>
                    @else
                        <span class="btn btn-white btn-sm disabled" aria-disabled="true">Próxima <i data-lucide="arrow-right"></i></span>
                    @endif
                </span>
            </div>
        @endif

        <p class="gb-petition-meta px-4 pb-3 m-0">Localização por IP: <a href="https://db-ip.com" target="_blank" rel="noopener">IP Geolocation by DB-IP</a>.</p>
    </section>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/pt.js"></script>
    <script>
        document.querySelectorAll('[data-log-date]').forEach(input => {
            const picker = flatpickr(input, { locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', disableMobile: true });
            picker.altInput.placeholder = 'dd/mm/aaaa';
            picker.altInput.id = input.id;
            input.removeAttribute('id');
        });
    </script>
@endsection
