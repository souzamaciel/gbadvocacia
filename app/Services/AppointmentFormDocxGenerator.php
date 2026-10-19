<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

class AppointmentFormDocxGenerator
{
    protected PhpWord $word;

    /**
     * @param  list<array{title: string, items: list<array{heading: bool, label: string, value: string|list<string>|null}>}>  $answers  output of AppointmentForm::answers()
     * @param  list<string>  $preliminares  titles
     * @param  list<string>  $pedidos  titles, in order
     */
    public function generate(array $answers, array $preliminares = [], array $pedidos = [], ?string $attendant = null): string
    {
        $this->word = new PhpWord;
        $this->word->setDefaultFontName('Arial');
        $this->word->setDefaultFontSize(10);
        $this->word->setDefaultParagraphStyle([
            'spaceAfter' => Converter::pointToTwip(4),
            'lineHeight' => 1.3,
        ]);

        $section = $this->word->addSection([
            'marginTop' => Converter::cmToTwip(2.5),
            'marginBottom' => Converter::cmToTwip(2),
            'marginLeft' => Converter::cmToTwip(2.5),
            'marginRight' => Converter::cmToTwip(2),
        ]);

        $header = $section->addHeader();
        $header->addImage(
            public_path('images/logo_marca_dagua.png'),
            [
                'width' => Converter::cmToPoint(3.5),
                'height' => Converter::cmToPoint(2.8),
                'alignment' => Jc::CENTER,
            ]
        );
        $header->addTextBreak(1, ['size' => 4]);

        $section->addText(
            'FICHA DE ATENDIMENTO',
            ['bold' => true, 'size' => 14, 'color' => '232A54'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => Converter::pointToTwip(4)]
        );
        $section->addText(
            'Data: '.now()->format('d/m/Y'),
            ['size' => 9, 'color' => '666666'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => Converter::pointToTwip(16)]
        );

        foreach ($answers as $index => $step) {
            $this->addStep($section, $index + 1, $step);
        }

        $this->addTitleList($section, 'PRELIMINARES SELECIONADAS', $preliminares);
        $this->addTitleList($section, 'PEDIDOS SELECIONADOS', $pedidos);
        $this->addSignature($section, $attendant);

        $path = tempnam(sys_get_temp_dir(), 'form_').'.docx';
        $writer = IOFactory::createWriter($this->word, 'Word2007');
        $writer->save($path);

        return $path;
    }

    protected function addSectionTitle(Section $section, string $title): void
    {
        $section->addText(
            mb_strtoupper($title),
            ['bold' => true, 'size' => 10, 'color' => '232A54'],
            [
                'spaceBefore' => Converter::pointToTwip(14),
                'spaceAfter' => Converter::pointToTwip(2),
                'borderBottomSize' => 6,
                'borderBottomColor' => 'C49A6C',
            ]
        );
    }

    /**
     * @param  array{title: string, items: list<array{heading: bool, label: string, value: string|list<string>|null}>}  $step
     */
    protected function addStep(Section $section, int $number, array $step): void
    {
        $this->addSectionTitle($section, $number.'. '.$step['title']);

        foreach ($step['items'] as $item) {
            if ($item['heading']) {
                $section->addText(
                    $item['label'],
                    ['bold' => true, 'size' => 9, 'color' => '7A5A2E'],
                    ['spaceBefore' => Converter::pointToTwip(6), 'spaceAfter' => Converter::pointToTwip(2)]
                );

                continue;
            }

            $value = is_array($item['value']) ? implode('; ', $item['value']) : (string) $item['value'];

            $textRun = $section->addTextRun([
                'spaceAfter' => Converter::pointToTwip(3),
                'indentation' => ['left' => Converter::cmToTwip(0.3)],
            ]);
            $textRun->addText($item['label'].': ', ['bold' => true, 'size' => 9, 'color' => '444444']);
            $textRun->addText($value, ['size' => 9, 'color' => '111111']);
        }
    }

    /**
     * @param  list<string>  $titles
     */
    protected function addTitleList(Section $section, string $title, array $titles): void
    {
        if ($titles === []) {
            return;
        }

        $this->addSectionTitle($section, $title);

        foreach (array_values($titles) as $i => $item) {
            $section->addText(
                ($i + 1).'. '.$item,
                ['size' => 9, 'color' => '111111'],
                ['indentation' => ['left' => Converter::cmToTwip(0.3)], 'spaceAfter' => Converter::pointToTwip(2)]
            );
        }
    }

    protected function addSignature(Section $section, ?string $attendant): void
    {
        $section->addTextBreak(1);

        $section->addText(
            'Declaro que são verdadeiras as informações prestadas acima.',
            ['size' => 9, 'italic' => true, 'color' => '444444'],
            ['spaceAfter' => Converter::pointToTwip(6)]
        );
        $section->addText(
            'Local e data: IPATINGA/MG, '.now()->format('d/m/Y').'.',
            ['size' => 9, 'color' => '444444'],
            ['spaceAfter' => Converter::pointToTwip(2)]
        );
        $section->addText(
            'Atendimento feito por: '.($attendant ?: '______________________________'),
            ['size' => 9, 'color' => '444444'],
            ['spaceAfter' => Converter::pointToTwip(18)]
        );

        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => 'pct',
            'alignment' => Jc::CENTER,
        ]);

        $table->addRow();

        foreach (['Assinatura do(a) atendente', 'Assinatura do(a) cliente'] as $label) {
            $cell = $table->addCell(5000);
            $cell->addText(
                '______________________________',
                ['size' => 10],
                ['alignment' => Jc::CENTER, 'spaceBefore' => Converter::pointToTwip(24)]
            );
            $cell->addText($label, ['size' => 9, 'color' => '444444'], ['alignment' => Jc::CENTER]);
        }

        $section->addText(
            'É obrigatória a assinatura deste documento por ambas as partes: ATENDENTE e CLIENTE. '
            .'A assinatura do CLIENTE deve ser idêntica àquela constante no documento de identidade (RG e/ou CNH).',
            ['size' => 8, 'italic' => true, 'color' => '666666'],
            ['spaceBefore' => Converter::pointToTwip(12)]
        );
    }
}
