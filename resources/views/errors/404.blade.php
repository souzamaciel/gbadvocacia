@extends('layouts.app')

@section('title', 'Página não encontrada')

@section('content')
    <div class="card gb-narrow mx-auto mt-4">
        <div class="gb-library-empty">
            <i data-lucide="search-x"></i>
            <p class="gb-hint-title">Página não encontrada</p>
            <p class="m-0">O endereço pode estar errado ou o item foi excluído.</p>
            <a href="{{ route('home') }}" class="btn btn-primary mt-3">Voltar ao início</a>
        </div>
    </div>
@endsection
