@extends('layouts.app')

@section('title', 'Sem permissão')

@section('content')
    <div class="card gb-narrow mx-auto mt-4">
        <div class="gb-library-empty">
            <i data-lucide="lock"></i>
            <p class="gb-hint-title">Seu perfil não tem permissão para esta tela</p>
            <p class="m-0">Se precisar deste acesso, fale com o administrador do sistema.</p>
            <a href="{{ route('home') }}" class="btn btn-primary mt-3">Voltar ao início</a>
        </div>
    </div>
@endsection
