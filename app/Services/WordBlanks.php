<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\TextRun;

/**
 * Blanks the lawyer fills in after generating the petition ("[CPF]", "{data de admissão}"):
 * written with a light-green highlight so they are easy to find in Word.
 */
class WordBlanks
{
    public const FILL = 'C6EFCE';

    private const PATTERN = '/(\[[^\[\]]+\]|\{[^{}]+\})/u';

    /**
     * @param  array<string, mixed>  $font
     */
    public static function add(TextRun $textRun, string $text, array $font = []): void
    {
        foreach (preg_split(self::PATTERN, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            $textRun->addText($part, preg_match(self::PATTERN, $part) === 1 ? [...$font, 'bgColor' => self::FILL] : $font);
        }
    }
}
