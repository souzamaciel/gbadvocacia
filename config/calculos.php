<?php

/*
|--------------------------------------------------------------------------
| Estimativa dos valores dos pedidos
|--------------------------------------------------------------------------
|
| Valores oficiais usados nas contas da etapa "Valores dos pedidos". Atualize
| quando o governo publicar os novos valores do ano.
|
*/

return [

    /*
     * Decreto 12.797/2025, em vigor desde 01/01/2026.
     */
    'salario_minimo' => env('CALCULO_SALARIO_MINIMO', '1621.00'),

    /*
     * Tabela do seguro-desemprego de 2026 (MTE, desde 11/01/2026): até a primeira faixa,
     * 80% do salário médio; até a segunda, o valor fixo mais 50% do que passar da primeira
     * faixa; acima, o teto. Nenhuma parcela fica abaixo do salário mínimo.
     */
    'seguro_desemprego' => [
        'faixa_1' => env('CALCULO_SEGURO_FAIXA_1', '2222.17'),
        'faixa_2' => env('CALCULO_SEGURO_FAIXA_2', '3703.99'),
        'valor_faixa_2' => env('CALCULO_SEGURO_VALOR_FAIXA_2', '1777.74'),
        'teto' => env('CALCULO_SEGURO_DESEMPREGO_TETO', '2518.65'),
    ],

    'divisor' => 220,

    'anos_prescricao' => 5,

];
