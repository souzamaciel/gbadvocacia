@php
    $navigation = [
        ['label' => 'Atendimentos', 'route' => 'appointment.create', 'active' => 'appointment.*'],
        ['label' => 'Petições', 'route' => 'petition.index', 'active' => 'petition.*'],
        ['label' => 'Biblioteca de tópicos', 'route' => 'settings.topics.index', 'active' => 'settings.*'],
    ];
@endphp

<header class="gb-header">
    <a class="gb-header-logo" href="{{ route('home') }}" aria-label="GB Advocacia — início">
        <img src="{{ asset('images/logo.svg') }}" alt="Grimaldo Bruno Advocacia" width="142" height="35">
    </a>

    <nav aria-label="Navegação principal" class="h-100">
        <ul class="gb-nav">
            @foreach ($navigation as $item)
                <li>
                    <a href="{{ route($item['route']) }}"
                       class="gb-nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}"
                       @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="gb-account dropdown">
        <button type="button" class="gb-account-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i data-lucide="user"></i>
            <span>{{ auth()->user()->name }}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            @include('layouts.partials.account-menu')
        </ul>
    </div>

    <button type="button" class="gb-menu-toggle" data-bs-toggle="offcanvas" data-bs-target="#gb-offcanvas"
            aria-controls="gb-offcanvas" aria-label="Abrir menu">
        <i data-lucide="menu"></i>
    </button>
</header>

<div class="offcanvas offcanvas-end gb-offcanvas" tabindex="-1" id="gb-offcanvas" aria-labelledby="gb-offcanvas-title">
    <div class="offcanvas-header">
        <img id="gb-offcanvas-title" src="{{ asset('images/logo.svg') }}" alt="Grimaldo Bruno Advocacia" width="142" height="35">
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Fechar menu"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column gap-4">
        <ul class="gb-nav">
            @foreach ($navigation as $item)
                <li>
                    <a href="{{ route($item['route']) }}"
                       class="gb-nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}">
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-auto">
            <p class="form-label text-white-50 mb-2">{{ auth()->user()->name }}</p>
            <ul class="list-unstyled bg-white rounded-1 py-1 m-0">
                @include('layouts.partials.account-menu')
            </ul>
        </div>
    </div>
</div>
