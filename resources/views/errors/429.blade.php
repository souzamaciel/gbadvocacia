@extends('layouts.app')

@section('title', 'Muitas tentativas')

@section('content')
    <div class="card gb-narrow mx-auto mt-4">
        <div class="gb-library-empty">
            <i data-lucide="hourglass"></i>
            <p class="gb-hint-title">Muitas tentativas seguidas</p>
            <p class="m-0">Aguarde um pouco e tente novamente.</p>
            <a href="{{ route('home') }}" class="btn btn-primary mt-3">Voltar ao início</a>
        </div>
    </div>
@endsection
