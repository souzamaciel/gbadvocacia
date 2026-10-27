<?php

namespace App\Enums;

/**
 * How the amount of a pedido (or of one of its reflexos) is estimated. The AI only
 * classifies each line and extracts quantities from the case; the math is done by
 * App\Services\Pedidos\PedidoCalculator.
 */
enum CalculationType: string
{
    case HorasExtras = 'horas_extras';
    case IntervaloIntrajornada = 'intervalo_intrajornada';
    case IntervaloInterjornada = 'intervalo_interjornada';
    case DomingosFeriados = 'domingos_feriados';
    case AdicionalNoturno = 'adicional_noturno';
    case AdicionalInsalubridade = 'adicional_insalubridade';
    case AdicionalPericulosidade = 'adicional_periculosidade';
    case DiferencaSalarial = 'diferenca_salarial';
    case SaldoSalario = 'saldo_salario';
    case AvisoPrevio = 'aviso_previo';
    case DecimoTerceiro = 'decimo_terceiro';
    case FeriasProporcionais = 'ferias_proporcionais';
    case FeriasVencidas = 'ferias_vencidas';
    case Fgts = 'fgts';
    case MultaFgts = 'multa_fgts';
    case Multa477 = 'multa_477';
    case Multa467 = 'multa_467';
    case SeguroDesemprego = 'seguro_desemprego';
    case Indenizacao = 'indenizacao';
    case Honorarios = 'honorarios';
    case ReflexoRsr = 'reflexo_rsr';
    case ReflexoDecimoTerceiro = 'reflexo_decimo_terceiro';
    case ReflexoFerias = 'reflexo_ferias';
    case ReflexoAvisoPrevio = 'reflexo_aviso_previo';
    case ReflexoFgts = 'reflexo_fgts';
    case ReflexoFgtsMulta = 'reflexo_fgts_multa';
    case Outro = 'outro';

    /**
     * What the AI must fill in "quantidade", "percentual" and "valor_informado" for this type.
     */
    public function instruction(): string
    {
        return match ($this) {
            self::HorasExtras => 'Horas extras. "quantidade" = média de horas extras por mês (ex.: 3 h/dia × 26 dias = 78); "percentual" = adicional (50 se não informado).',
            self::IntervaloIntrajornada => 'Intervalo para refeição não concedido ou reduzido. "quantidade" = horas suprimidas por mês (ex.: 30 min/dia × 22 dias = 11).',
            self::IntervaloInterjornada => 'Intervalo de 11 h entre jornadas desrespeitado. "quantidade" = horas suprimidas por mês.',
            self::DomingosFeriados => 'Domingos e feriados trabalhados sem folga compensatória. "quantidade" = dias por mês.',
            self::AdicionalNoturno => 'Adicional noturno. "quantidade" = horas noturnas por mês; "percentual" = adicional (20 se não informado).',
            self::AdicionalInsalubridade => 'Adicional de insalubridade sobre o salário mínimo. "percentual" = grau (10, 20 ou 40; 20 se não informado).',
            self::AdicionalPericulosidade => 'Adicional de periculosidade de 30% sobre o salário.',
            self::DiferencaSalarial => 'Diferenças salariais (equiparação, desvio ou acúmulo de função, piso). "valor_informado" = diferença mensal em reais OU "percentual" = acréscimo sobre o salário (ex.: 30 no acúmulo de função).',
            self::SaldoSalario => 'Saldo de salário do mês da dispensa.',
            self::AvisoPrevio => 'Aviso prévio indenizado (30 dias + 3 por ano completo).',
            self::DecimoTerceiro => '13º salário. Proporcional do último ano, ou "quantidade" = anos integrais não pagos.',
            self::FeriasProporcionais => 'Férias proporcionais + 1/3.',
            self::FeriasVencidas => 'Férias vencidas + 1/3. "quantidade" = períodos vencidos; "percentual" = 200 se pedidas em dobro.',
            self::Fgts => 'Depósitos de FGTS não realizados no contrato.',
            self::MultaFgts => 'Multa de 40% do FGTS sobre todo o contrato.',
            self::Multa477 => 'Multa do art. 477 da CLT (um salário).',
            self::Multa467 => 'Multa do art. 467 da CLT (50% das verbas rescisórias).',
            self::SeguroDesemprego => 'Indenização substitutiva do seguro-desemprego. "quantidade" = parcelas, se a ficha informar.',
            self::Indenizacao => 'Indenização de valor fixo (dano moral, existencial etc.). "valor_informado" = valor pedido no texto do tópico, OU "quantidade" = número de salários pedido. Se o tópico não indicar valor nem quantidade de salários, deixe ambos nulos.',
            self::Honorarios => 'Honorários advocatícios. "percentual" = percentual pedido (15 se não informado).',
            self::ReflexoRsr => 'Reflexo da parcela principal em descansos semanais remunerados (RSR/DSR).',
            self::ReflexoDecimoTerceiro => 'Reflexo da parcela principal em 13º salários.',
            self::ReflexoFerias => 'Reflexo da parcela principal em férias + 1/3.',
            self::ReflexoAvisoPrevio => 'Reflexo da parcela principal em aviso prévio.',
            self::ReflexoFgts => 'Reflexo da parcela principal em FGTS (8%), sem a multa de 40%.',
            self::ReflexoFgtsMulta => 'Reflexo da parcela principal em FGTS + 40%.',
            self::Outro => 'Qualquer outro pedido ou parcela. "valor_informado" só se o texto do tópico indicar o valor; senão, nulo.',
        };
    }

    public function isReflexo(): bool
    {
        return str_starts_with($this->value, 'reflexo_');
    }
}
