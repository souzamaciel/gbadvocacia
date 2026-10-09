@extends('layouts.app')

@section('title', 'Home')

@section('fonts')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&family=Montserrat:wght@400;500&display=swap" rel="stylesheet">
@endsection

@section('body-font', "'Montserrat', sans-serif")

@section('content')
    <main class="d-flex flex-grow-1 align-items-center py-5">
        <div class="container-fluid px-4 px-sm-5">
            <div class="row align-items-center justify-content-center g-5">

                {{-- Bloco de texto --}}
                <div class="col-12 col-lg-auto text-center text-lg-start" style="max-width: 622px;">
                    <h1 class="fw-medium text-white mb-4" style="font-size: clamp(1.5rem, 4vw, 2.25rem);">
                        Gerador de Petição Inicial
                    </h1>
                    <p class="text-white text-opacity-90 mb-5" style="line-height: 1.75;">
                        Preencha o formulário de atendimento com as informações do cliente. A IA analisará o caso e sugerirá automaticamente os pedidos jurídicos para a petição inicial.
                    </p>
                    <div class="d-flex gap-3 justify-content-center justify-content-lg-start flex-wrap">
                        {{-- Botão primário --}}
                        <a href="{{ route('appointment.create') }}"
                           class="btn btn-lg rounded-pill d-flex align-items-center gap-2 text-white"
                           style="background-color: var(--gb-gold);">
                            Iniciar atendimento
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M7.5 4.17 13.33 10 7.5 15.83" stroke="white" stroke-width="1.67" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>

                        {{-- Botão secundário --}}
                        <a href="{{ route('petition.create') }}"
                           class="btn btn-lg rounded-pill"
                           style="color: var(--gb-gold); border: 1px solid var(--gb-gold);">
                            Gerar petição
                        </a>
                    </div>
                </div>

                {{-- Imagem ilustrativa --}}
                <div class="col-auto d-none d-lg-block">
                    <img src="{{ asset('images/main_logo.png') }}" alt="GB Advocacia" width="571" height="571" class="object-fit-contain">
                </div>

            </div>
        </div>
    </main>
@endsection
