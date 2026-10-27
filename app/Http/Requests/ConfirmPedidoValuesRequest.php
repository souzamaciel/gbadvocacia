<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmPedidoValuesRequest extends FormRequest
{
    private const AMOUNT = ['nullable', 'string', 'max:30', 'regex:/^\s*(R\$\s*)?\d{1,3}(\.\d{3})*(,\d{1,2})?\s*$|^\s*(R\$\s*)?\d+(,\d{1,2})?\s*$/'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pedidos' => ['array'],
            'pedidos.*.valor' => self::AMOUNT,
            'pedidos.*.subitens' => ['array'],
            'pedidos.*.subitens.*.valor' => self::AMOUNT,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pedidos.*.valor.regex' => 'Use o formato R$ 1.234,56.',
            'pedidos.*.valor.max' => 'Use o formato R$ 1.234,56.',
            'pedidos.*.subitens.*.valor.regex' => 'Use o formato R$ 1.234,56.',
            'pedidos.*.subitens.*.valor.max' => 'Use o formato R$ 1.234,56.',
        ];
    }
}
