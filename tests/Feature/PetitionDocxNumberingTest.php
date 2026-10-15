<?php

namespace Tests\Feature;

use App\Services\PetitionDocxGenerator;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use ZipArchive;

class PetitionDocxNumberingTest extends TestCase
{
    public function test_merit_headings_are_numbered_sequentially_by_the_system(): void
    {
        $paragraphs = $this->generateMeritParagraphs([
            'topicos' => [
                ['ordem' => '1', 'titulo' => 'HORAS EXTRAS ALÉM DA 08ª DIÁRIA', 'conteudo_html' => '<p>A.</p>', 'subtopicos' => []],
                ['ordem' => '7', 'titulo' => 'DA SUPRESSÃO DO DSR', 'conteudo_html' => '<p>B.</p>', 'subtopicos' => []],
            ],
            'incluir_honorarios' => true,
            'honorarios_ordem' => '1',
            'honorarios_html' => '<p>Honorários.</p>',
            'pedidos_ordem' => '1',
        ]);

        $this->assertSame([
            '01 – DO CONTRATO DE TRABALHO.',
            '02 – HORAS EXTRAS ALÉM DA 08ª DIÁRIA.',
            '03 – DA SUPRESSÃO DO DSR.',
            '04 – DOS HONORÁRIOS ADVOCATÍCIOS.',
            '05 – DOS PEDIDOS.',
        ], $this->numberedHeadings($paragraphs));
    }

    public function test_numbers_already_present_in_ai_titles_are_replaced(): void
    {
        $paragraphs = $this->generateMeritParagraphs([
            'topicos' => [
                ['ordem' => '', 'titulo' => '1 – HORAS EXTRAS.', 'conteudo_html' => '', 'subtopicos' => []],
                ['ordem' => '', 'titulo' => '02 - DA SUPRESSÃO DO DSR', 'conteudo_html' => '', 'subtopicos' => []],
            ],
        ]);

        $this->assertContains('02 – HORAS EXTRAS.', $paragraphs);
        $this->assertContains('03 – DA SUPRESSÃO DO DSR.', $paragraphs);
    }

    public function test_subtopics_use_their_parent_number(): void
    {
        $paragraphs = $this->generateMeritParagraphs([
            'topicos' => [
                ['ordem' => '9', 'titulo' => 'DAS HORAS EXTRAS', 'conteudo_html' => '', 'subtopicos' => [
                    ['ordem' => 'x', 'titulo' => 'DO INTERVALO INTRAJORNADA', 'conteudo_html' => ''],
                    ['ordem' => 'y', 'titulo' => 'DO LABOR AOS FERIADOS', 'conteudo_html' => ''],
                ]],
            ],
        ]);

        $this->assertContains('2.1 – DO INTERVALO INTRAJORNADA', $paragraphs);
        $this->assertContains('2.2 – DO LABOR AOS FERIADOS', $paragraphs);
    }

    public function test_preliminares_have_no_roman_numerals(): void
    {
        $paragraphs = $this->generateMeritParagraphs([
            'preliminares' => [
                ['titulo' => 'JUÍZO 100% DIGITAL', 'conteudo_html' => '<p>Texto.</p>'],
                ['titulo' => 'Da Gratuidade de Justiça', 'conteudo_html' => '<p>Texto.</p>'],
            ],
        ]);

        $this->assertContains('JUÍZO 100% DIGITAL', $paragraphs);
        $this->assertContains('DA GRATUIDADE DE JUSTIÇA', $paragraphs);
        $this->assertEmpty(array_filter($paragraphs, fn (string $text): bool => preg_match('/^[IVX]+ - /', $text) === 1));
    }

    public function test_closing_uses_office_city_and_long_date(): void
    {
        Carbon::setTestNow('2026-03-14 10:00:00');

        $paragraphs = $this->generateMeritParagraphs(['cliente_cidade' => 'Belo Horizonte']);

        $this->assertContains('Ipatinga, 14 de março de 2026', $paragraphs);
        $this->assertContains('Nesses termos,', $paragraphs);
        $this->assertContains('Pede deferimento.', $paragraphs);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function generateMeritParagraphs(array $data): array
    {
        $path = (new PetitionDocxGenerator)->generateMerit($data + ['contrato_trabalho_html' => '<p>Contrato.</p>']);

        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        preg_match_all('/<w:p[ >].*?<\/w:p>/s', $xml, $matches);

        return array_values(array_filter(array_map(
            fn (string $paragraph): string => trim(html_entity_decode(strip_tags($paragraph))),
            $matches[0],
        )));
    }

    /**
     * @param  array<int, string>  $paragraphs
     * @return array<int, string>
     */
    private function numberedHeadings(array $paragraphs): array
    {
        return array_values(array_filter($paragraphs, fn (string $text): bool => preg_match('/^\d{2} – /u', $text) === 1));
    }
}
