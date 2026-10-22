<?php

namespace Tests\Unit;

use App\Services\MoneyInWords;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyInWordsTest extends TestCase
{
    /**
     * @return array<string, array{int, string}>
     */
    public static function spellings(): array
    {
        return [
            'model petition' => [11275476, 'cento e doze mil, setecentos e cinquenta e quatro reais e setenta e seis centavos'],
            'one real' => [100, 'um real'],
            'only cents' => [45, 'quarenta e cinco centavos'],
            'hundred' => [10000, 'cem reais'],
            'thousand and hundred' => [110000, 'mil e cem reais'],
            'thousand and units' => [100500, 'mil e cinco reais'],
            'round million' => [100000000, 'um milhão de reais'],
            'millions' => [250000000, 'dois milhões e quinhentos mil reais'],
            'zero' => [0, 'zero real'],
        ];
    }

    #[DataProvider('spellings')]
    public function test_spells_amounts(int $cents, string $expected): void
    {
        $this->assertSame($expected, MoneyInWords::spell($cents));
    }

    public function test_parses_and_formats_amounts(): void
    {
        $this->assertSame(959040, MoneyInWords::parseCents('R$ 9.590,40'));
        $this->assertSame(600000, MoneyInWords::parseCents('6000'));
        $this->assertSame(50, MoneyInWords::parseCents('R$ 0,5'));
        $this->assertNull(MoneyInWords::parseCents(''));
        $this->assertNull(MoneyInWords::parseCents('R$ ________'));
        $this->assertSame('R$ 112.754,76', MoneyInWords::formatCents(11275476));
    }
}
