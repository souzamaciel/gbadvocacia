@extends('layouts.app')

@section('title', $topic ? 'Editar tópico' : 'Novo tópico')

@section('fonts')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
@endsection

@section('navbar-class', 'bg-dark bg-opacity-10')

@section('body-font', "'Inter', sans-serif")

@section('page-header')
    <div class="container-fluid px-4 px-sm-5 py-4 d-flex align-items-center gap-2">
        <div class="flex-shrink-0" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
            <a href="{{ route('settings.topics.index') }}">
                <i class="fa-regular fa-circle-left text-white fs-5"></i>
            </a>
        </div>

        <div class="flex-grow-1 d-flex flex-column gap-2">
            <h1 style="color: var(--gb-gold);font-size:24px;font-family:'Roboto',sans-serif;font-weight:500;line-height:26.15px;margin:0;">
                {{ $topic ? 'Editar tópico' : 'Novo tópico' }}
            </h1>
            <p style="color:white;font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:13px;margin:0;">
                {{ $topic ? 'Altere as informações do tópico.' : 'Preencha as informações para criar um novo tópico.' }}
            </p>
        </div>

        <nav class="d-none d-sm-flex align-items-center gap-1 flex-shrink-0 ps-4"
             style="font-size:13px;font-family:'Roboto',sans-serif;font-weight:400;line-height:20px;color:white;">
            <a href="{{ route('home') }}" class="text-decoration-none" style="color:white;">Home</a>
            <span class="px-1" style="color:white;">/</span>
            <a href="{{ route('settings.topics.index') }}" class="text-decoration-none" style="color:white;">Tópicos</a>
            <span class="px-1" style="color:white;">/</span>
            <span style="color:white;">{{ $topic ? 'Editar' : 'Novo' }}</span>
        </nav>
    </div>
@endsection

@section('styles')
<style>
    .section-card { background:#fff; border:1px solid #e2e8f0; border-radius:.5rem; margin-bottom:1rem; }
    .section-header { padding:.75rem 1.25rem; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; gap:.75rem; }
    .section-badge { width:28px;height:28px;min-width:28px; border-radius:4px; background-color:var(--gb-navy); color:white; font-size:11px;font-weight:700; display:flex;align-items:center;justify-content:center; }
    .section-title { font-size:12px;font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#1e293b; margin:0; }
    .section-body { padding:1.5rem; display:flex; flex-direction:column; gap:1.25rem; }
    .form-label { font-size:12px;font-weight:500; color:#64748b; margin-bottom:4px; }
    .form-control, .form-select { font-size:13px;color:#314158; }
    .form-control:focus, .form-select:focus { border-color:var(--gb-navy); box-shadow:0 0 0 .2rem rgba(35,42,84,.15); }
    .form-control::placeholder { color:rgba(10,10,10,.35); }
    .btn-submit { background-color:var(--gb-navy); color:#fff; font-size:13px; font-weight:500; padding:.6rem 1.5rem; border:none; border-radius:6px; font-family:'Roboto',sans-serif; }
    .btn-submit:hover { opacity:.9; color:#fff; }
    .content-textarea { min-height:500px; resize:vertical; line-height:1.6; }
</style>
@endsection

@section('content')
    <div class="container-fluid px-4 px-sm-5 pb-5">
        <div class="mx-auto" style="max-width: 800px;">

            <form method="POST"
                  action="{{ $topic ? route('settings.topics.update', $topic) : route('settings.topics.store') }}"
                  novalidate>
                @csrf
                @if ($topic)
                    @method('PUT')
                @endif

                <div class="section-card">
                    <div class="section-header">
                        <span class="section-badge"><i class="fa-solid fa-file-lines" style="font-size:12px;"></i></span>
                        <h6 class="section-title">Informações do tópico</h6>
                    </div>
                    <div class="section-body">

                        <div class="row g-3">
                            {{-- Grupo --}}
                            <div class="col-12 col-md-5">
                                <label for="group_id" class="form-label">Grupo</label>
                                <select id="group_id"
                                        name="group_id"
                                        class="form-select form-select-sm @error('group_id') is-invalid @enderror">
                                    <option value="">Selecione um grupo</option>
                                    @foreach ($groups as $group)
                                        <option value="{{ $group->id }}"
                                            @selected(old('group_id', $topic?->group_id) == $group->id)>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('group_id')
                                    <div class="text-danger mt-1" style="font-size:11px;">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Título --}}
                            <div class="col-12 col-md-7">
                                <label for="title" class="form-label">Título</label>
                                <input type="text"
                                       id="title"
                                       name="title"
                                       value="{{ old('title', $topic?->title) }}"
                                       class="form-control form-control-sm @error('title') is-invalid @enderror"
                                       placeholder="Ex: Horas extras habituais">
                                @error('title')
                                    <div class="text-danger mt-1" style="font-size:11px;">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Conteúdo --}}
                        <div>
                            <label for="content" class="form-label">Conteúdo</label>
                            <textarea id="content"
                                      name="content"
                                      rows="20"
                                      class="form-control content-textarea @error('content') is-invalid @enderror"
                                      placeholder="Digite o conteúdo completo do tópico...">{{ old('content', $topic?->content) }}</textarea>
                            @error('content')
                                <div class="text-danger mt-1" style="font-size:11px;">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2">
                            <a href="{{ route('settings.topics.index') }}" class="btn btn-sm"
                               style="color:#64748b;font-size:13px;font-family:'Roboto',sans-serif;">
                                Cancelar
                            </a>
                            <button type="submit" class="btn-submit">
                                {{ $topic ? 'Salvar alterações' : 'Criar tópico' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
