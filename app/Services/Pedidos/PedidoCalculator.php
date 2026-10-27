<?php

namespace App\Services\Pedidos;

use App\Enums\CalculationType;
use App\Services\MoneyInWords;

/**
 * Estimates the amount of each pedido and reflexo with fixed formulas, always the same
 * way, and writes down how each amount was reached ("memória de cálculo"). Lines without
 * enough data get no amount and are flagged for the lawyer to fill in.
 *
 * The estimates follow common practice for the initial petition (art. 840, §1º, CLT and
 * IN 41/2018 do TST, art. 12, §2º): they are not a settlement calculation.
 */
class PedidoCalculator
{
    public const MISSING_SALARY = 'Falta o último salário na ficha de atendimento.';

    public const MISSING_DATES = 'Faltam as datas de admissão e dispensa na ficha de atendimento.';

    public function __construct(
        protected CaseFacts $facts
    ) {}

    /**
     * @param  list<array{descricao: string, tipo: string, quantidade: ?float, percentual: ?float, valor_informado: ?float, explicacao: string, subitens: list<array<string, mixed>>}>  $pedidos  as classified by the AI
     * @return list<array{descricao: string, tipo: string, valor: ?int, memoria: string, subitens: list<array{descricao: string, tipo: string, valor: ?int, memoria: string}>}>
     */
    public function calculate(array $pedidos): array
    {
        $result = [];
        $feesIndexes = [];

        foreach (array_values($pedidos) as $index => $pedido) {
            $type = CalculationType::tryFrom((string) ($pedido['tipo'] ?? '')) ?? CalculationType::Outro;

            if ($type === CalculationType::Honorarios) {
                $feesIndexes[] = $index;
                $result[] = ['descricao' => (string) $pedido['descricao'], 'tipo' => $type->value, 'valor' => null, 'memoria' => '', 'subitens' => []];

                continue;
            }

            [$amount, $memory] = $this->main($type, $pedido);
            $result[] = [
                'descricao' => (string) $pedido['descricao'],
                'tipo' => $type->value,
                'valor' => $amount,
                'memoria' => $memory,
                'subitens' => $this->subitems($amount, $type, $pedido['subitens'] ?? []),
            ];
        }

        foreach ($feesIndexes as $index) {
            [$result[$index]['valor'], $result[$index]['memoria']] = $this->fees($result, $pedidos[$index]);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array{0: ?int, 1: string}
     */
    protected function main(CalculationType $type, array $line): array
    {
        $quantity = $this->number($line['quantidade'] ?? null);
        $percent = $this->number($line['percentual'] ?? null);
        $informed = $this->number($line['valor_informado'] ?? null);
        $explanation = trim((string) ($line['explicacao'] ?? ''));

        if (in_array($type, [CalculationType::Indenizacao, CalculationType::Outro], true)) {
            return $this->fixed($type, $quantity, $informed, $explanation);
        }

        if ($type === CalculationType::AdicionalInsalubridade) {
            return $this->unhealthy($percent ?? 20.0);
        }

        if ($this->facts->remuneration() === null) {
            return [null, self::MISSING_SALARY];
        }

        if ($this->facts->admission === null || $this->facts->dismissal === null) {
            return [null, self::MISSING_DATES];
        }

        $pay = $this->facts->remuneration();
        $hourly = $this->facts->hourly();
        $months = (float) $this->facts->claimableMonths();
        $salary = $this->facts->remunerationText();
        $period = $this->period($months);
        $hourText = $this->money((int) round($hourly)).' ('.$salary.' ÷ '.$this->facts->divisor.')';

        return match ($type) {
            CalculationType::HorasExtras => $this->hourBased(
                $quantity,
                1 + ($percent ?? 50) / 100,
                "{$this->decimal($quantity)} h extras/mês × hora de {$hourText} × ".$this->decimal(1 + ($percent ?? 50) / 100)." (adicional de {$this->decimal($percent ?? 50)}%) × {$period}",
                $months,
                $explanation,
            ),
            CalculationType::IntervaloIntrajornada => $this->hourBased($quantity, 1.5, "{$this->decimal($quantity)} h suprimidas/mês × hora de {$hourText} × 1,5 × {$period}", $months, $explanation),
            CalculationType::IntervaloInterjornada => $this->hourBased($quantity, 1.5, "{$this->decimal($quantity)} h suprimidas/mês × hora de {$hourText} × 1,5 × {$period}", $months, $explanation),
            CalculationType::AdicionalNoturno => $this->hourBased(
                $quantity,
                ($percent ?? 20) / 100,
                "{$this->decimal($quantity)} h noturnas/mês × hora de {$hourText} × {$this->decimal($percent ?? 20)}% × {$period}",
                $months,
                $explanation,
            ),
            CalculationType::DomingosFeriados => $quantity === null
                ? [null, $this->missing('quantos domingos e feriados por mês foram trabalhados', $explanation)]
                : $this->result($quantity * $pay / 30 * 2 * $months, "{$this->decimal($quantity)} dias/mês × dia de ".$this->money((int) round($pay / 30))." × 2 (em dobro) × {$period}", $explanation),
            CalculationType::AdicionalPericulosidade => $this->result($pay * 0.3 * $months, "30% × {$salary} × {$period}", $explanation),
            CalculationType::DiferencaSalarial => $this->salaryDifference($percent, $informed, $months, $salary, $explanation),
            CalculationType::SaldoSalario => $this->balance(),
            CalculationType::AvisoPrevio => $this->notice(),
            CalculationType::DecimoTerceiro => $quantity !== null && $quantity >= 1
                ? $this->result($pay * $quantity, "{$salary} × {$this->decimal($quantity)} ano(s)", $explanation)
                : $this->thirteenth(),
            CalculationType::FeriasProporcionais => $this->vacationProportional(),
            CalculationType::FeriasVencidas => $this->result(
                $pay * 4 / 3 * ($quantity ?? 1) * (($percent ?? 100) >= 200 ? 2 : 1),
                "{$salary} + 1/3 × ".$this->decimal($quantity ?? 1).' período(s)'.(($percent ?? 100) >= 200 ? ' × 2 (em dobro)' : ''),
                $explanation,
            ),
            CalculationType::Fgts => $this->result($pay * 0.08 * $months, "8% × {$salary} × {$period}", $explanation),
            CalculationType::MultaFgts => $this->result(
                $pay * 0.08 * (float) $this->facts->contractMonths() * 0.4,
                "40% × (8% × {$salary} × ".$this->decimal((float) $this->facts->contractMonths()).' meses de contrato)',
                $explanation,
            ),
            CalculationType::Multa477 => $this->result($pay, "Um salário: {$salary}", $explanation),
            CalculationType::Multa467 => $this->penalty467(),
            CalculationType::SeguroDesemprego => $this->unemployment($quantity),
            default => [null, 'Tipo de pedido sem fórmula. Informe o valor.'],
        };
    }

    /**
     * Reflexos follow the main amount: RSR at 1/6, then 13º, vacation + 1/3 and notice on
     * the amount plus RSR, and FGTS on all of them except vacation.
     *
     * @param  list<array<string, mixed>>  $subitems
     * @return list<array{descricao: string, tipo: string, valor: ?int, memoria: string}>
     */
    protected function subitems(?int $mainAmount, CalculationType $mainType, array $subitems): array
    {
        $lines = [];
        $types = [];

        foreach (array_values($subitems) as $index => $subitem) {
            $types[$index] = CalculationType::tryFrom((string) ($subitem['tipo'] ?? '')) ?? CalculationType::Outro;
        }

        $rsr = null;
        foreach ($types as $index => $type) {
            if ($type === CalculationType::ReflexoRsr && $mainAmount !== null) {
                $rsr = (int) round($mainAmount / 6);
            }
        }

        $base = $mainAmount === null ? null : $mainAmount + ($rsr ?? 0);
        $baseText = $rsr === null ? 'valor principal' : 'principal + RSR';
        $months = (float) $this->facts->claimableMonths();
        $computed = [];

        foreach ($types as $index => $type) {
            $line = $subitems[$index];
            $description = (string) ($line['descricao'] ?? '');

            if (! $type->isReflexo()) {
                [$amount, $memory] = $this->main($type, $line);
                $lines[$index] = ['descricao' => $description, 'tipo' => $type->value, 'valor' => $amount, 'memoria' => $memory];

                continue;
            }

            if ($base === null) {
                $lines[$index] = ['descricao' => $description, 'tipo' => $type->value, 'valor' => null, 'memoria' => 'Sem o valor da parcela principal, o reflexo não pode ser estimado.'];

                continue;
            }

            [$amount, $memory] = match ($type) {
                CalculationType::ReflexoRsr => [$rsr, '1/6 de '.$this->money($mainAmount).' (média de 1 descanso para 6 dias de trabalho)'],
                CalculationType::ReflexoDecimoTerceiro => [(int) round($base / 12), "1/12 de {$this->money($base)} ({$baseText})"],
                CalculationType::ReflexoFerias => [(int) round($base / 12 * 4 / 3), "1/12 de {$this->money($base)} ({$baseText}) + 1/3"],
                CalculationType::ReflexoAvisoPrevio => $months > 0 && $this->facts->noticeDays() !== null
                    ? [(int) round($base / $months * $this->facts->noticeDays() / 30), "média mensal de {$this->money((int) round($base / $months))} ({$baseText}) × {$this->facts->noticeDays()} dias de aviso ÷ 30"]
                    : [null, self::MISSING_DATES],
                default => [null, ''],
            };

            if ($amount !== null && in_array($type, [CalculationType::ReflexoDecimoTerceiro, CalculationType::ReflexoAvisoPrevio], true)) {
                $computed[] = $amount;
            }

            $lines[$index] = ['descricao' => $description, 'tipo' => $type->value, 'valor' => $amount, 'memoria' => $memory];
        }

        foreach ($types as $index => $type) {
            if (! in_array($type, [CalculationType::ReflexoFgts, CalculationType::ReflexoFgtsMulta], true) || $base === null) {
                continue;
            }

            $fgtsBase = $base + array_sum($computed);
            $rate = $type === CalculationType::ReflexoFgts ? 0.08 : 0.112;
            $lines[$index]['valor'] = (int) round($fgtsBase * $rate);
            $lines[$index]['memoria'] = ($type === CalculationType::ReflexoFgts ? '8%' : '11,2% (8% + 40%)')
                ." × {$this->money($fgtsBase)} ({$baseText}".($computed !== [] ? ' + reflexos em 13º e aviso' : '').')';
        }

        ksort($lines);

        return array_values($lines);
    }

    /**
     * @param  list<array<string, mixed>>  $result
     * @param  array<string, mixed>  $line
     * @return array{0: ?int, 1: string}
     */
    protected function fees(array $result, array $line): array
    {
        $percent = $this->number($line['percentual'] ?? null) ?? 15.0;
        $total = 0;

        foreach ($result as $pedido) {
            if ($pedido['tipo'] === CalculationType::Honorarios->value) {
                continue;
            }

            $total += (int) $pedido['valor'];
            foreach ($pedido['subitens'] as $subitem) {
                $total += (int) $subitem['valor'];
            }
        }

        if ($total === 0) {
            return [null, 'Sem valores nos demais pedidos para calcular os honorários.'];
        }

        return [(int) round($total * $percent / 100), "{$this->decimal($percent)}% × {$this->money($total)} (soma dos demais pedidos)"];
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function hourBased(?float $hours, float $factor, string $memory, float $months, string $explanation): array
    {
        if ($hours === null || $hours <= 0) {
            return [null, $this->missing('a quantidade de horas por mês', $explanation)];
        }

        return $this->result($hours * $this->facts->hourly() * $factor * $months, $memory, $explanation);
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function fixed(CalculationType $type, ?float $quantity, ?float $informed, string $explanation): array
    {
        if ($informed !== null && $informed > 0) {
            return $this->result($informed * 100, 'Valor indicado no texto do pedido', $explanation);
        }

        if ($type === CalculationType::Indenizacao && $quantity !== null && $quantity > 0) {
            return $this->facts->remuneration() === null
                ? [null, self::MISSING_SALARY]
                : $this->result($quantity * $this->facts->remuneration(), "{$this->decimal($quantity)} × {$this->facts->remunerationText()}", $explanation);
        }

        return [null, $type === CalculationType::Indenizacao
            ? 'O texto do pedido não indica o valor da indenização. Informe o valor pretendido.'
            : 'Pedido sem fórmula de cálculo. Informe o valor.'];
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function unhealthy(float $percent): array
    {
        $months = $this->facts->claimableMonths();

        if ($months === null) {
            return [null, self::MISSING_DATES];
        }

        $minimum = (int) round((float) config('calculos.salario_minimo') * 100);

        return $this->result(
            $minimum * $percent / 100 * $months,
            "{$this->decimal($percent)}% × salário mínimo de {$this->money($minimum)} × {$this->period($months)}",
            '',
        );
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function salaryDifference(?float $percent, ?float $informed, float $months, string $salary, string $explanation): array
    {
        if ($informed !== null && $informed > 0) {
            return $this->result($informed * 100 * $months, $this->money((int) round($informed * 100))." de diferença/mês × {$this->period($months)}", $explanation);
        }

        if ($percent !== null && $percent > 0) {
            return $this->result($this->facts->remuneration() * $percent / 100 * $months, "{$this->decimal($percent)}% × {$salary} × {$this->period($months)}", $explanation);
        }

        return [null, $this->missing('a diferença mensal ou o percentual pretendido', $explanation)];
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function balance(): array
    {
        if ($this->facts->dismissalIsToday) {
            return [null, 'Falta a data de dispensa na ficha de atendimento.'];
        }

        $days = $this->facts->dismissal->day;

        return $this->result($this->facts->remuneration() / 30 * $days, "{$days} dias × {$this->facts->remunerationText()} ÷ 30", '');
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function notice(): array
    {
        $days = (int) $this->facts->noticeDays();

        return $this->result($this->facts->remuneration() / 30 * $days, "{$days} dias (30 + 3 por ano completo) × {$this->facts->remunerationText()} ÷ 30", '');
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function thirteenth(): array
    {
        $end = $this->facts->projectedEnd();
        $start = $end->copy()->startOfYear();
        $start = $this->facts->admission->greaterThan($start) ? $this->facts->admission->copy() : $start;
        $twelfths = CaseFacts::twelfths($start, $end);

        return $this->result(
            $this->facts->remuneration() / 12 * $twelfths,
            "{$twelfths}/12 × {$this->facts->remunerationText()} ({$start->format('d/m/Y')} a {$end->format('d/m/Y')}".($end->notEqualTo($this->facts->dismissal) ? ', com a projeção do aviso' : '').')',
            '',
        );
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function vacationProportional(): array
    {
        $end = $this->facts->projectedEnd();
        $start = $this->facts->admission->copy();

        while ($start->copy()->addYear()->lessThanOrEqualTo($end)) {
            $start->addYear();
        }

        $twelfths = CaseFacts::twelfths($start, $end);

        return $this->result(
            $this->facts->remuneration() / 12 * $twelfths * 4 / 3,
            "{$twelfths}/12 × {$this->facts->remunerationText()} + 1/3 (período aquisitivo desde {$start->format('d/m/Y')})",
            '',
        );
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function penalty467(): array
    {
        $severance = (int) $this->balance()[0] + (int) $this->notice()[0] + (int) $this->thirteenth()[0] + (int) $this->vacationProportional()[0];

        return $this->result($severance / 2, "50% × {$this->money($severance)} (saldo de salário + aviso + 13º + férias proporcionais)", '');
    }

    /**
     * @return array{0: ?int, 1: string}
     */
    protected function unemployment(?float $installments): array
    {
        $months = (float) $this->facts->contractMonths();
        $installments ??= $months >= 24 ? 5.0 : ($months >= 12 ? 4.0 : null);

        if ($installments === null) {
            return [null, 'Contrato com menos de 12 meses: confira se o cliente tem direito e quantas parcelas.'];
        }

        $cents = fn (string $key): int => (int) round((float) config("calculos.{$key}") * 100);
        $pay = $this->facts->remuneration();
        $firstBracket = $cents('seguro_desemprego.faixa_1');
        $secondBracket = $cents('seguro_desemprego.faixa_2');
        $ceiling = $cents('seguro_desemprego.teto');

        [$installment, $rule] = match (true) {
            $pay <= $firstBracket => [(int) round($pay * 0.8), '80% do salário'],
            $pay <= $secondBracket => [
                $cents('seguro_desemprego.valor_faixa_2') + (int) round(($pay - $firstBracket) * 0.5),
                "{$this->money($cents('seguro_desemprego.valor_faixa_2'))} + 50% do que passa de {$this->money($firstBracket)}",
            ],
            default => [$ceiling, 'teto da tabela'],
        };

        if ($installment < $cents('salario_minimo')) {
            [$installment, $rule] = [$cents('salario_minimo'), 'salário mínimo, piso do benefício'];
        }

        return $this->result(
            $installment * $installments,
            "{$this->decimal($installments)} ".($installments == 1 ? 'parcela' : 'parcelas')." × {$this->money($installment)} ({$rule}, tabela do seguro-desemprego)",
            '',
        );
    }

    /**
     * @return array{0: int, 1: string}
     */
    protected function result(float $cents, string $memory, string $explanation): array
    {
        return [(int) round($cents), $explanation !== '' ? "{$memory}. {$explanation}" : $memory];
    }

    protected function missing(string $what, string $explanation): string
    {
        return "A ficha não informa {$what}. ".($explanation !== '' ? $explanation.' ' : '').'Informe o valor.';
    }

    protected function period(float $months): string
    {
        $start = $this->facts->claimableStart();
        $prescribed = $start !== null && $this->facts->admission !== null && $start->greaterThan($this->facts->admission);

        return $this->decimal($months).' meses'.($prescribed ? ' (últimos 5 anos)' : '');
    }

    protected function money(int $cents): string
    {
        return MoneyInWords::formatCents($cents);
    }

    protected function decimal(?float $value): string
    {
        $value = (float) $value;

        return floor($value) === $value ? number_format($value, 0, ',', '.') : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    protected function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
