<?php

namespace Tests\Feature;

use App\Services\PetitionDocxGenerator;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;
use ZipArchive;

class PetitionDocxPedidosTest extends TestCase
{
    public function test_pedidos_follow_the_office_layout_with_blank_amounts(): void
    {
        Date::setTestNow('2026-10-22 10:00:00');

        $paragraphs = $this->pedidoParagraphs([
            ['descricao' => 'Pagamento das horas extras além da 08ª diária', 'subitens' => [['descricao' => 'Reflexos em 13º salários'], ['descricao' => 'Reflexos em RSR']]],
            ['descricao' => 'Indenização substitutiva do seguro-desemprego.', 'subitens' => []],
        ]);

        $this->assertStringContainsString('Diante do exposto, requer que seja condenada a ré', $paragraphs['intro']);
        $this->assertStringContainsString('valores atualizados até 22/10/2026.', $paragraphs['intro']);

        $this->assertSame([
            "a) Pagamento das horas extras além da 08ª diária\tR$ ________",
            "a.1) Reflexos em 13º salários\tR$ ________",
            "a.2) Reflexos em RSR\tR$ ________",
            "b) Indenização substitutiva do seguro-desemprego\tR$ ________",
        ], array_column($paragraphs['items'], 'text'));

        $this->assertSame(['0', '1701', '1701', '0'], array_column($paragraphs['items'], 'indent'));
        $this->assertSame(['dot', 'dot', 'dot', 'dot'], array_column($paragraphs['items'], 'leader'));
        $this->assertSame(['0', '0', '360', '360'], array_column($paragraphs['items'], 'spaceAfter'));
    }

    public function test_an_amount_written_by_the_ai_is_replaced_by_the_blank(): void
    {
        $paragraphs = $this->pedidoParagraphs([
            ['descricao' => 'Pagamento das horas extras ........ R$ 9.590,40', 'subitens' => [['descricao' => 'Reflexos em RSR… R$ 959,04']]],
            ['descricao' => 'Entrega das guias, sob pena de multa diária de R$ 100,00', 'subitens' => []],
        ]);

        $this->assertSame([
            "a) Pagamento das horas extras\tR$ ________",
            "a.1) Reflexos em RSR\tR$ ________",
            "b) Entrega das guias, sob pena de multa diária de R$ 100,00\tR$ ________",
        ], array_column($paragraphs['items'], 'text'));
    }

    public function test_estimates_are_highlighted_and_add_up_to_the_amount_of_the_claim(): void
    {
        $path = (new PetitionDocxGenerator)->generateMerit([
            'contrato_trabalho_html' => '<p>Contrato.</p>',
            'valor_causa' => 'R$ 999,00',
            'pedidos' => [
                ['descricao' => 'Horas extras', 'valor' => 'R$ 9.590,40', 'subitens' => [['descricao' => 'Reflexos em RSR', 'valor' => 'R$ 959,04']]],
                ['descricao' => 'Seguro-desemprego', 'valor' => '6000', 'subitens' => []],
            ],
        ]);

        $xml = $this->documentXml($path);

        $this->assertSame(4, substr_count($xml, '<w:highlight w:val="yellow"/>'));
        $this->assertStringContainsString('>R$ 9.590,40<', $xml);
        $this->assertStringContainsString('>R$ 959,04<', $xml);
        $this->assertStringContainsString('>R$ 6.000,00<', $xml);
        $this->assertStringContainsString('R$ 16.549,44 (dezesseis mil, quinhentos e quarenta e nove reais e quarenta e quatro centavos)', $xml);
        $this->assertStringNotContainsString('R$ 999,00', $xml);
    }

    public function test_without_estimates_the_ai_amount_of_the_claim_is_kept(): void
    {
        $xml = $this->documentXml((new PetitionDocxGenerator)->generateMerit([
            'contrato_trabalho_html' => '<p>Contrato.</p>',
            'valor_causa' => 'R$ 1.000,00',
            'valor_causa_extenso' => 'mil reais',
            'pedidos' => [['descricao' => 'Horas extras', 'subitens' => []]],
        ]));

        $this->assertStringContainsString('R$ 1.000,00 (mil reais)', $xml);
        $this->assertStringContainsString('R$ ________', $xml);
    }

    public function test_honorarios_chosen_as_a_topic_are_not_repeated(): void
    {
        $xml = $this->documentXml((new PetitionDocxGenerator)->generateMerit([
            'contrato_trabalho_html' => '<p>Contrato.</p>',
            'incluir_honorarios' => true,
            'honorarios_html' => '<p>Honorários.</p>',
            'topicos' => [['titulo' => 'DOS HONORÁRIOS ADVOCATÍCIOS', 'conteudo_html' => '<p>Requer honorários.</p>', 'subtopicos' => []]],
        ]));

        $this->assertSame(1, substr_count($xml, 'DOS HONORÁRIOS ADVOCATÍCIOS'));
    }

    public function test_final_requests_follow_the_model_wording(): void
    {
        $text = implode("\n", array_column($this->pedidoParagraphs([], [
            'cliente_estado_civil' => 'casada',
            'requerimentos_finais_html' => '<p>Requer, ainda, a produção de todos os meios de prova.</p>',
        ])['after'], 'text'));

        $this->assertStringNotContainsString('Requer, ainda', $text);

        $this->assertStringContainsString('condenando a reclamada na forma do pedido', $text);
        $this->assertStringContainsString('perícia técnica, juntada de documentos', $text);
        $this->assertStringContainsString("Nesses termos,\nPede deferimento\n", $text);
    }

    public function test_empty_contract_text_gets_a_placeholder_so_headings_do_not_share_a_box(): void
    {
        $xml = $this->documentXml((new PetitionDocxGenerator)->generateMerit(['contrato_trabalho_html' => '']));

        $this->assertStringContainsString('[Descrever o contrato de trabalho', $xml);
    }

    private function documentXml(string $path): string
    {
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        return $xml;
    }

    /**
     * @param  list<array{descricao: string, subitens: list<array{descricao: string}>}>  $pedidos
     * @param  array<string, mixed>  $extra
     * @return array{intro: string, items: list<array{text: string, indent: string, leader: string, spaceAfter: string}>, after: list<array{text: string}>}
     */
    private function pedidoParagraphs(array $pedidos, array $extra = []): array
    {
        $path = (new PetitionDocxGenerator)->generateMerit([
            'contrato_trabalho_html' => '<p>Contrato.</p>',
            'pedidos' => $pedidos,
            ...$extra,
        ]);

        $zip = new ZipArchive;
        $zip->open($path);
        $document = new DOMDocument;
        $document->loadXML($zip->getFromName('word/document.xml'));
        $zip->close();
        @unlink($path);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $result = ['intro' => '', 'items' => [], 'after' => []];
        $stage = 'before';

        foreach ($xpath->query('//w:body/w:p') as $paragraph) {
            $text = '';
            foreach ($xpath->query('.//w:t', $paragraph) as $node) {
                $text .= $node->textContent;
            }

            if (str_contains($text, 'DOS PEDIDOS')) {
                $stage = 'intro';

                continue;
            }

            if ($stage === 'intro') {
                $result['intro'] = $text;
                $stage = 'items';

                continue;
            }

            if ($stage === 'items' && ! str_contains($text, "\t")) {
                $stage = 'after';
            }

            if ($stage === 'items') {
                $result['items'][] = [
                    'text' => $text,
                    'indent' => $this->attribute($xpath, $paragraph, 'w:pPr/w:ind', 'w:left'),
                    'leader' => $this->attribute($xpath, $paragraph, 'w:pPr/w:tabs/w:tab', 'w:leader'),
                    'spaceAfter' => $this->attribute($xpath, $paragraph, 'w:pPr/w:spacing', 'w:after'),
                ];
            } elseif ($stage === 'after') {
                $result['after'][] = ['text' => $text];
            }
        }

        return $result;
    }

    private function attribute(DOMXPath $xpath, DOMElement $paragraph, string $path, string $name): string
    {
        $node = $xpath->query($path, $paragraph)->item(0);

        return $node instanceof DOMElement ? $node->getAttribute($name) : '';
    }
}
