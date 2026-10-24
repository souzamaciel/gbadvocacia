<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\VerticalJc;
use PhpOffice\PhpWord\Style\Language;

/**
 * The attendance form printed for the client's folder: one table per subject with the
 * question on the left and the answer on the right, the chosen topics and the signatures.
 */
class AppointmentFormDocxGenerator
{
    private const NAVY = '232A54';

    private const GOLD = 'C49A6C';

    private const LABEL_FILL = 'F2F3F7';

    private const HEADING_FILL = 'F6EEE4';

    private const BORDER = 'D5D8E0';

    /** Usable width in twips: A4 minus 2.2 cm on each side. */
    private const WIDTH = 9466;

    private const LABEL_WIDTH = 3600;

    protected PhpWord $word;

    /**
     * @param  list<array{title: string, items: list<array{heading: bool, label: string, value: string|list<string>|null}>}>  $answers  output of AppointmentForm::answers()
     * @param  list<string>  $preliminares  titles
     * @param  list<string>  $pedidos  titles, in order
     */
    public function generate(array $answers, array $preliminares = [], array $pedidos = [], ?string $attendant = null, ?string $clientName = null): string
    {
        $this->word = new PhpWord;
        $this->word->setDefaultFontName('Arial');
        $this->word->setDefaultFontSize(10);
        $this->word->setDefaultParagraphStyle(['spaceAfter' => 0, 'lineHeight' => 1.15]);
        $this->word->getSettings()->setThemeFontLang(new Language(Language::PT_BR));

        $section = $this->word->addSection([
            'marginTop' => Converter::cmToTwip(1.6),
            'marginBottom' => Converter::cmToTwip(1.8),
            'marginLeft' => Converter::cmToTwip(2.2),
            'marginRight' => Converter::cmToTwip(2.2),
            'footerHeight' => Converter::cmToTwip(0.8),
        ]);

        $this->addFooter($section, $clientName);
        $this->addHeading($section, $attendant, $clientName);

        foreach ($answers as $step) {
            $this->addStep($section, $step);
        }

        $this->addTitleList($section, 'Preliminares selecionadas', $preliminares);
        $this->addTitleList($section, 'Pedidos selecionados', $pedidos);
        $this->addSignature($section, $attendant);

        $path = tempnam(sys_get_temp_dir(), 'form_').'.docx';
        IOFactory::createWriter($this->word, 'Word2007')->save($path);

        return $path;
    }

    protected function addHeading(Section $section, ?string $attendant, ?string $clientName): void
    {
        $table = $section->addTable(['width' => self::WIDTH, 'unit' => 'dxa', 'cellMargin' => 60]);
        $table->addRow();
        $table->addCell(2000, ['valign' => VerticalJc::CENTER])->addImage(public_path('images/logo_peticao.png'), [
            'width' => Converter::cmToPoint(2.4),
            'height' => Converter::cmToPoint(2.67),
        ]);

        $cell = $table->addCell(self::WIDTH - 2000, ['valign' => VerticalJc::CENTER]);
        $cell->addText('FICHA DE ATENDIMENTO', ['bold' => true, 'size' => 16, 'color' => self::NAVY], ['spaceAfter' => 60]);
        $cell->addText('Grimaldo Bruno Advocacia', ['size' => 9, 'color' => '7A5A2E'], ['spaceAfter' => 160]);

        $details = [
            'Cliente' => $clientName ?: '______________________________',
            'Data' => now()->format('d/m/Y'),
            'Atendente' => $attendant ?: '______________________________',
        ];
        foreach ($details as $label => $value) {
            $line = $cell->addTextRun(['spaceAfter' => 20]);
            $line->addText($label.': ', ['bold' => true, 'size' => 9, 'color' => '555B6E']);
            $line->addText($value, ['size' => 10, 'color' => '111111']);
        }

        $section->addText('', [], ['spaceAfter' => 0, 'borderBottomSize' => 12, 'borderBottomColor' => self::GOLD]);
    }

    protected function addFooter(Section $section, ?string $clientName): void
    {
        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Ficha de atendimento'.($clientName ? ' — '.$clientName : '').'   ·   Página {PAGE} de {NUMPAGES}',
            ['size' => 8, 'color' => '888888'],
            ['alignment' => Jc::CENTER]
        );
    }

    protected function addSectionTitle(Section $section, string $title): void
    {
        $section->addText('', [], ['spaceBefore' => 200, 'keepNext' => true]);
        $table = $section->addTable(['width' => self::WIDTH, 'unit' => 'dxa', 'cellMarginTop' => 50, 'cellMarginBottom' => 50, 'cellMarginLeft' => 100]);
        $table->addRow(null, ['cantSplit' => true]);
        $table->addCell(self::WIDTH, ['bgColor' => self::NAVY])
            ->addText(mb_strtoupper($title), ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'], ['keepNext' => true]);
    }

    /**
     * @param  array{title: string, items: list<array{heading: bool, label: string, value: string|list<string>|null}>}  $step
     */
    protected function addStep(Section $section, array $step): void
    {
        $this->addSectionTitle($section, $step['title']);
        $table = $this->table($section);

        foreach ($step['items'] as $item) {
            if ($item['heading']) {
                $table->addRow(null, ['cantSplit' => true]);
                $table->addCell(self::WIDTH, ['gridSpan' => 2, 'bgColor' => self::HEADING_FILL])
                    ->addText($item['label'], ['bold' => true, 'size' => 9, 'color' => '7A5A2E'], ['keepNext' => true]);

                continue;
            }

            $table->addRow(null, ['cantSplit' => true]);
            $table->addCell(self::LABEL_WIDTH, ['bgColor' => self::LABEL_FILL])
                ->addText(rtrim(trim($item['label']), ':'), ['size' => 9, 'color' => '444A5C']);

            $this->addAnswer($table->addCell(self::WIDTH - self::LABEL_WIDTH), $item['value']);
        }
    }

    /**
     * Lists of table rows ("Empresa: X | CNPJ: Y") become one line per row; lists of
     * choices become a comma-separated answer.
     *
     * @param  string|list<string>|null  $value
     */
    protected function addAnswer(mixed $cell, string|array|null $value): void
    {
        if (! is_array($value)) {
            foreach (preg_split('/\R/u', (string) $value) as $line) {
                $cell->addText($line, ['size' => 10, 'color' => '111111']);
            }

            return;
        }

        $isTable = array_filter($value, fn (string $row): bool => str_contains($row, ' | ')) !== [];

        if (! $isTable) {
            $cell->addText(implode(', ', $value), ['size' => 10, 'color' => '111111']);

            return;
        }

        foreach ($value as $index => $row) {
            $line = $cell->addTextRun(['spaceBefore' => $index > 0 ? 80 : 0]);
            $line->addText(($index + 1).'. ', ['bold' => true, 'size' => 9, 'color' => self::NAVY]);

            foreach (explode(' | ', $row) as $partIndex => $part) {
                [$label, $text] = array_pad(explode(': ', $part, 2), 2, null);
                if ($partIndex > 0) {
                    $line->addText(' · ', ['size' => 9, 'color' => '999999']);
                }
                if ($text === null) {
                    $line->addText($label, ['size' => 10, 'color' => '111111']);

                    continue;
                }
                $line->addText($label.': ', ['size' => 9, 'color' => '555B6E']);
                $line->addText($text, ['size' => 10, 'color' => '111111']);
            }
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
        $table = $this->table($section);

        foreach (array_values($titles) as $index => $item) {
            $table->addRow(null, ['cantSplit' => true]);
            $table->addCell(600, ['bgColor' => self::LABEL_FILL])
                ->addText((string) ($index + 1), ['bold' => true, 'size' => 9, 'color' => self::NAVY], ['alignment' => Jc::CENTER]);
            $table->addCell(self::WIDTH - 600)->addText($item, ['size' => 10, 'color' => '111111']);
        }
    }

    protected function addSignature(Section $section, ?string $attendant): void
    {
        $this->addSectionTitle($section, 'Declaração e assinaturas');

        $table = $section->addTable(['width' => self::WIDTH, 'unit' => 'dxa', 'cellMargin' => 80]);
        $table->addRow(null, ['cantSplit' => true]);
        $cell = $table->addCell(self::WIDTH, ['gridSpan' => 2]);
        $cell->addText('Declaro que são verdadeiras as informações prestadas acima.', ['size' => 10, 'italic' => true, 'color' => '333333'], ['spaceBefore' => 120, 'spaceAfter' => 120, 'keepNext' => true]);
        $cell->addText('Local e data: IPATINGA/MG, '.now()->format('d/m/Y').'.', ['size' => 10, 'color' => '333333'], ['spaceAfter' => 40, 'keepNext' => true]);
        $cell->addText('Atendimento feito por: '.($attendant ?: '______________________________'), ['size' => 10, 'color' => '333333'], ['keepNext' => true]);

        $table->addRow(null, ['cantSplit' => true]);
        foreach (['Assinatura do(a) atendente', 'Assinatura do(a) cliente'] as $label) {
            $signature = $table->addCell(intdiv(self::WIDTH, 2));
            $signature->addText('______________________________________', ['size' => 10], ['alignment' => Jc::CENTER, 'spaceBefore' => 720]);
            $signature->addText($label, ['size' => 9, 'color' => '444444'], ['alignment' => Jc::CENTER]);
        }

        $section->addText(
            'É obrigatória a assinatura deste documento por ambas as partes: ATENDENTE e CLIENTE. '
            .'A assinatura do CLIENTE deve ser idêntica àquela constante no documento de identidade (RG e/ou CNH).',
            ['size' => 8, 'italic' => true, 'color' => '666666'],
            ['spaceBefore' => 240, 'alignment' => Jc::BOTH]
        );
    }

    private function table(Section $section): Table
    {
        return $section->addTable([
            'width' => self::WIDTH,
            'unit' => 'dxa',
            'borderSize' => 4,
            'borderColor' => self::BORDER,
            'cellMarginTop' => 50,
            'cellMarginBottom' => 50,
            'cellMarginLeft' => 100,
            'cellMarginRight' => 100,
        ]);
    }
}
