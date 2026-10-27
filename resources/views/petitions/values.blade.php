@extends('layouts.app')

@section('title', 'Valores dos pedidos')

@php
    $money = fn ($cents) => is_int($cents) ? \App\Services\MoneyInWords::formatCents($cents) : '';
    $pedidos = $petition->pedidos ?? [];
    $toCheck = collect($pedidos)->sum(fn ($pedido) => (int) ($pedido['valor'] === null) + collect($pedido['subitens'])->where('valor', null)->count());
@endphp

@section('page-header')
    <x-page-header title="Valores dos pedidos"
                   description="Confira o valor estimado de cada pedido e ajuste o que precisar. O valor da causa é a soma."
                   :breadcrumbs="['Petições' => route('petition.index'), $petition->name]">
        <x-slot:actions>
            <button type="button" class="btn btn-white js-cancel-flow" data-item="a petição"
                    data-list-url="{{ route('petition.index') }}"
                    data-delete-url="{{ route('petition.destroy', $petition) }}"
                    @cannot('petitions.delete') data-can-delete="false" @endcannot>
                <i data-lucide="x"></i> Cancelar
            </button>
            <span class="gb-badge {{ $petition->hasConfirmedPedidos() ? 'gb-badge-success' : 'gb-badge-neutral' }}">
                {{ $petition->hasConfirmedPedidos() ? 'Valores confirmados' : 'A conferir' }}
            </span>
        </x-slot:actions>
    </x-page-header>
@endsection

@section('content')
    <x-petition-steps :current="3" :petition="$petition" />

    @if ($needsCalculation)
        <section class="card" id="calculation-card" aria-live="polite">
            <div class="gb-library-empty" id="calculation-progress">
                <span class="spinner-border text-secondary" aria-hidden="true"></span>
                <p class="gb-hint-title mt-3">Calculando os valores dos pedidos…</p>
                <p class="m-0">A IA lê a ficha e os tópicos, e o sistema faz as contas. Leva cerca de um minuto.</p>
            </div>
            <div class="gb-library-empty" id="calculation-error" hidden>
                <i data-lucide="circle-alert"></i>
                <p class="gb-hint-title">Não foi possível calcular os valores</p>
                <p class="m-0" id="calculation-error-text"></p>
                <button type="button" class="btn btn-primary mt-3" id="calculation-retry"><i data-lucide="refresh-cw"></i> Tentar de novo</button>
            </div>
        </section>
    @else
        @unless ($hasCaseData)
            <div class="card gb-hint-card mb-3">
                <i data-lucide="info"></i>
                <div>
                    <p class="gb-hint-title">Petição montada sem ficha de atendimento</p>
                    <p class="gb-hint-text">Sem salário e datas do contrato, a maioria dos valores fica em branco. Preencha os valores ou deixe em branco para completar no Word.</p>
                </div>
            </div>
        @endunless

        <form method="POST" action="{{ route('petition.values.update', $petition) }}" id="values-form" novalidate>
            @csrf
            @method('PUT')

            <div class="row g-3 align-items-start">
                <div class="col-12 col-xl-8">
                    <section class="card" aria-labelledby="values-title">
                        <div class="card-header">
                            <h2 class="card-title" id="values-title">Pedidos</h2>
                            <p class="card-description">Cada valor mostra como foi calculado. Valores em branco saem como “R$ ________” no Word.</p>
                        </div>

                        <ol class="gb-values-list">
                            @foreach ($pedidos as $index => $pedido)
                                @php($letter = chr(ord('a') + ($index % 26)))
                                @include('petitions.partials.value-line', [
                                    'label' => $letter.')',
                                    'line' => $pedido,
                                    'name' => "pedidos[{$index}][valor]",
                                    'field' => "pedidos.{$index}.valor",
                                    'isSub' => false,
                                ])
                                @foreach ($pedido['subitens'] as $subIndex => $subitem)
                                    @include('petitions.partials.value-line', [
                                        'label' => $letter.'.'.($subIndex + 1).')',
                                        'line' => $subitem,
                                        'name' => "pedidos[{$index}][subitens][{$subIndex}][valor]",
                                        'field' => "pedidos.{$index}.subitens.{$subIndex}.valor",
                                        'isSub' => true,
                                    ])
                                @endforeach
                            @endforeach
                        </ol>
                    </section>
                </div>

                <div class="col-12 col-xl-4">
                    <section class="card gb-values-summary" aria-labelledby="total-title">
                        <div class="card-body p-4 d-flex flex-column gap-3">
                            <div>
                                <p class="form-label mb-1" id="total-title">Valor da causa</p>
                                <p class="gb-values-total m-0" id="values-total">{{ $money($total) }}</p>
                                <p class="gb-petition-meta m-0" id="values-pending">
                                    {{ $toCheck === 0 ? 'Todos os pedidos têm valor.' : $toCheck.' '.($toCheck === 1 ? 'valor em branco' : 'valores em branco').' (não entram na soma).' }}
                                </p>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i data-lucide="check"></i> Confirmar valores e avançar
                            </button>
                            <button type="button" class="btn btn-white w-100" id="recalculate">
                                <i data-lucide="calculator"></i> Calcular de novo
                            </button>
                            <p class="gb-field-hint m-0">Os valores são estimativas para a petição inicial (art. 840, §1º, da CLT), não um cálculo de liquidação.</p>
                        </div>
                    </section>
                </div>
            </div>

            <div class="gb-form-actions">
                <a href="{{ route('petition.review', $petition) }}" class="btn btn-white"><i data-lucide="arrow-left"></i> Voltar à revisão</a>
                <span class="gb-field-hint m-0">Os valores digitados ficam salvos ao confirmar.</span>
            </div>
        </form>
    @endif
@endsection

@section('scripts')
    <script>
        (function () {
            const calculateUrl = '{{ route('petition.values.calculate', $petition) }}';
            const csrf = '{{ csrf_token() }}';

            function calculate() {
                const progress = document.getElementById('calculation-progress');
                const failure = document.getElementById('calculation-error');
                if (progress) { progress.hidden = false; failure.hidden = true; }
                window.gbIsDirty = () => true;

                return gbGeneration.run(calculateUrl, new FormData(), csrf)
                    .then(({ result }) => {
                        window.gbIsDirty = () => false;
                        window.location.href = result.redirect;
                    })
                    .catch(error => {
                        window.gbIsDirty = () => false;
                        if (progress) {
                            progress.hidden = true;
                            failure.hidden = false;
                            document.getElementById('calculation-error-text').textContent = error.message;
                        } else {
                            Swal.fire({ icon: 'error', title: 'Não foi possível calcular', text: error.message, confirmButtonColor: 'var(--df-brand-01)' });
                        }
                        throw error;
                    });
            }

            @if ($needsCalculation)
                calculate().catch(() => {});
                document.getElementById('calculation-retry').addEventListener('click', () => calculate().catch(() => {}));
            @else
                const form = document.getElementById('values-form');
                const inputs = [...form.querySelectorAll('[data-amount]')];
                const toCents = value => {
                    const digits = String(value).replace(/\D/g, '');
                    return digits === '' ? null : Number(digits);
                };
                const format = cents => 'R$ ' + Math.floor(cents / 100).toLocaleString('pt-BR') + ',' + String(cents % 100).padStart(2, '0');
                let dirty = false;
                window.gbIsDirty = () => dirty;

                function refresh() {
                    let total = 0;
                    let blanks = 0;
                    inputs.forEach(input => {
                        const cents = toCents(input.value);
                        if (cents === null) { blanks++; } else { total += cents; }
                        const row = input.closest('.gb-value-line');
                        row.classList.toggle('is-blank', cents === null);
                        const suggestion = row.querySelector('[data-suggestion]');
                        if (suggestion) { suggestion.hidden = cents === Number(input.dataset.suggested || -1) || input.dataset.suggested === ''; }
                    });
                    document.getElementById('values-total').textContent = format(total);
                    document.getElementById('values-pending').textContent = blanks === 0
                        ? 'Todos os pedidos têm valor.'
                        : `${blanks} ${blanks === 1 ? 'valor em branco' : 'valores em branco'} (não entram na soma).`;
                }

                inputs.forEach(input => input.addEventListener('input', () => {
                    const cents = toCents(input.value);
                    input.value = cents === null ? '' : format(cents);
                    dirty = true;
                    refresh();
                }));
                form.addEventListener('submit', () => { dirty = false; });
                refresh();

                document.getElementById('recalculate').addEventListener('click', event => {
                    Swal.fire({
                        icon: 'question',
                        title: 'Calcular de novo?',
                        text: 'A IA lê a ficha e os tópicos outra vez e os valores digitados são substituídos pelos novos cálculos.',
                        showCancelButton: true,
                        confirmButtonText: 'Calcular de novo',
                        cancelButtonText: 'Manter os valores',
                        confirmButtonColor: 'var(--df-brand-01)',
                    }).then(answer => {
                        if (!answer.isConfirmed) { return; }
                        const button = event.target.closest('button');
                        button.disabled = true;
                        button.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Calculando… cerca de um minuto';
                        dirty = false;
                        calculate().catch(() => {
                            button.disabled = false;
                            button.innerHTML = '<i data-lucide="calculator"></i> Calcular de novo';
                            lucide.createIcons();
                        });
                    });
                });
            @endif
        })();
    </script>
@endsection
