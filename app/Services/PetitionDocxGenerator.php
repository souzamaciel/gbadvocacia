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
    /**
     * Text width in twips: A4 width minus the left and right margins.
     */
    protected const BODY_WIDTH = 11906 - 1276 - 1133;

    /**
     * Room kept at the right of each pedido line for its amount, so the text wraps before
     * it and the dotted leader always has space (the amount sits in the right indent).
     */
    protected const AMOUNT_WIDTH = 1701;

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
     * Generate the full petition: opening with the client and employer data (or [blanks]
     * to fill in when the petition was built from the Library), then merit and pedidos.
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
        $bodyWidth = self::BODY_WIDTH;

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
            'internalNote' => [
                'alignment' => Jc::BOTH,
                'keepNext' => true,
                'spaceAfter' => Converter::pointToTwip(12),
                'lineHeight' => 1.15,
                'shading' => ['fill' => 'E2EFDA'],
            ],
            'spacer' => [
                'spaceAfter' => Converter::pointToTwip(54),
            ],
            'sectionLabel' => ['bold' => true, 'underline' => 'single', 'allCaps' => true],
            'estimate' => ['fgColor' => 'yellow'],
            'bold' => ['bold' => true],
            'boldUpper' => ['bold' => true, 'allCaps' => true],
            'normal' => [],
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function addEnderecamento(Section $section, array $data): void
    {
        $cidade = trim((string) ($data['cidade_vara'] ?? '')) ?: '[CIDADE]/[ESTADO]';
        $cidade = mb_strtoupper($cidade);

        WordBlanks::add(
            $section->addTextRun(array_merge($this->styles['indent'], $this->styles['spacer'])),
            "AO DOUTO JUÍZO DA ___ª VARA DO TRABALHO DE {$cidade}",
            $this->styles['bold'],
        );
    }

    /**
     * Client qualification in the office's wording (PETIÇÃO.doc); missing data becomes a
     * [blank] for the lawyer to fill in.
     *
     * @param  array<string, mixed>  $data
     */
    protected function addQualificacaoReclamante(Section $section, array $data): void
    {
        $textRun = $section->addTextRun($this->styles['tight']);
        WordBlanks::add($textRun, mb_strtoupper($this->field($data, 'cliente_nome', 'NOME DO RECLAMANTE')), $this->styles['bold']);

        $endereco = 'na '.$this->field($data, 'cliente_endereco', 'endereço')
            .', nº '.$this->field($data, 'cliente_numero', 'número')
            .(trim((string) ($data['cliente_complemento'] ?? '')) !== '' ? ', '.trim($data['cliente_complemento']) : '')
            .', bairro '.$this->field($data, 'cliente_bairro', 'bairro')
            .', CEP '.$this->field($data, 'cliente_cep', 'CEP')
            .', '.$this->field($data, 'cliente_cidade', 'cidade').'/'.$this->field($data, 'cliente_uf', 'UF');

        WordBlanks::add($textRun, ', '.implode(', ', [
            $this->field($data, 'cliente_nacionalidade', 'nacionalidade'),
            $this->field($data, 'cliente_estado_civil', 'estado civil'),
            $this->field($data, 'cliente_profissao', 'profissão'),
            'RG '.$this->field($data, 'cliente_rg', 'RG'),
            $this->field($data, 'cliente_orgao_expedidor', 'órgão expedidor'),
            'CPF '.$this->field($data, 'cliente_cpf', 'CPF'),
            ($this->isFeminine($data) ? 'residente e domiciliada ' : 'residente e domiciliado ').$endereco,
            'CTPS: '.$this->field($data, 'cliente_ctps', 'CTPS'),
            'série '.$this->field($data, 'cliente_ctps_serie', 'série'),
            'PIS/PASEP '.$this->field($data, 'cliente_pis', 'PIS/PASEP'),
        ]));

        $advogados = array_map(
            fn (array $adv): string => ($adv['nome'] ?? '').', '.((($adv['genero'] ?? 'M') === 'F') ? 'inscrita' : 'inscrito').' na '.($adv['oab'] ?? ''),
            $this->getAdvogados($data),
        );
        $last = array_pop($advogados);
        WordBlanks::add($textRun, ', por seus advogados '.($advogados === [] ? $last : implode(', ', $advogados).' e '.$last).', mandato incluso');

        WordBlanks::add($textRun,
            ', ambos com endereço profissional na Rua Jaspe, nº 290, Bairro: Iguaçu – Cidade: Ipatinga/MG – CEP: 35.162-077, '
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
        WordBlanks::add($textRun, mb_strtoupper($this->field($data, 'empregador_nome', 'NOME DA RECLAMADA')), $this->styles['bold']);

        WordBlanks::add($textRun,
            ', pessoa jurídica de direito privado, inscrita no CNPJ sob o nº '.$this->field($data, 'empregador_cnpj', 'CNPJ')
            .', com sede na '.$this->field($data, 'empregador_endereco', 'endereço')
            .', nº '.$this->field($data, 'empregador_numero', 'número')
            .(trim((string) ($data['empregador_complemento'] ?? '')) !== '' ? ', '.trim($data['empregador_complemento']) : '')
            .', '.$this->field($data, 'empregador_bairro', 'bairro')
            .', '.$this->field($data, 'empregador_cidade', 'cidade').'/'.$this->field($data, 'empregador_uf', 'UF')
            .', CEP '.$this->field($data, 'empregador_cep', 'CEP')
            .', de acordo com as razões a seguir aduzidas:'
        );
    }

    /**
     * A case detail, or "[label]" when it was not informed.
     *
     * @param  array<string, mixed>  $data
     */
    protected function field(array $data, string $key, string $label): string
    {
        $value = trim((string) ($data[$key] ?? ''));

        return $value !== '' ? $value : "[{$label}]";
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

                $this->addInternalNote($section, $preliminar['orientacao'] ?? null);

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
        $contrato = trim(strip_tags((string) ($data['contrato_trabalho_html'] ?? ''))) !== ''
            ? $data['contrato_trabalho_html']
            : '<p>[Descrever o contrato de trabalho: admissão, função, salário, jornada e desligamento.]</p>';
        $this->addHtmlContent($section, $contrato);

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

                $this->addInternalNote($section, $topico['orientacao'] ?? null);

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
        $honorariosIsATopic = collect($data['topicos'] ?? [])
            ->contains(fn ($topico): bool => is_array($topico) && str_contains(mb_strtoupper((string) ($topico['titulo'] ?? '')), 'HONORÁRIOS'));

        if (! empty($data['incluir_honorarios']) && ! $honorariosIsATopic) {
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

    /**
     * Pedidos in the office's layout (PETIÇÃO.doc): "a) ..." and indented "a.1) ...", each
     * ending with a dotted leader up to a blank amount for the lawyer to fill in.
     *
     * @param  array<string, mixed>  $data
     */
    protected function addPedidos(Section $section, array $data): void
    {
        $section->addText(
            $this->nextTopicHeading('DOS PEDIDOS'),
            $this->styles['boldUpper'],
            $this->styles['topicTitle']
        );

        $intro = $section->addTextRun($this->styles['indent']);
        $intro->addText('Diante do exposto, ', $this->styles['bold']);
        $intro->addText(
            'requer que seja condenada a ré ao pagamento das parcelas abaixo discriminadas, apresentando seu cálculo '
            .'com descrição dos valores atualizados até '.now()->format('d/m/Y').'.'
        );

        foreach (array_values($data['pedidos'] ?? []) as $index => $pedido) {
            $pedido = is_array($pedido) ? $pedido : ['descricao' => (string) $pedido];
            $letter = chr(ord('a') + ($index % 26));
            $subitens = array_values(array_filter(
                array_map(fn ($subitem): array => is_array($subitem) ? $subitem : ['descricao' => (string) $subitem], $pedido['subitens'] ?? []),
                fn (array $subitem): bool => trim((string) ($subitem['descricao'] ?? '')) !== '',
            ));

            $this->addPedidoLine(
                $section,
                "{$letter}) ".$this->pedidoText((string) ($pedido['descricao'] ?? '')),
                $pedido['valor'] ?? null,
                0,
                $subitens === [],
            );

            foreach ($subitens as $subIndex => $subitem) {
                $this->addPedidoLine(
                    $section,
                    "{$letter}.".($subIndex + 1).') '.$this->pedidoText((string) $subitem['descricao']),
                    $subitem['valor'] ?? null,
                    (int) round(Converter::cmToTwip(3)),
                    $subIndex === count($subitens) - 1,
                );
            }
        }
    }

    /**
     * One line of the pedidos: text, dotted leader and the AI's estimate highlighted in
     * yellow for the lawyer to check (or a blank to fill in when there is no estimate).
     */
    protected function addPedidoLine(Section $section, string $text, ?string $estimate, int $leftIndent, bool $lastOfGroup): void
    {
        $textRun = $section->addTextRun([
            'alignment' => Jc::BOTH,
            'spaceAfter' => $lastOfGroup ? Converter::pointToTwip(18) : 0,
            'lineHeight' => 1.5,
            'indentation' => ['left' => $leftIndent, 'right' => self::AMOUNT_WIDTH],
            'tabs' => [new Tab(Tab::TAB_STOP_RIGHT, self::BODY_WIDTH, Tab::TAB_LEADER_DOT)],
        ]);
        WordBlanks::add($textRun, $text."\t");

        $cents = MoneyInWords::parseCents($estimate);
        if ($cents === null) {
            $textRun->addText('R$ ________');

            return;
        }

        $textRun->addText(MoneyInWords::formatCents($cents), $this->styles['estimate']);
    }

    /**
     * Strips the trailing period and any dotted amount the AI may have added, so the line ends in the leader.
     */
    protected function pedidoText(string $text): string
    {
        $text = preg_replace('/\s*(?:\.{3,}|…+)\s*R\$\s*[\d.,_]*\s*$/u', '', trim($text)) ?? $text;

        return rtrim(trim($text), ' .;');
    }

    /** @param  array<string, mixed>  $data */
    protected function addRequerimentosFinais(Section $section, array $data): void
    {
        $section->addText(
            'Diante do exposto, requer de Vossa Excelência que proceda a citação da reclamada para contestar a '
            .'presente reclamatória trabalhista, sob pena de revelia e confissão quanto à matéria de fato, que, '
            .'ao final, deverá ser julgada procedente, condenando a reclamada na forma do pedido, acrescido de juros '
            .'e correção monetária.',
            $this->styles['normal'],
            $this->styles['indent']
        );

        $selectedPreliminares = $data['preliminares_selecionadas_ids'] ?? [];

        if (in_array(Topic::JUIZO_100_DIGITAL_ID, $selectedPreliminares, true)) {
            $section->addText(
                'Requer a tramitação do processo no Juízo 100% digital, bem como seja intimada a parte requerida para '
                .'manifestar sobre a petição no prazo de 5 dias, sob pena do silêncio entender-se como concordância tácita.',
                $this->styles['normal'],
                $this->styles['indent']
            );
        }

        if (in_array(Topic::GRATUIDADE_JUSTICA_ID, $selectedPreliminares, true)) {
            $section->addText(
                'Requer os benefícios da Assistência Judiciária Gratuita, nos termos da Lei nº 1.060/50 e suas alterações, '
                .'por ser '.($this->isFeminine($data) ? 'a reclamante' : 'o reclamante').' pessoa pobre no sentido legal.',
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
            .'perícia técnica, juntada de documentos e demais provas '
            .'que se fizerem necessárias.',
            $this->styles['normal'],
            $this->styles['indent']
        );
    }

    /**
     * The amount of the claim is the sum of the pedidos' estimates (or the AI's own
     * suggestion when there are none), highlighted in yellow like the estimates.
     *
     * @param  array<string, mixed>  $data
     */
    protected function addValorCausa(Section $section, array $data): void
    {
        $cents = $this->estimatesTotal($data['pedidos'] ?? []);

        if ($cents !== null) {
            $valor = MoneyInWords::formatCents($cents);
            $extenso = MoneyInWords::spell($cents);
        } else {
            $valor = ! empty($data['valor_causa']) ? $data['valor_causa'] : 'R$ 0,00';
            $extenso = $data['valor_causa_extenso'] ?? '';
        }

        $textRun = $section->addTextRun($this->styles['indent']);
        $textRun->addText('Dá à causa o valor de ');
        $textRun->addText($valor.(! empty($extenso) ? " ({$extenso})" : ''), $this->styles['estimate']);
        $textRun->addText(', para os fins legais.');
    }

    /**
     * @param  array<int, mixed>  $pedidos
     */
    protected function estimatesTotal(array $pedidos): ?int
    {
        $amounts = [];

        foreach ($pedidos as $pedido) {
            if (! is_array($pedido)) {
                continue;
            }

            $amounts[] = MoneyInWords::parseCents($pedido['valor'] ?? null);
            foreach ($pedido['subitens'] ?? [] as $subitem) {
                $amounts[] = is_array($subitem) ? MoneyInWords::parseCents($subitem['valor'] ?? null) : null;
            }
        }

        $amounts = array_filter($amounts, fn (?int $cents): bool => $cents !== null);

        return $amounts === [] ? null : array_sum($amounts);
    }

    /** @param  array<string, mixed>  $data */
    protected function addFecho(Section $section, array $data): void
    {
        $section->addText('Nesses termos,', null, $this->styles['tight']);
        $section->addText('Pede deferimento', null, $this->styles['tight']);

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
     * Internal guidance of the topic, highlighted in green for the lawyer to check and remove before filing.
     */
    protected function addInternalNote(Section $section, ?string $note): void
    {
        $note = trim((string) $note);
        if ($note === '') {
            return;
        }

        $textRun = $section->addTextRun($this->styles['internalNote']);
        $textRun->addText('Orientação interna: ', ['bold' => true, 'color' => '375623']);

        foreach (preg_split('/\R/u', $note) as $index => $line) {
            if ($index > 0) {
                $textRun->addTextBreak();
            }
            $textRun->addText($line, ['color' => '375623']);
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
     * The client's gender as inferred from the marital status ("casada", "solteira"...).
     *
     * @param  array<string, mixed>  $data
     */
    protected function isFeminine(array $data): bool
    {
        $estadoCivil = (string) ($data['cliente_estado_civil'] ?? '');

        return $estadoCivil !== '' && mb_substr($estadoCivil, -1) === 'a';
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
