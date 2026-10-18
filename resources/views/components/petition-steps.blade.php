@props(['current', 'petition' => null])

@php
    $steps = [
        1 => ['Selecionar e organizar', 'Escolha os tópicos', $petition ? route('petition.edit', $petition) : null],
        2 => ['Revisar conteúdo', 'Ajuste os textos', $petition ? route('petition.review', $petition) : null],
        3 => ['Gerar documento', 'Word', $petition && $petition->isFullyReviewed() ? route('petition.generate.show', $petition) : null],
    ];
@endphp

<nav class="card gb-wizard" aria-label="Etapas da petição">
    <ol>
        @foreach ($steps as $number => [$title, $description, $url])
            @php($state = $number < $current ? 'complete' : ($number === $current ? 'active' : 'default'))
            <li class="gb-wizard-step is-{{ $state }}" @if ($state === 'active') aria-current="step" @endif>
                @if ($url && $state !== 'active')
                    <a href="{{ $url }}" class="gb-wizard-link">
                @else
                    <span class="gb-wizard-link">
                @endif
                    <span class="gb-wizard-number">
                        @if ($state === 'complete')
                            <i data-lucide="check"></i>
                        @else
                            {{ $number }}
                        @endif
                    </span>
                    <span>
                        <span class="gb-wizard-title">{{ $title }}</span>
                        <span class="gb-wizard-description">{{ $description }}</span>
                    </span>
                @if ($url && $state !== 'active')
                    </a>
                @else
                    </span>
                @endif
            </li>
            @if (! $loop->last)
                <li class="gb-wizard-separator" aria-hidden="true"><i data-lucide="chevron-right"></i></li>
            @endif
        @endforeach
    </ol>
</nav>
