<?php

namespace Tests\Feature;

use App\Services\PetitionDocxGenerator;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;
use ZipArchive;

class PetitionDocxOpeningTest extends TestCase
{
    private const CASE_DATA = [
        'cidade_vara' => 'CORONEL FABRICIANO/MINAS GERAIS',
        'cliente_nome' => 'Gabriel Alves de Macedo',
        'cliente_nacionalidade' => 'brasileiro',
        'cliente_estado_civil' => 'casado',
        'cliente_profissao' => 'Analista de Transporte',
        'cliente_rg' => 'MG.15075531',
        'cliente_orgao_expedidor' => 'SSP/MG',
        'cliente_cpf' => '090.179.646-85',
        'cliente_endereco' => 'Rua Vinólia',
        'cliente_numero' => '598',
        'cliente_bairro' => 'Esperança',
        'cliente_cep' => '35.162-347',
        'cliente_cidade' => 'Ipatinga',
        'cliente_uf' => 'MG',
        'cliente_ctps' => '1758596',
        'cliente_ctps_serie' => '003-0/MG',
        'cliente_pis' => '206.95779.15-4',
        'empregador_nome' => 'SHPX Logística LTDA – Shopee',
        'empregador_cnpj' => '42.446.277/0001-92',
        'empregador_endereco' => 'Av. Brigadeiro Faria Lima',
        'empregador_numero' => '3.732',
        'empregador_complemento' => 'Andar 22 e 23',
        'empregador_bairro' => 'Itaim Bibi',
        'empregador_cidade' => 'São Paulo',
        'empregador_uf' => 'SP',
        'empregador_cep' => '04.538-132',
        'empregador_telefone' => '(11) 0000-0000',
    ];

    public function test_opening_follows_the_office_model_with_the_case_data(): void
    {
        $paragraphs = $this->openingParagraphs(self::CASE_DATA);

        $this->assertSame('AO DOUTO JUÍZO DA ___ª VARA DO TRABALHO DE CORONEL FABRICIANO/MINAS GERAIS', $paragraphs[0]);
        $this->assertSame(
            'GABRIEL ALVES DE MACEDO, brasileiro, casado, Analista de Transporte, RG MG.15075531, SSP/MG, CPF 090.179.646-85, '
            .'residente e domiciliado na Rua Vinólia, nº 598, bairro Esperança, CEP 35.162-347, Ipatinga/MG, CTPS: 1758596, '
            .'série 003-0/MG, PIS/PASEP 206.95779.15-4, por seus advogados Grimaldo Bruno Fernandes Botelho, inscrito na OAB/MG 120.920, '
            .'Helen Cristina Ribeiro Soares Real, inscrita na OAB/MG 139.212 e Igor Felippe Nascimento Firmino de Oliveira, '
            .'inscrito na OAB/MG 191.603, mandato incluso, ambos com endereço profissional na Rua Jaspe, nº 290, Bairro: Iguaçu – '
            .'Cidade: Ipatinga/MG – CEP: 35.162-077, local onde recebem intimações/notificações, vem, respeitosamente, à presença '
            .'de Vossa Excelência, nos termos do Artigo 840 da CLT propor:',
            $paragraphs[1]
        );
        $this->assertSame('AÇÃO TRABALHISTA, em face de:', $paragraphs[2]);
        $this->assertSame(
            'SHPX LOGÍSTICA LTDA – SHOPEE, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº 42.446.277/0001-92, '
            .'com sede na Av. Brigadeiro Faria Lima, nº 3.732, Andar 22 e 23, Itaim Bibi, São Paulo/SP, CEP 04.538-132, '
            .'de acordo com as razões a seguir aduzidas:',
            $paragraphs[3]
        );
    }

    public function test_missing_data_becomes_blanks_to_fill_in(): void
    {
        $paragraphs = $this->openingParagraphs([]);

        $this->assertSame('AO DOUTO JUÍZO DA ___ª VARA DO TRABALHO DE [CIDADE]/[ESTADO]', $paragraphs[0]);
        $this->assertStringStartsWith('[NOME DO RECLAMANTE], [nacionalidade], [estado civil], [profissão], RG [RG]', $paragraphs[1]);
        $this->assertStringStartsWith('[NOME DA RECLAMADA], pessoa jurídica de direito privado, inscrita no CNPJ sob o nº [CNPJ]', $paragraphs[3]);
    }

    public function test_blanks_are_highlighted_in_light_green_and_informed_data_is_not(): void
    {
        $path = (new PetitionDocxGenerator)->generateFull(['cliente_nome' => 'Gabriel', 'contrato_trabalho_html' => '<p>Admitido em [data de admissão].</p>']);
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        $this->assertMatchesRegularExpression('/<w:shd [^>]*w:fill="C6EFCE"[^>]*\/><\/w:rPr><w:t[^>]*>\[CPF\]</', $xml);
        $this->assertMatchesRegularExpression('/<w:shd [^>]*w:fill="C6EFCE"[^>]*\/><\/w:rPr><w:t[^>]*>\[data de admissão\]</', $xml);
        $this->assertDoesNotMatchRegularExpression('/w:fill="C6EFCE"[^>]*\/><\/w:rPr><w:t[^>]*>GABRIEL</', $xml);
    }

    public function test_feminine_client_is_residente_e_domiciliada(): void
    {
        $paragraphs = $this->openingParagraphs(['cliente_estado_civil' => 'solteira']);

        $this->assertStringContainsString('residente e domiciliada na', $paragraphs[1]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function openingParagraphs(array $data): array
    {
        $path = (new PetitionDocxGenerator)->generateFull($data + ['contrato_trabalho_html' => '<p>Contrato.</p>']);

        $zip = new ZipArchive;
        $zip->open($path);
        $document = new DOMDocument;
        $document->loadXML($zip->getFromName('word/document.xml'));
        $zip->close();
        @unlink($path);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $paragraphs = [];
        foreach ($xpath->query('//w:body/w:p') as $paragraph) {
            $text = '';
            foreach ($xpath->query('.//w:t', $paragraph) as $node) {
                $text .= $node->textContent;
            }
            if ($text !== '') {
                $paragraphs[] = $text;
            }
        }

        return array_slice($paragraphs, 0, 4);
    }
}
