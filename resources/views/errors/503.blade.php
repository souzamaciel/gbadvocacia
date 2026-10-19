@extends('layouts.app')

@section('title', 'Em manutenção')

@section('content')
    <div class="card gb-narrow mx-auto mt-4">
        <div class="gb-library-empty">
            <i data-lucide="wrench"></i>
            <p class="gb-hint-title">O sistema está em manutenção</p>
            <p class="m-0">Estamos fazendo uma atualização. Tente novamente em alguns minutos.</p>
            <a href="{{ route('home') }}" class="btn btn-primary mt-3">Tentar novamente</a>
        </div>
    </div>
@endsection
