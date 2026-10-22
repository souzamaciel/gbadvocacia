<?php

namespace App\Services;

/**
 * Brazilian currency helpers for the petition amounts ("R$ 1.234,56" and its spelling).
 */
class MoneyInWords
{
    private const UNITS = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove', 'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove'];

    private const TENS = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];

    private const HUNDREDS = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

    /**
     * Cents of an amount written as "R$ 1.234,56" (or "1234,56"); null when there is no amount.
     */
    public static function parseCents(?string $amount): ?int
    {
        if (! preg_match('/(\d{1,3}(?:\.\d{3})+|\d+)(?:,(\d{1,2}))?/', (string) $amount, $match)) {
            return null;
        }

        return (int) str_replace('.', '', $match[1]) * 100 + (int) str_pad($match[2] ?? '0', 2, '0');
    }

    public static function formatCents(int $cents): string
    {
        return 'R$ '.number_format(intdiv($cents, 100), 0, ',', '.').','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * "cento e doze mil, setecentos e cinquenta e quatro reais e setenta e seis centavos"
     */
    public static function spell(int $cents): string
    {
        $reais = intdiv($cents, 100);
        $centavos = $cents % 100;
        $parts = [];

        if ($reais > 0) {
            $parts[] = self::spellInteger($reais).(($reais % 1000000 === 0) ? ' de' : '').($reais === 1 ? ' real' : ' reais');
        }

        if ($centavos > 0) {
            $parts[] = self::spellBelowThousand($centavos).($centavos === 1 ? ' centavo' : ' centavos');
        }

        return $parts === [] ? 'zero real' : implode(' e ', $parts);
    }

    private static function spellInteger(int $number): string
    {
        $groups = [];
        $scales = [['', ''], ['mil', 'mil'], ['milhão', 'milhões'], ['bilhão', 'bilhões']];

        foreach ($scales as [$singular, $plural]) {
            $group = $number % 1000;
            $number = intdiv($number, 1000);

            if ($group > 0) {
                $words = $singular === 'mil' && $group === 1 ? 'mil' : trim(self::spellBelowThousand($group).' '.($group === 1 ? $singular : $plural));
                array_unshift($groups, ['words' => $words, 'value' => $group]);
            }

            if ($number === 0) {
                break;
            }
        }

        $text = '';
        foreach ($groups as $index => $group) {
            if ($index > 0) {
                $text .= ($index === count($groups) - 1 && ($group['value'] < 100 || $group['value'] % 100 === 0)) ? ' e ' : ', ';
            }
            $text .= $group['words'];
        }

        return $text;
    }

    private static function spellBelowThousand(int $number): string
    {
        if ($number === 100) {
            return 'cem';
        }

        $words = [];
        if ($number >= 100) {
            $words[] = self::HUNDREDS[intdiv($number, 100)];
            $number %= 100;
        }
        if ($number >= 20) {
            $words[] = self::TENS[intdiv($number, 10)];
            $number %= 10;
        }
        if ($number > 0) {
            $words[] = self::UNITS[$number];
        }

        return implode(' e ', $words);
    }
}
