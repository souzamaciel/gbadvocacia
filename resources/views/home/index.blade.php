@extends('layouts.app')

@section('title', 'Início')

@section('page-header')
    <x-page-header title="Olá, {{ auth()->user()->name }}"
                   description="Comece um atendimento ou monte uma petição inicial."
                   :breadcrumbs="['Início']" />
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column align-items-start gap-3 p-4">
                    <span class="gb-icon-tile"><i data-lucide="clipboard-list"></i></span>
                    <div>
                        <h2 class="card-title">Atendimento</h2>
                        <p class="card-description">Preencha o formulário com os dados do cliente. A IA analisa o caso e sugere os pedidos.</p>
                    </div>
                    <a href="{{ route('appointment.create') }}" class="btn btn-primary mt-auto">
                        Iniciar atendimento <i data-lucide="arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column align-items-start gap-3 p-4">
                    <span class="gb-icon-tile"><i data-lucide="file-text"></i></span>
                    <div>
                        <h2 class="card-title">Petição inicial</h2>
                        <p class="card-description">Escolha as preliminares e os pedidos e gere o documento Word no formato do escritório.</p>
                    </div>
                    <a href="{{ route('petition.create') }}" class="btn btn-white mt-auto">
                        Montar petição <i data-lucide="arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
