@extends('layouts.app')

@section('title', 'Acesso necessário')

@section('content')
    <div class="card gb-narrow mx-auto mt-4">
        <div class="gb-library-empty">
            <i data-lucide="lock"></i>
            <p class="gb-hint-title">Entre no sistema para continuar</p>
            <p class="m-0">Sua sessão terminou ou você ainda não entrou.</p>
            <a href="{{ route('login') }}" class="btn btn-primary mt-3">Entrar</a>
        </div>
    </div>
@endsection
