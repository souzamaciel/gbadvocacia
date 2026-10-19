@extends('layouts.app')

@section('title', 'Sessão expirada')

@section('content')
    <div class="card gb-narrow mx-auto mt-4">
        <div class="gb-library-empty">
            <i data-lucide="timer-reset"></i>
            <p class="gb-hint-title">Sua sessão expirou</p>
            <p class="m-0">Por segurança, a página ficou muito tempo aberta sem uso. Recarregue e tente novamente.</p>
            <a href="{{ url()->previous() }}" class="btn btn-primary mt-3">Recarregar</a>
        </div>
    </div>
@endsection
