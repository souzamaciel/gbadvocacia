<?php

namespace Tests\Feature;

use App\Services\Pedidos\CaseFacts;
use App\Services\Pedidos\PedidoCalculator;
use Tests\TestCase;

class PedidoCalculatorTest extends TestCase
{
    private const CASE = [
        'contrato_admissao' => '2024-03-01',
        'contrato_dispensa' => '2026-09-15',
        'salario_ultimo' => 'R$ 2.200,00',
        'extincao_causa' => 'Sem justa causa',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-26 10:00:00');
    }

    public function test_full_case_matches_the_hand_calculation(): void
    {
        $result = $this->calculate(self::CASE, [
            $this->line('Pagamento das horas extras', 'horas_extras', quantity: 78, percent: 50, explanation: '3 h extras/dia × 26 dias.', subitems: [
                $this->line('Reflexos em RSR', 'reflexo_rsr'),
                $this->line('Reflexos em 13º salários', 'reflexo_decimo_terceiro'),
                $this->line('Reflexos em férias + 1/3', 'reflexo_ferias'),
                $this->line('Reflexos em aviso prévio', 'reflexo_aviso_previo'),
                $this->line('Reflexos em FGTS + 40%', 'reflexo_fgts_multa'),
            ]),
            $this->line('Saldo de salário', 'saldo_salario', subitems: [
                $this->line('Aviso prévio indenizado', 'aviso_previo'),
                $this->line('13º salário proporcional', 'decimo_terceiro'),
                $this->line('Férias proporcionais + 1/3', 'ferias_proporcionais'),
            ]),
            $this->line('Multa do art. 477 da CLT', 'multa_477'),
            $this->line('Multa do art. 467 da CLT', 'multa_467'),
            $this->line('Multa de 40% do FGTS', 'multa_fgts'),
            $this->line('Indenização substitutiva do seguro-desemprego', 'seguro_desemprego'),
            $this->line('Indenização por danos morais', 'indenizacao', quantity: 5),
            $this->line('Honorários advocatícios (15%)', 'honorarios', percent: 15),
        ]);

        $this->assertSame([3567330, 594555, 346824, 462432, 163800, 523321], [$result[0]['valor'], ...array_column($result[0]['subitens'], 'valor')]);
        $this->assertSame([110000, 264000, 183333, 195556], [$result[1]['valor'], ...array_column($result[1]['subitens'], 'valor')]);
        $this->assertSame([220000, 376445, 214650, 880000, 1100000, 1380337], array_column(array_slice($result, 2), 'valor'));

        $this->assertSame('78 h extras/mês × hora de R$ 10,00 (R$ 2.200,00 ÷ 220) × 1,5 (adicional de 50%) × 30,49 meses. 3 h extras/dia × 26 dias.', $result[0]['memoria']);
        $this->assertSame('1/6 de R$ 35.673,30 (média de 1 descanso para 6 dias de trabalho)', $result[0]['subitens'][0]['memoria']);
        $this->assertStringContainsString('36 dias de aviso', $result[0]['subitens'][3]['memoria']);
        $this->assertSame('10/12 × R$ 2.200,00 (01/01/2026 a 21/10/2026, com a projeção do aviso)', $result[1]['subitens'][1]['memoria']);
        $this->assertSame('5 parcelas × R$ 1.760,00 (80% do salário, tabela do seguro-desemprego)', $result[5]['memoria']);
        $this->assertSame('15% × R$ 92.022,46 (soma dos demais pedidos)', $result[7]['memoria']);
    }

    public function test_unemployment_installment_follows_the_2026_brackets(): void
    {
        $installment = fn (string $salary): array => $this->calculate(
            [...self::CASE, 'salario_ultimo' => $salary],
            [$this->line('Indenização substitutiva do seguro-desemprego', 'seguro_desemprego', quantity: 1)],
        )[0];

        $this->assertSame(177774, $installment('R$ 2.222,17')['valor']);
        $this->assertSame(216666, $installment('R$ 3.000,00')['valor']);
        $this->assertSame('1 parcela × R$ 2.166,66 (R$ 1.777,74 + 50% do que passa de R$ 2.222,17, tabela do seguro-desemprego)', $installment('R$ 3.000,00')['memoria']);
        $this->assertSame(251865, $installment('R$ 9.000,00')['valor']);
        $this->assertSame(162100, $installment('R$ 1.621,00')['valor']);
        $this->assertSame('1 parcela × R$ 1.621,00 (salário mínimo, piso do benefício, tabela do seguro-desemprego)', $installment('R$ 1.621,00')['memoria']);
    }

    public function test_only_the_last_five_years_are_claimed(): void
    {
        $result = $this->calculate(
            ['contrato_admissao' => '2018-01-10', 'contrato_dispensa' => '2026-06-30', 'salario_ultimo' => '2200'],
            [$this->line('Adicional de periculosidade', 'adicional_periculosidade')],
        );

        $months = round(today()->subYears(5)->diffInDays('2026-06-30') / 30.4375, 2);
        $this->assertSame((int) round(220000 * 0.3 * $months), $result[0]['valor']);
        $this->assertStringContainsString('meses (últimos 5 anos)', $result[0]['memoria']);
    }

    public function test_money_paid_off_the_books_joins_the_salary(): void
    {
        $result = $this->calculate(
            [...self::CASE, 'salario_por_fora' => 'Sim', 'salario_valor_por_fora' => 'R$ 800,00'],
            [$this->line('Multa do art. 477 da CLT', 'multa_477')],
        );

        $this->assertSame(300000, $result[0]['valor']);
        $this->assertSame('Um salário: R$ 3.000,00 (salário + valor “por fora”)', $result[0]['memoria']);
    }

    public function test_lines_without_data_are_left_blank_with_the_reason(): void
    {
        $result = $this->calculate(['contrato_admissao' => '2024-03-01'], [
            $this->line('Pagamento das horas extras', 'horas_extras', quantity: 78, subitems: [$this->line('Reflexos em RSR', 'reflexo_rsr')]),
            $this->line('Indenização por danos morais', 'indenizacao', informed: 15000),
            $this->line('Indenização por dano existencial', 'indenizacao'),
            $this->line('Entrega das guias do seguro-desemprego', 'outro'),
            $this->line('Honorários advocatícios', 'honorarios'),
        ]);

        $this->assertNull($result[0]['valor']);
        $this->assertSame(PedidoCalculator::MISSING_SALARY, $result[0]['memoria']);
        $this->assertNull($result[0]['subitens'][0]['valor']);
        $this->assertSame(1500000, $result[1]['valor']);
        $this->assertSame('Valor indicado no texto do pedido', $result[1]['memoria']);
        $this->assertNull($result[2]['valor']);
        $this->assertStringContainsString('Informe o valor pretendido', $result[2]['memoria']);
        $this->assertNull($result[3]['valor']);
        $this->assertSame(225000, $result[4]['valor']);
    }

    public function test_hours_missing_from_the_ficha_are_not_guessed(): void
    {
        $result = $this->calculate(self::CASE, [$this->line('Pagamento das horas extras', 'horas_extras', explanation: 'A ficha não traz o horário realizado.')]);

        $this->assertNull($result[0]['valor']);
        $this->assertSame('A ficha não informa a quantidade de horas por mês. A ficha não traz o horário realizado. Informe o valor.', $result[0]['memoria']);
    }

    public function test_divisor_from_the_topics_changes_the_hour(): void
    {
        $facts = CaseFacts::fromAnswers(self::CASE, 180);

        $this->assertSame(180, $facts->divisor);
        $this->assertEqualsWithDelta(1222.22, $facts->hourly(), 0.01);
        $this->assertSame(220, CaseFacts::fromAnswers(self::CASE, 7)->divisor);
    }

    public function test_twelfths_count_fractions_of_fifteen_days_or_more(): void
    {
        $this->assertSame(10, CaseFacts::twelfths(today()->setDate(2026, 1, 1), today()->setDate(2026, 10, 21)));
        $this->assertSame(9, CaseFacts::twelfths(today()->setDate(2026, 1, 1), today()->setDate(2026, 10, 13)));
        $this->assertSame(12, CaseFacts::twelfths(today()->setDate(2025, 1, 1), today()->setDate(2026, 6, 1)));
        $this->assertSame(0, CaseFacts::twelfths(today()->setDate(2026, 5, 1), today()->setDate(2026, 4, 1)));
    }

    /**
     * @param  array<string, mixed>  $answers
     * @param  list<array<string, mixed>>  $pedidos
     * @return list<array<string, mixed>>
     */
    private function calculate(array $answers, array $pedidos): array
    {
        return (new PedidoCalculator(CaseFacts::fromAnswers($answers)))->calculate($pedidos);
    }

    /**
     * @param  list<array<string, mixed>>  $subitems
     * @return array<string, mixed>
     */
    private function line(string $description, string $type, ?float $quantity = null, ?float $percent = null, ?float $informed = null, string $explanation = '', array $subitems = []): array
    {
        return [
            'descricao' => $description,
            'tipo' => $type,
            'quantidade' => $quantity,
            'percentual' => $percent,
            'valor_informado' => $informed,
            'explicacao' => $explanation,
            'subitens' => $subitems,
        ];
    }
}
