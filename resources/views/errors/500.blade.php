@extends('layouts.app')

@section('title', 'Erro inesperado')

@section('content')
    <div class="card gb-narrow mx-auto mt-4">
        <div class="gb-library-empty">
            <i data-lucide="triangle-alert"></i>
            <p class="gb-hint-title">Ocorreu um erro inesperado</p>
            <p class="m-0">Tente novamente em instantes. Se o problema continuar, fale com o administrador do sistema.</p>
            <a href="{{ route('home') }}" class="btn btn-primary mt-3">Voltar ao início</a>
        </div>
    </div>
@endsection
