@php
    $suggested = $line['sugerido'] ?? null;
    $current = old($field, is_int($line['valor'] ?? null) ? \App\Services\MoneyInWords::formatCents($line['valor']) : '');
    $inputId = 'value-'.\Illuminate\Support\Str::slug($field);
@endphp

<li class="gb-value-line {{ $isSub ? 'is-sub' : '' }}">
    <div class="gb-value-text">
        <label for="{{ $inputId }}" class="gb-value-title">
            <span class="gb-value-letter">{{ $label }}</span> {{ $line['descricao'] }}
        </label>
        @if (($line['memoria'] ?? '') !== '')
            <p class="gb-value-memory">{{ $line['memoria'] }}</p>
        @endif
        @if (is_int($suggested))
            <p class="gb-value-memory" data-suggestion hidden>Cálculo do sistema: {{ \App\Services\MoneyInWords::formatCents($suggested) }}</p>
        @endif
    </div>
    <div class="gb-value-amount">
        <input type="text" id="{{ $inputId }}" name="{{ $name }}" value="{{ $current }}" inputmode="numeric" autocomplete="off"
               class="form-control text-end @error($field) is-invalid @enderror" placeholder="R$ ________"
               data-amount data-suggested="{{ is_int($suggested) ? $suggested : '' }}"
               @error($field) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @enderror>
        @error($field)
            <div id="{{ $inputId }}-error" class="invalid-feedback">{{ $message }}</div>
        @enderror
        <span class="gb-value-blank-badge gb-badge gb-badge-warning">A conferir</span>
    </div>
</li>
