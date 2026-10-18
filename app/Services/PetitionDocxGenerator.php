<?php

namespace App\Services;

use App\Models\Topic;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Tab;

class PetitionDocxGenerator
{
    protected PhpWord $word;

    /**
     * Next sequential number for the merit section headings (01 – contract, 02 – first topic, ...).
     */
    protected int $nextTopicNumber = 1;

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

    /**
     * Header and footer copied from the office's petition template (PETIÇÃO.doc).
     */
    protected function addHeaderAndFooter(Section $section): void
    {
        $header = $section->addHeader();
        $header->addImage(
            public_path('images/logo_peticao.png'),
            [
                'width' => Converter::cmToPoint(6.59),
                'height' => Converter::cmToPoint(3.53),
                'positioning' => 'relative',
                'posHorizontal' => 'absolute',
                'posHorizontalRel' => 'column',
                'marginLeft' => Converter::cmToPoint(5.25),
                'posVertical' => 'absolute',
                'posVerticalRel' => 'line',
                'marginTop' => Converter::cmToPoint(-0.89),
                'wrappingStyle' => 'topAndBottom',
            ]
        );
        $header->addText('', null, ['spaceAfter' => 0]);

        $footer = $section->addFooter();
        $footerFont = ['name' => 'Verdana', 'size' => 10, 'color' => 'D7AA2D'];
        $bodyWidth = 11906 - 1276 - 1133;

        $addressLine = $footer->addTextRun([
            'alignment' => Jc::LEFT,
            'spaceAfter' => 0,
            'lineHeight' => 1.0,
            'tabs' => [new Tab('center', intdiv($bodyWidth, 2)), new Tab('right', $bodyWidth)],
        ]);
        $addressLine->addText("\t".'Rua Jaspe, nº 290, Bairro: Iguaçu – Cidade: Ipatinga/MG – CEP: 35.162-077.'."\t", $footerFont);
        $addressLine->addField('PAGE');

        $footer->addText(
            '(31) 3616-2247 (31) 98768-2247. Email: atendimento@advocaciagb.com',
            $footerFont,
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'lineHeight' => 1.0]
        );
    }

    protected function configureDefaults(): void
    {
        $this->word->setDefaultFontName('Calibri');
        $this->word->setDefaultFontSize(12);
        $this->word->setDefaultParagraphStyle([
            'alignment' => Jc::BOTH,
            'spaceAfter' => Converter::pointToTwip(18),
            'lineHeight' => 1.5,
        ]);
    }

    protected function defineStyles(): void
    {
        $this->styles = [
            'section' => [
                'pageSizeW' => 11906,
                'pageSizeH' => 16838,
                'marginTop' => 2835,
                'marginBottom' => 1418,
                'marginLeft' => 1276,
                'marginRight' => 1133,
                'headerHeight' => 737,
                'footerHeight' => 352,
            ],
            'center' => [
                'alignment' => Jc::CENTER,
                'spaceAfter' => Converter::pointToTwip(18),
                'lineHeight' => 1.5,
            ],
            'indent' => [
                'alignment' => Jc::BOTH,
                'spaceAfter' => Converter::pointToTwip(18),
                'lineHeight' => 1.5,
            ],
            'tight' => [
                'alignment' => Jc::BOTH,
                'spaceAfter' => 0,
                'lineHeight' => 1.5,
            ],
            'sectionTitle' => [
                'alignment' => Jc::BOTH,
                'keepNext' => true,
                'spaceBefore' => 0,
                'spaceAfter' => 0,
                'lineHeight' => 1.5,
            ],
            'topicTitle' => [
                'alignment' => Jc::BOTH,
                'keepNext' => true,
                'spaceBefore' => 0,
                'spaceAfter' => Converter::pointToTwip(18),
                'lineHeight' => 1.5,
                'borderSize' => 4,
                'borderColor' => '000000',
            ],
            'subtopicTitle' => [
                'alignment' => Jc::BOTH,
                'keepNext' => true,
                'spaceBefore' => 0,
                'spaceAfter' => Converter::pointToTwip(18),
                'lineHeight' => 1.5,
            ],
            'spacer' => [
                'spaceAfter' => Converter::pointToTwip(54),
            ],
            'sectionLabel' => ['bold' => true, 'underline' => 'single', 'allCaps' => true],
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
            array_merge($this->styles['indent'], $this->styles['spacer'])
        );
    }

    /** @param  array<string, mixed>  $data */
    protected function addQualificacaoReclamante(Section $section, array $data): void
    {
        $textRun = $section->addTextRun($this->styles['tight']);

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
        $textRun = $section->addTextRun($this->styles['center']);
        $textRun->addText('AÇÃO TRABALHISTA', $this->styles['bold']);
        $textRun->addText(', em face de:');
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
            $section->addText('PRELIMINARES', $this->styles['sectionLabel'], $this->styles['sectionTitle']);

            foreach (array_values($preliminares) as $preliminar) {
                $titulo = $preliminar['titulo'] ?? '';
                if ($titulo !== '') {
                    $section->addText(
                        mb_strtoupper($titulo),
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
        $section->addText('MÉRITO', $this->styles['sectionLabel'], $this->styles['sectionTitle']);

        // Contrato de trabalho
        $section->addText(
            $this->nextTopicHeading('DO CONTRATO DE TRABALHO'),
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
                $topicNumber = $this->nextTopicNumber;
                $titulo = $topico['titulo'] ?? 'TÓPICO';

                $section->addText(
                    $this->nextTopicHeading($titulo),
                    $this->styles['boldUpper'],
                    $this->styles['topicTitle']
                );

                if (! empty($topico['conteudo_html'])) {
                    $this->addHtmlContent($section, $topico['conteudo_html']);
                }

                foreach (array_values($topico['subtopicos'] ?? []) as $subIndex => $subtopico) {
                    $subOrdem = $topicNumber.'.'.($subIndex + 1).' –';
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
            $section->addText(
                $this->nextTopicHeading('DOS HONORÁRIOS ADVOCATÍCIOS'),
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
        $section->addText(
            $this->nextTopicHeading('DOS PEDIDOS'),
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

        $pedidos = array_values($data['pedidos'] ?? []);

        foreach ($pedidos as $index => $pedido) {
            $descricao = is_array($pedido) ? ($pedido['descricao'] ?? '') : $pedido;
            $letter = chr(ord('a') + ($index % 26));

            $section->addText(
                "{$letter}) {$descricao}",
                $this->styles['normal'],
                [
                    'alignment' => Jc::BOTH,
                    'spaceAfter' => Converter::pointToTwip($index === count($pedidos) - 1 ? 18 : 4),
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

        $selectedPreliminares = $data['preliminares_selecionadas_ids'] ?? [];

        if (in_array(Topic::JUIZO_100_DIGITAL_ID, $selectedPreliminares, true)) {
            $section->addText(
                'A tramitação do processo no Juízo 100% digital, bem como sejam intimadas as partes requeridas para '
                .'manifestarem sobre a petição no prazo de 5 dias, sob pena do silêncio entender-se como concordância tácita.',
                $this->styles['normal'],
                $this->styles['indent']
            );
        }

        if (in_array(Topic::GRATUIDADE_JUSTICA_ID, $selectedPreliminares, true)) {
            $section->addText(
                'Os benefícios da Assistência Judiciária Gratuita, nos termos da Lei nº 1.060/50 e suas alterações, '
                .'por ser a reclamante pessoa pobre no sentido legal.',
                $this->styles['normal'],
                $this->styles['indent']
            );
        }

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
        $section->addText('Nesses termos,', null, $this->styles['tight']);
        $section->addText('Pede deferimento.', null, $this->styles['tight']);

        $cidade = $data['cidade_assinatura'] ?? 'Ipatinga';
        $dataAssinatura = $data['data_assinatura'] ?? now()->locale('pt_BR')->translatedFormat('j \\d\\e F \\d\\e Y');
        $section->addText("{$cidade}, {$dataAssinatura}", null, $this->styles['indent']);

        $advogados = $this->getAdvogados($data);

        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => 'pct',
            'alignment' => Jc::CENTER,
        ]);

        foreach (array_chunk($advogados, 2) as $pair) {
            $table->addRow();
            $isLoneLawyer = count($pair) === 1;

            foreach ($pair as $adv) {
                $cellStyle = ['alignment' => Jc::CENTER];
                if ($isLoneLawyer) {
                    $cellStyle['gridSpan'] = 2;
                }

                $cell = $table->addCell(5000, $cellStyle);
                $cell->addText($adv['nome'] ?? '', null, ['alignment' => Jc::CENTER, 'spaceBefore' => Converter::pointToTwip(18), 'spaceAfter' => 0]);
                $cell->addText($adv['oab'] ?? '', null, ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            }
        }
    }

    /**
     * Add the HTML written by the AI (or the editor) as Word paragraphs.
     */
    protected function addHtmlContent(Section $section, string $html): void
    {
        (new WordHtmlRenderer($this->styles['indent']))->render($section, $html);
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

    /**
     * Build a numbered merit heading ("02 – TITLE.") and advance the counter.
     */
    protected function nextTopicHeading(string $title): string
    {
        $number = str_pad((string) $this->nextTopicNumber++, 2, '0', STR_PAD_LEFT);
        $title = preg_replace('/^\s*\d+(\.\d+)*\s*[–-]\s*/u', '', $title);

        return mb_strtoupper("{$number} – ".rtrim(trim($title), '.').'.');
    }

    protected function save(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'petition_').'.docx';
        $writer = IOFactory::createWriter($this->word, 'Word2007');
        $writer->save($path);

        return $path;
    }
}
