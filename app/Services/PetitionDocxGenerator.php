<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;

class PetitionDocxGenerator
{
    protected PhpWord $word;

    /** @var array<string, mixed> */
    protected array $styles;

    public function __construct()
    {
        $this->word = new PhpWord;
        $this->configureDefaults();
        $this->defineStyles();
    }

    /**
     * Generate the full petition (with client/employer data).
     *
     * @param  array<string, mixed>  $data
     */
    public function generateFull(array $data): string
    {
        $section = $this->word->addSection($this->styles['section']);
        $this->addHeaderAndFooter($section);

        $this->addEnderecamento($section, $data);
        $this->addQualificacaoReclamante($section, $data);
        $this->addTituloAcao($section, $data);
        $this->addQualificacaoReclamada($section, $data);
        $this->addMerito($section, $data);
        $this->addPedidos($section, $data);
        $this->addRequerimentosFinais($section, $data);
        $this->addValorCausa($section, $data);
        $this->addFecho($section, $data);

        return $this->save();
    }

    /**
     * Generate merit-only petition (no client/employer data).
     *
     * @param  array<string, mixed>  $data
     */
    public function generateMerit(array $data): string
    {
        $section = $this->word->addSection($this->styles['section']);
        $this->addHeaderAndFooter($section);

        $this->addMerito($section, $data);
        $this->addPedidos($section, $data);
        $this->addRequerimentosFinais($section, $data);
        $this->addValorCausa($section, $data);
        $this->addFecho($section, $data);

        return $this->save();
    }

    protected function addHeaderAndFooter(Section $section): void
    {
        $header = $section->addHeader();
        $header->addImage(
            public_path('images/logo_marca_dagua.png'),
            [
                'width' => Converter::cmToPoint(3.5),
                'height' => Converter::cmToPoint(2.8),
                'alignment' => Jc::CENTER,
            ]
        );
        $header->addTextBreak(1, ['size' => 6]);

        $footer = $section->addFooter();
        $footer->addText(
            'Rua Jaspe, nº 290, Bairro: Iguaçu – Cidade: Ipatinga/MG – CEP: 35.162-077.',
            ['size' => 10, 'color' => 'D7AA2D'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
        $footer->addText(
            '(31) 3616-2247 (31) 98768-2247. Email: atendimento@advocaciagb.com',
            ['size' => 10, 'color' => 'D7AA2D'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]
        );
    }

    protected function configureDefaults(): void
    {
        $this->word->setDefaultFontName('Times New Roman');
        $this->word->setDefaultFontSize(12);
        $this->word->setDefaultParagraphStyle([
            'alignment' => Jc::BOTH,
            'spaceAfter' => Converter::pointToTwip(6),
            'lineHeight' => 1.5,
        ]);
    }

    protected function defineStyles(): void
    {
        $this->styles = [
            'section' => [
                'marginTop' => Converter::cmToTwip(3),
                'marginBottom' => Converter::cmToTwip(2),
                'marginLeft' => Converter::cmToTwip(3),
                'marginRight' => Converter::cmToTwip(2),
            ],
            'center' => [
                'alignment' => Jc::CENTER,
                'spaceAfter' => Converter::pointToTwip(6),
                'lineHeight' => 1.5,
            ],
            'indent' => [
                'alignment' => Jc::BOTH,
                'spaceAfter' => Converter::pointToTwip(6),
                'lineHeight' => 1.5,
            ],
            'sectionTitle' => [
                'alignment' => Jc::BOTH,
                'spaceBefore' => Converter::pointToTwip(18),
                'spaceAfter' => Converter::pointToTwip(8),
                'lineHeight' => 1.5,
            ],
            'topicTitle' => [
                'alignment' => Jc::BOTH,
                'spaceBefore' => Converter::pointToTwip(16),
                'spaceAfter' => Converter::pointToTwip(6),
                'lineHeight' => 1.5,
            ],
            'subtopicTitle' => [
                'alignment' => Jc::BOTH,
                'spaceBefore' => Converter::pointToTwip(12),
                'spaceAfter' => Converter::pointToTwip(4),
                'lineHeight' => 1.5,
            ],
            'spacer' => [
                'spaceAfter' => Converter::pointToTwip(24),
            ],
            'bold' => ['bold' => true],
            'boldUpper' => ['bold' => true, 'allCaps' => true],
            'normal' => [],
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function addEnderecamento(Section $section, array $data): void
    {
        $vara = $data['vara_competente'] ?? '___ VARA DO TRABALHO DE __________';
        $section->addText(
            mb_strtoupper("AO DOUTO JUÍZO DA {$vara}"),
            $this->styles['boldUpper'],
            $this->styles['center']
        );
        $section->addTextBreak(1, null, $this->styles['spacer']);
    }

    /** @param  array<string, mixed>  $data */
    protected function addQualificacaoReclamante(Section $section, array $data): void
    {
        $textRun = $section->addTextRun($this->styles['indent']);

        $textRun->addText(
            mb_strtoupper($data['cliente_nome'] ?? 'NOME DA PARTE AUTORA'),
            $this->styles['bold']
        );

        $parts = [];
        $parts[] = $data['cliente_nacionalidade'] ?? 'nacionalidade não informada';
        $parts[] = $data['cliente_estado_civil'] ?? 'estado civil não informado';
        $parts[] = $data['cliente_profissao'] ?? 'profissão não informada';

        $textRun->addText(', '.implode(', ', $parts));

        if (! empty($data['cliente_rg'])) {
            $textRun->addText(', RG '.$data['cliente_rg']);
            if (! empty($data['cliente_orgao_expedidor'])) {
                $textRun->addText(', '.$data['cliente_orgao_expedidor']);
            }
        }

        if (! empty($data['cliente_cpf'])) {
            $textRun->addText(', CPF '.$data['cliente_cpf']);
        }

        if (! empty($data['cliente_ctps'])) {
            $textRun->addText(', CTPS '.$data['cliente_ctps']);
        }
        if (! empty($data['cliente_ctps_serie'])) {
            $textRun->addText(', SÉRIE '.$data['cliente_ctps_serie']);
        }
        if (! empty($data['cliente_pis'])) {
            $textRun->addText(', PIS/PASEP '.$data['cliente_pis']);
        }

        $estadoCivil = $data['cliente_estado_civil'] ?? '';
        $isFeminine = $estadoCivil !== '' && mb_substr($estadoCivil, -1) === 'a';
        $residenteTerm = $isFeminine ? 'residente e domiciliada' : 'residente e domiciliado';

        $textRun->addText(', '.$residenteTerm.' à '.($data['cliente_endereco'] ?? 'endereço não informado'));

        if (! empty($data['cliente_numero'])) {
            $textRun->addText(' nº '.$data['cliente_numero']);
        }
        if (! empty($data['cliente_complemento'])) {
            $textRun->addText(', '.$data['cliente_complemento']);
        }

        $textRun->addText(', Bairro '.($data['cliente_bairro'] ?? 'não informado'));
        $textRun->addText(', '.($data['cliente_cidade'] ?? 'cidade não informada').'/'.($data['cliente_uf'] ?? 'UF'));
        $textRun->addText(', CEP '.($data['cliente_cep'] ?? 'não informado'));

        $advogados = $this->getAdvogados($data);
        if ($advogados !== []) {
            $textRun->addText(', por seus advogados ');

            $advTexts = [];
            foreach ($advogados as $adv) {
                $inscricao = (($adv['genero'] ?? 'M') === 'F') ? 'inscrita' : 'inscrito';
                $advTexts[] = ($adv['nome'] ?? '').', '.$inscricao.' na '.($adv['oab'] ?? '');
            }

            if (count($advTexts) === 1) {
                $textRun->addText($advTexts[0]);
            } else {
                $last = array_pop($advTexts);
                $textRun->addText(implode(', ', $advTexts).' e '.$last);
            }

            $textRun->addText(', mandato incluso');
        }

        $textRun->addText(
            ', ambos com endereço profissional na Rua Jaspe, nº 290, Bairro: Iguaçu Ipatinga/MG CEP: 35.162-077, '
            .'local onde recebem intimações/notificações, '
            .'vem, respeitosamente, à presença de Vossa Excelência, '
            .'nos termos do Artigo 840 da CLT propor:'
        );
    }

    /** @param  array<string, mixed>  $data */
    protected function addTituloAcao(Section $section, array $data): void
    {
        $section->addText(
            'AÇÃO TRABALHISTA, em face de:',
            ['bold' => true, 'size' => 13],
            array_merge($this->styles['center'], [
                'spaceBefore' => Converter::pointToTwip(20),
                'spaceAfter' => Converter::pointToTwip(14),
            ])
        );
    }

    /** @param  array<string, mixed>  $data */
    protected function addQualificacaoReclamada(Section $section, array $data): void
    {
        $textRun = $section->addTextRun($this->styles['indent']);

        $textRun->addText(
            mb_strtoupper($data['empregador_nome'] ?? 'NOME DA RECLAMADA'),
            $this->styles['bold']
        );

        $textRun->addText(', pessoa jurídica de direito privado');
        $textRun->addText(', inscrita no CNPJ sob o nº '.($data['empregador_cnpj'] ?? 'CNPJ não informado'));
        $textRun->addText(', com sede e endereço para receber notificações à '.($data['empregador_endereco'] ?? 'endereço não informado'));

        if (! empty($data['empregador_numero'])) {
            $textRun->addText(', nº '.$data['empregador_numero']);
        }
        if (! empty($data['empregador_complemento'])) {
            $textRun->addText(', '.$data['empregador_complemento']);
        }

        $textRun->addText(', Bairro '.($data['empregador_bairro'] ?? 'não informado'));
        $textRun->addText(', '.($data['empregador_cidade'] ?? 'cidade não informada').'/'.($data['empregador_uf'] ?? 'UF'));
        $textRun->addText(', CEP '.($data['empregador_cep'] ?? 'não informado'));

        $tel = $data['empregador_telefone'] ?? '';
        $email = $data['empregador_email'] ?? '';

        if ($tel !== '' && $email !== '') {
            $textRun->addText(', telefone '.$tel.' e endereço eletrônico '.$email);
        } elseif ($tel !== '') {
            $textRun->addText(', telefone '.$tel);
        } elseif ($email !== '') {
            $textRun->addText(', endereço eletrônico '.$email);
        }
    }

    /** @param  array<string, mixed>  $data */
    protected function addMerito(Section $section, array $data): void
    {
        // Preliminares
        $preliminares = $data['preliminares'] ?? [];
        if (is_array($preliminares) && $preliminares !== []) {
            $section->addText('PRELIMINARES', $this->styles['boldUpper'], $this->styles['sectionTitle']);

            foreach (array_values($preliminares) as $index => $preliminar) {
                $titulo = $preliminar['titulo'] ?? '';
                if ($titulo !== '') {
                    $roman = $this->toRoman($index + 1);
                    $section->addText(
                        mb_strtoupper("{$roman} - {$titulo}"),
                        $this->styles['boldUpper'],
                        $this->styles['topicTitle']
                    );
                }

                if (! empty($preliminar['conteudo_html'])) {
                    $this->addHtmlContent($section, $preliminar['conteudo_html']);
                }
            }
        }

        // Mérito
        $section->addText('MÉRITO', $this->styles['boldUpper'], $this->styles['sectionTitle']);

        // Contrato de trabalho
        $section->addText(
            '01 – DO CONTRATO DE TRABALHO.',
            $this->styles['boldUpper'],
            $this->styles['topicTitle']
        );
        $this->addHtmlContent(
            $section,
            $data['contrato_trabalho_html'] ?? '<p>Inserir descrição do contrato de trabalho.</p>'
        );

        // Tópicos dinâmicos
        $topicos = $data['topicos'] ?? [];
        if (is_array($topicos)) {
            foreach ($topicos as $topico) {
                $ordem = $topico['ordem'] ?? '';
                $titulo = $topico['titulo'] ?? 'TÓPICO';

                $section->addText(
                    mb_strtoupper("{$ordem} – {$titulo}."),
                    $this->styles['boldUpper'],
                    $this->styles['topicTitle']
                );

                if (! empty($topico['conteudo_html'])) {
                    $this->addHtmlContent($section, $topico['conteudo_html']);
                }

                foreach ($topico['subtopicos'] ?? [] as $subtopico) {
                    $subOrdem = $subtopico['ordem'] ?? '';
                    $subTitulo = $subtopico['titulo'] ?? 'Subtópico';

                    $section->addText(
                        "{$subOrdem} {$subTitulo}",
                        $this->styles['bold'],
                        $this->styles['subtopicTitle']
                    );

                    if (! empty($subtopico['conteudo_html'])) {
                        $this->addHtmlContent($section, $subtopico['conteudo_html']);
                    }
                }
            }
        }

        // Honorários
        if (! empty($data['incluir_honorarios'])) {
            $ordem = $data['honorarios_ordem'] ?? '06';
            $section->addText(
                mb_strtoupper("{$ordem} – DOS HONORÁRIOS ADVOCATÍCIOS."),
                $this->styles['boldUpper'],
                $this->styles['topicTitle']
            );
            $this->addHtmlContent(
                $section,
                $data['honorarios_html']
                    ?? '<p>Requer a condenação da reclamada ao pagamento de honorários advocatícios sucumbenciais, na forma da lei.</p>'
            );
        }
    }

    /** @param  array<string, mixed>  $data */
    protected function addPedidos(Section $section, array $data): void
    {
        $ordem = $data['pedidos_ordem'] ?? '07';
        $section->addText(
            mb_strtoupper("{$ordem} – DOS PEDIDOS."),
            $this->styles['boldUpper'],
            $this->styles['topicTitle']
        );

        $section->addText(
            'Diante de todo o exposto, requer a parte reclamante a total procedência da presente reclamação trabalhista, '
            .'com a condenação da reclamada ao pagamento e cumprimento das parcelas e obrigações abaixo descritas, '
            .'tudo a ser apurado em regular liquidação de sentença, acrescido de juros, correção monetária e demais '
            .'consectários legais:',
            $this->styles['normal'],
            $this->styles['indent']
        );

        $pedidos = $data['pedidos'] ?? [];

        foreach (array_values($pedidos) as $index => $pedido) {
            $descricao = is_array($pedido) ? ($pedido['descricao'] ?? '') : $pedido;
            $letter = chr(ord('a') + ($index % 26));

            $section->addText(
                "{$letter}) {$descricao}",
                $this->styles['normal'],
                [
                    'alignment' => Jc::BOTH,
                    'spaceAfter' => Converter::pointToTwip(4),
                    'indentation' => [
                        'left' => Converter::cmToTwip(0.8),
                        'hanging' => Converter::cmToTwip(0.8),
                    ],
                    'lineHeight' => 1.5,
                ]
            );
        }
    }

    /** @param  array<string, mixed>  $data */
    protected function addRequerimentosFinais(Section $section, array $data): void
    {
        $section->addText(
            'Diante do exposto, requer de Vossa Excelência que proceda a citação da reclamada para contestar a '
            .'presente reclamatória trabalhista, sob pena de revelia e confissão quanto à matéria de fato, que, '
            .'ao final, deverá ser julgada procedente, condenando a demandada na forma do pedido, acrescido de juros '
            .'e correção monetária.',
            $this->styles['normal'],
            $this->styles['indent']
        );

        $section->addText(
            'A tramitação do processo no Juízo 100% digital, bem como sejam intimadas as partes requeridas para '
            .'manifestarem sobre a petição no prazo de 5 dias, sob pena do silêncio entender-se como concordância tácita.',
            $this->styles['normal'],
            $this->styles['indent']
        );

        $section->addText(
            'Os benefícios da Assistência Judiciária Gratuita, nos termos da Lei nº 1.060/50 e suas alterações, '
            .'por ser a reclamante pessoa pobre no sentido legal.',
            $this->styles['normal'],
            $this->styles['indent']
        );

        if (! empty($data['requerimentos_finais_html'])) {
            $this->addHtmlContent($section, $data['requerimentos_finais_html']);
        }

        $section->addText(
            'Protesta provar o alegado por todos os meios de prova em direito admitidos, sem exclusão de nenhuma, '
            .'especialmente pelo depoimento pessoal da reclamada, sob pena de confissão (En. 74 do C. TST), oitiva de testemunhas, '
            .'perícia técnica para comprovar a insalubridade e periculosidade, juntada de documentos e demais provas '
            .'que se fizerem necessárias.',
            $this->styles['normal'],
            $this->styles['indent']
        );
    }

    /** @param  array<string, mixed>  $data */
    protected function addValorCausa(Section $section, array $data): void
    {
        $valor = ! empty($data['valor_causa']) ? $data['valor_causa'] : 'R$ 0,00';
        $extenso = $data['valor_causa_extenso'] ?? '';

        $textRun = $section->addTextRun($this->styles['indent']);
        $textRun->addText('Dá à causa o valor de ');
        $textRun->addText($valor);
        if (! empty($extenso)) {
            $textRun->addText(" ({$extenso})");
        }
        $textRun->addText(', para os fins legais.');
    }

    /** @param  array<string, mixed>  $data */
    protected function addFecho(Section $section, array $data): void
    {
        $section->addTextBreak(1);

        $section->addText('Nesses termos,');
        $section->addText('Pede deferimento.');

        $section->addTextBreak(1);

        $cidade = $data['cidade_assinatura'] ?? ($data['cliente_cidade'] ?? 'Ipatinga');
        $dataAssinatura = $data['data_assinatura'] ?? now()->format('d/m/Y');
        $section->addText("{$cidade}, {$dataAssinatura}");

        $section->addTextBreak(2);

        $advogados = $this->getAdvogados($data);

        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => 'pct',
            'alignment' => Jc::CENTER,
        ]);

        foreach (array_chunk($advogados, 2) as $rowIndex => $pair) {
            $table->addRow();
            $isLoneLawyer = count($pair) === 1;
            $spaceBefore = $rowIndex === 0 ? 36 : 72;

            foreach ($pair as $adv) {
                $cellStyle = ['alignment' => Jc::CENTER];
                if ($isLoneLawyer) {
                    $cellStyle['gridSpan'] = 2;
                }

                $cell = $table->addCell(5000, $cellStyle);
                $cell->addText(
                    '______________________________',
                    ['size' => 10],
                    ['alignment' => Jc::CENTER, 'spaceBefore' => Converter::pointToTwip($spaceBefore)]
                );
                $cell->addText(
                    $adv['nome'] ?? '',
                    ['bold' => true, 'size' => 10],
                    ['alignment' => Jc::CENTER]
                );
                $cell->addText(
                    $adv['oab'] ?? '',
                    ['size' => 10],
                    ['alignment' => Jc::CENTER]
                );
            }
        }
    }

    /**
     * Parse simple HTML content and add it as Word paragraphs.
     */
    protected function addHtmlContent(Section $section, string $html): void
    {
        $html = strip_tags($html, '<p><strong><b><br><ol><ul><li><em><i>');
        $html = trim($html);

        if (empty($html)) {
            return;
        }

        // Split by paragraphs
        $blocks = preg_split('/<\/?p[^>]*>/i', $html);

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            // Check for list blocks
            if (preg_match('/<[ou]l/i', $block)) {
                $this->addListFromHtml($section, $block);

                continue;
            }

            $textRun = $section->addTextRun($this->styles['indent']);
            $this->addFormattedText($textRun, $block);
        }

        // Handle any standalone lists not inside <p>
        if (preg_match_all('/<(ol|ul)>(.*?)<\/\1>/si', $html, $matches, PREG_SET_ORDER)) {
            // Already handled in block loop above if inside <p>
        }
    }

    protected function addListFromHtml(Section $section, string $html): void
    {
        preg_match_all('/<li>(.*?)<\/li>/si', $html, $items);

        foreach ($items[1] as $item) {
            $textRun = $section->addTextRun($this->styles['indent']);
            $this->addFormattedText($textRun, $item);
        }
    }

    /**
     * Parse inline HTML (bold, italic, br) and add to a TextRun.
     */
    protected function addFormattedText(TextRun $textRun, string $html): void
    {
        $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);

        $parts = preg_split('/(<\/?(?:strong|b|em|i)>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        $bold = false;
        $italic = false;

        foreach ($parts as $part) {
            $lower = strtolower($part);

            if ($lower === '<strong>' || $lower === '<b>') {
                $bold = true;

                continue;
            }
            if ($lower === '</strong>' || $lower === '</b>') {
                $bold = false;

                continue;
            }
            if ($lower === '<em>' || $lower === '<i>') {
                $italic = true;

                continue;
            }
            if ($lower === '</em>' || $lower === '</i>') {
                $italic = false;

                continue;
            }

            $text = html_entity_decode(strip_tags($part), ENT_QUOTES, 'UTF-8');
            if ($text === '') {
                continue;
            }

            $fontStyle = [];
            if ($bold) {
                $fontStyle['bold'] = true;
            }
            if ($italic) {
                $fontStyle['italic'] = true;
            }

            $lines = explode("\n", $text);
            foreach ($lines as $i => $line) {
                if ($i > 0) {
                    $textRun->addTextBreak();
                }
                if ($line !== '') {
                    $textRun->addText($line, $fontStyle);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{nome: string, oab: string, genero?: string}>
     */
    protected function getAdvogados(array $data): array
    {
        $advogados = $data['advogados'] ?? [];

        if (! empty($advogados)) {
            return $advogados;
        }

        return [
            [
                'nome' => $data['advogado_1_nome'] ?? 'Grimaldo Bruno Fernandes Botelho',
                'oab' => $data['advogado_1_oab'] ?? 'OAB/MG 120.920',
                'genero' => 'M',
            ],
            [
                'nome' => $data['advogado_2_nome'] ?? 'Helen Cristina Ribeiro Soares Real',
                'oab' => $data['advogado_2_oab'] ?? 'OAB/MG 139.212',
                'genero' => 'F',
            ],
            [
                'nome' => $data['advogado_3_nome'] ?? 'Igor Felippe Nascimento Firmino de Oliveira',
                'oab' => $data['advogado_3_oab'] ?? 'OAB/MG 191.603',
                'genero' => 'M',
            ],
        ];
    }

    protected function toRoman(int $number): string
    {
        $map = [
            1000 => 'M', 900 => 'CM', 500 => 'D', 400 => 'CD',
            100 => 'C', 90 => 'XC', 50 => 'L', 40 => 'XL',
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I',
        ];

        $result = '';

        foreach ($map as $value => $symbol) {
            while ($number >= $value) {
                $result .= $symbol;
                $number -= $value;
            }
        }

        return $result;
    }

    protected function save(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'petition_').'.docx';
        $writer = IOFactory::createWriter($this->word, 'Word2007');
        $writer->save($path);

        return $path;
    }
}
