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
     * Form structure: sections with their fields.
     *
     * @var array<int, array{title: string, fields: array<int, array{label: string, key: string, type?: string, options?: array<string, string>}>}>
     */
    protected array $formSections;

    /** @param  array<string, mixed>  $data */
    public function generate(array $data): string
    {
        $this->word = new PhpWord;
        $this->word->setDefaultFontName('Arial');
        $this->word->setDefaultFontSize(10);
        $this->word->setDefaultParagraphStyle([
            'spaceAfter' => Converter::pointToTwip(4),
            'lineHeight' => 1.3,
        ]);

        $this->buildFormStructure();

        $section = $this->word->addSection([
            'marginTop' => Converter::cmToTwip(2.5),
            'marginBottom' => Converter::cmToTwip(2),
            'marginLeft' => Converter::cmToTwip(2.5),
            'marginRight' => Converter::cmToTwip(2),
        ]);

        // Header with logo
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

        // Title
        $section->addText(
            'FORMULÁRIO DE ATENDIMENTO',
            ['bold' => true, 'size' => 14, 'color' => '232A54'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => Converter::pointToTwip(4)]
        );
        $section->addText(
            'Data: '.now()->format('d/m/Y'),
            ['size' => 9, 'color' => '666666'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => Converter::pointToTwip(16)]
        );

        // Render each section
        foreach ($this->formSections as $index => $formSection) {
            $this->addSection($section, $index + 1, $formSection, $data);
        }

        // Pedidos selecionados
        $this->addPedidosSection($section, $data);

        // Assinatura
        $this->addSignature($section);

        $path = tempnam(sys_get_temp_dir(), 'form_').'.docx';
        $writer = IOFactory::createWriter($this->word, 'Word2007');
        $writer->save($path);

        return $path;
    }

    /** @param  array<string, mixed>  $formSection */
    protected function addSection(
        Section $docSection,
        int $number,
        array $formSection,
        array $data
    ): void {
        // Section title
        $docSection->addText(
            $number.'. '.mb_strtoupper($formSection['title']),
            ['bold' => true, 'size' => 10, 'color' => '232A54'],
            [
                'spaceBefore' => Converter::pointToTwip(14),
                'spaceAfter' => Converter::pointToTwip(2),
                'borderBottomSize' => 6,
                'borderBottomColor' => 'C49A6C',
            ]
        );

        foreach ($formSection['fields'] as $field) {
            $value = $this->getFieldValue($data, $field);

            if ($value === '' || $value === null) {
                continue;
            }

            $textRun = $docSection->addTextRun([
                'spaceAfter' => Converter::pointToTwip(3),
                'indentation' => ['left' => Converter::cmToTwip(0.3)],
            ]);
            $textRun->addText($field['label'].': ', ['bold' => true, 'size' => 9, 'color' => '444444']);
            $textRun->addText($value, ['size' => 9, 'color' => '111111']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $field
     */
    protected function getFieldValue(array $data, array $field): ?string
    {
        $key = $field['key'];
        $type = $field['type'] ?? 'text';
        $options = $field['options'] ?? [];

        if ($type === 'radio' || $type === 'select') {
            $raw = $data[$key] ?? null;

            return $raw !== null && $raw !== '' ? ($options[$raw] ?? $raw) : null;
        }

        if ($type === 'checkbox') {
            $raw = $data[$key] ?? [];
            if (! is_array($raw) || empty($raw)) {
                return null;
            }

            $labels = array_map(fn ($v) => $options[$v] ?? $v, $raw);

            return implode(', ', $labels);
        }

        if ($type === 'matrix') {
            $rows = $data[$key] ?? [];
            if (! is_array($rows) || empty($rows)) {
                return null;
            }

            $parts = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $vals = array_filter($row, fn ($v) => $v !== '' && $v !== null);
                if (! empty($vals)) {
                    $parts[] = implode(' | ', $vals);
                }
            }

            return ! empty($parts) ? implode('; ', $parts) : null;
        }

        $raw = $data[$key] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        return (string) $raw;
    }

    /** @param  array<string, mixed>  $data */
    protected function addPedidosSection(Section $section, array $data): void
    {
        $pedidos = $data['pedidos_selecionados'] ?? [];
        if (empty($pedidos) || ! is_array($pedidos)) {
            return;
        }

        $section->addText(
            'PEDIDOS SELECIONADOS',
            ['bold' => true, 'size' => 10, 'color' => '232A54'],
            [
                'spaceBefore' => Converter::pointToTwip(14),
                'spaceAfter' => Converter::pointToTwip(2),
                'borderBottomSize' => 6,
                'borderBottomColor' => 'C49A6C',
            ]
        );

        foreach ($pedidos as $i => $pedido) {
            $title = is_array($pedido) ? ($pedido['title'] ?? $pedido['titulo'] ?? '') : $pedido;
            $section->addText(
                ($i + 1).'. '.$title,
                ['size' => 9, 'color' => '111111'],
                ['indentation' => ['left' => Converter::cmToTwip(0.3)], 'spaceAfter' => Converter::pointToTwip(2)]
            );
        }
    }

    protected function addSignature(Section $section): void
    {
        $section->addTextBreak(1);

        $section->addText(
            'Declaro que as informações acima são verdadeiras e que autorizo o escritório GB Advocacia '
            .'a utilizá-las para fins de elaboração da petição inicial.',
            ['size' => 9, 'italic' => true, 'color' => '666666'],
            ['spaceAfter' => Converter::pointToTwip(24)]
        );

        $table = $section->addTable([
            'width' => 100 * 50,
            'unit' => 'pct',
            'alignment' => Jc::CENTER,
        ]);

        $table->addRow();

        $cell1 = $table->addCell(5000);
        $cell1->addText(
            '______________________________',
            ['size' => 10],
            ['alignment' => Jc::CENTER, 'spaceBefore' => Converter::pointToTwip(24)]
        );
        $cell1->addText(
            'Assinatura do(a) Cliente',
            ['size' => 9, 'color' => '444444'],
            ['alignment' => Jc::CENTER]
        );

        $cell2 = $table->addCell(5000);
        $cell2->addText(
            '______________________________',
            ['size' => 10],
            ['alignment' => Jc::CENTER, 'spaceBefore' => Converter::pointToTwip(24)]
        );
        $cell2->addText(
            'Data',
            ['size' => 9, 'color' => '444444'],
            ['alignment' => Jc::CENTER]
        );
    }

    protected function buildFormStructure(): void
    {
        $this->formSections = [
            [
                'title' => 'Informações do Cliente',
                'fields' => [
                    ['label' => 'Nome Completo', 'key' => 'cliente_nome'],
                    ['label' => 'Profissão', 'key' => 'cliente_profissao'],
                    ['label' => 'Nacionalidade', 'key' => 'cliente_nacionalidade'],
                    ['label' => 'Estado Civil', 'key' => 'cliente_estado_civil'],
                    ['label' => 'Data de Nascimento', 'key' => 'cliente_nascimento'],
                    ['label' => 'RG', 'key' => 'cliente_rg'],
                    ['label' => 'Órgão Expedidor', 'key' => 'cliente_orgao_expedidor'],
                    ['label' => 'CPF', 'key' => 'cliente_cpf'],
                    ['label' => 'PIS', 'key' => 'cliente_pis'],
                    ['label' => 'CTPS', 'key' => 'cliente_ctps'],
                    ['label' => 'Série', 'key' => 'cliente_ctps_serie'],
                    ['label' => 'Nome da Mãe', 'key' => 'cliente_nome_mae'],
                    ['label' => 'CEP', 'key' => 'cliente_cep'],
                    ['label' => 'Endereço', 'key' => 'cliente_endereco'],
                    ['label' => 'Número', 'key' => 'cliente_numero'],
                    ['label' => 'Complemento', 'key' => 'cliente_complemento'],
                    ['label' => 'Bairro', 'key' => 'cliente_bairro'],
                    ['label' => 'Cidade', 'key' => 'cliente_cidade'],
                    ['label' => 'UF', 'key' => 'cliente_uf'],
                ],
            ],
            [
                'title' => 'Contatos',
                'fields' => [
                    ['label' => 'Telefone Residencial', 'key' => 'contato_tel_residencial'],
                    ['label' => 'Celular', 'key' => 'contato_celular'],
                    ['label' => 'Telefone de Terceiro', 'key' => 'contato_tel_terceiro'],
                    ['label' => 'Nome do Terceiro', 'key' => 'contato_nome_terceiro'],
                    ['label' => 'Grau de Parentesco', 'key' => 'contato_grau_parentesco'],
                ],
            ],
            [
                'title' => 'Informações do Empregador',
                'fields' => [
                    ['label' => 'Empregador', 'key' => 'empregador_nome'],
                    ['label' => 'CNPJ', 'key' => 'empregador_cnpj'],
                    ['label' => 'CEP', 'key' => 'empregador_cep'],
                    ['label' => 'Endereço', 'key' => 'empregador_endereco'],
                    ['label' => 'Cidade', 'key' => 'empregador_cidade'],
                    ['label' => 'UF', 'key' => 'empregador_uf'],
                    ['label' => 'Bairro', 'key' => 'empregador_bairro'],
                    ['label' => 'E-mail', 'key' => 'empregador_email'],
                    ['label' => 'Telefone', 'key' => 'empregador_telefone'],
                    ['label' => 'Superior Hierárquico', 'key' => 'empregador_superior'],
                    ['label' => 'Grupo Econômico', 'key' => 'grupo_economico'],
                    ['label' => 'Tomadoras de Serviço', 'key' => 'tomadoras', 'type' => 'matrix'],
                    ['label' => 'Autoriza incluir no polo passivo', 'key' => 'empregador_autoriza_polo', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                ],
            ],
            [
                'title' => 'Urgência na Ação',
                'fields' => [
                    ['label' => 'Motivos de urgência', 'key' => 'urgencia_motivos', 'type' => 'checkbox', 'options' => ['prescricao' => 'Prescrição', 'doenca' => 'Doença', 'desemprego' => 'Desemprego', 'financeiro' => 'Dificuldade financeira', 'outro' => 'Outro']],
                    ['label' => 'Outros motivos', 'key' => 'urgencia_outros'],
                    ['label' => 'Prescrição Bienal', 'key' => 'urgencia_prescricao_bienal'],
                    ['label' => 'Seguro-desemprego ou benefício', 'key' => 'urgencia_seguro_beneficio', 'type' => 'radio', 'options' => ['seguro' => 'Seguro-desemprego', 'beneficio' => 'Benefício', 'nao' => 'Não']],
                    ['label' => 'Qual benefício', 'key' => 'urgencia_qual_beneficio'],
                ],
            ],
            [
                'title' => 'Contrato de Trabalho',
                'fields' => [
                    ['label' => 'Data de Admissão', 'key' => 'contrato_admissao'],
                    ['label' => 'Data de Dispensa', 'key' => 'contrato_dispensa'],
                    ['label' => 'Registro na CTPS', 'key' => 'contrato_registro_ctps', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'parcial' => 'Parcial']],
                    ['label' => 'Modalidade do Contrato', 'key' => 'contrato_modalidade', 'type' => 'radio', 'options' => ['prazo_indeterminado' => 'Prazo indeterminado', 'experiencia' => 'Experiência', 'prazo_determinado' => 'Prazo determinado', 'temporario' => 'Temporário']],
                    ['label' => 'Dias de Experiência', 'key' => 'contrato_dias_experiencia'],
                    ['label' => 'Funções Exercidas', 'key' => 'funcoes', 'type' => 'matrix'],
                    ['label' => 'Vigilante armado/desarmado', 'key' => 'vigilante_armado', 'type' => 'radio', 'options' => ['armado' => 'Armado', 'desarmado' => 'Desarmado', 'nao_aplicavel' => 'N/A']],
                    ['label' => 'Portava colete', 'key' => 'vigilante_colete', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'nao_aplicavel' => 'N/A']],
                ],
            ],
            [
                'title' => 'Local e Jornada de Trabalho',
                'fields' => [
                    ['label' => 'Cidade', 'key' => 'jornada_cidade'],
                    ['label' => 'UF', 'key' => 'jornada_uf'],
                    ['label' => 'Empresa / Local', 'key' => 'jornada_empresa_local'],
                    ['label' => 'Tomadoras de Serviço (Jornada)', 'key' => 'jornada_tomadoras', 'type' => 'matrix'],
                    ['label' => 'Horário Contratual', 'key' => 'jornada_horario_contratual'],
                    ['label' => 'Horário Realizado', 'key' => 'jornada_horario_realizado', 'type' => 'checkbox', 'options' => ['igual_contratual' => 'Igual ao contratual', 'horas_extras' => 'Com horas extras', 'variavel' => 'Variável', 'outro' => 'Outro']],
                    ['label' => 'Outros horários', 'key' => 'jornada_horario_outros'],
                    ['label' => 'Saía mais cedo ou ficava mais tarde', 'key' => 'jornada_mais_cedo_tarde', 'type' => 'radio', 'options' => ['mais_cedo' => 'Mais cedo', 'mais_tarde' => 'Mais tarde', 'ambos' => 'Ambos', 'nao' => 'Não']],
                    ['label' => 'Minutos além da jornada', 'key' => 'jornada_minutos_extras'],
                    ['label' => 'Vezes por semana', 'key' => 'jornada_vezes_semana'],
                ],
            ],
            [
                'title' => 'Jornada – Domingos, Feriados e Intervalo',
                'fields' => [
                    ['label' => 'Trabalhava aos domingos', 'key' => 'jornada_domingos', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'as_vezes' => 'Às vezes']],
                    ['label' => 'Trabalhava em feriados', 'key' => 'jornada_feriados', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'as_vezes' => 'Às vezes']],
                    ['label' => 'Frequência (domingos/feriados)', 'key' => 'jornada_frequencia'],
                    ['label' => 'Dia(s) de folga', 'key' => 'jornada_folga_dias'],
                    ['label' => 'Folga no domingo do mês', 'key' => 'jornada_domingo_mes', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Trabalhava 6 dias consecutivos', 'key' => 'jornada_6_dias', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Dias consecutivos', 'key' => 'jornada_dias_consecutivos'],
                    ['label' => 'Intervalo de refeição', 'key' => 'jornada_intervalo_refeicao'],
                    ['label' => 'Intervalo inferior ao legal', 'key' => 'jornada_intervalo_inferior', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Vezes por semana (intervalo inferior)', 'key' => 'jornada_intervalo_vezes_semana'],
                ],
            ],
            [
                'title' => 'Cartão de Ponto',
                'fields' => [
                    ['label' => 'Tinha controle de ponto', 'key' => 'ponto_tem', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Tipo de ponto', 'key' => 'ponto_tipo', 'type' => 'checkbox', 'options' => ['papel' => 'Papel', 'biometrico' => 'Biométrico', 'eletronico' => 'Eletrônico', 'aplicativo' => 'Aplicativo', 'outro' => 'Outro']],
                    ['label' => 'Celular pessoal ou da empresa', 'key' => 'ponto_celular', 'type' => 'radio', 'options' => ['pessoal' => 'Pessoal', 'empresa' => 'Da empresa', 'nao_aplicavel' => 'N/A']],
                    ['label' => 'Mais de 20 funcionários', 'key' => 'ponto_mais_20_func', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'nao_sabe' => 'Não sabe']],
                    ['label' => 'Ponto registrava jornada verdadeira', 'key' => 'ponto_verdadeiro', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Havia alteração dos cartões', 'key' => 'ponto_alteracao', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                ],
            ],
            [
                'title' => 'Salário e Remuneração',
                'fields' => [
                    ['label' => 'Último Salário', 'key' => 'salario_ultimo'],
                    ['label' => 'Recebia pagamento por fora', 'key' => 'salario_por_fora', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Valor pago por fora', 'key' => 'salario_valor_por_fora'],
                    ['label' => 'Forma de pagamento', 'key' => 'salario_forma_pagamento', 'type' => 'checkbox', 'options' => ['dinheiro' => 'Dinheiro', 'deposito' => 'Depósito bancário', 'pix' => 'PIX', 'cheque' => 'Cheque']],
                    ['label' => 'Provas do pagamento por fora', 'key' => 'salario_prova_por_fora'],
                    ['label' => 'Recebia comissões', 'key' => 'comissoes_recebia', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Valor / Percentual comissão', 'key' => 'comissoes_valor'],
                ],
            ],
            [
                'title' => 'Transferência',
                'fields' => [
                    ['label' => 'Foi transferido', 'key' => 'transferencia_foi', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Data da Transferência', 'key' => 'transferencia_data'],
                    ['label' => 'Motivo', 'key' => 'transferencia_motivo'],
                    ['label' => 'Implicou mudança de endereço', 'key' => 'transferencia_mudanca_endereco', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Provisória ou definitiva', 'key' => 'transferencia_tipo', 'type' => 'radio', 'options' => ['provisoria' => 'Provisória', 'definitiva' => 'Definitiva']],
                    ['label' => 'Empresa arcou com despesas', 'key' => 'transferencia_despesas', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'parcial' => 'Parcialmente']],
                    ['label' => 'Detalhes adicionais', 'key' => 'transferencia_detalhes'],
                ],
            ],
            [
                'title' => 'Trabalho Fora de Ipatinga',
                'fields' => [
                    ['label' => 'Trabalhou fora de Ipatinga', 'key' => 'fora_ipatinga', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Localidade', 'key' => 'fora_ipatinga_localidade'],
                    ['label' => 'Contrato celebrado por telefone', 'key' => 'fora_ipatinga_contrato_telefone', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                ],
            ],
            [
                'title' => 'Insalubridade e Periculosidade',
                'fields' => [
                    ['label' => 'Exposto a agentes nocivos', 'key' => 'insalubridade_exposto', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Agentes nocivos', 'key' => 'insalubridade_agentes', 'type' => 'checkbox', 'options' => ['ruido' => 'Ruído', 'calor' => 'Calor', 'quimico' => 'Químico', 'biologico' => 'Biológico', 'eletricidade' => 'Eletricidade', 'explosivos' => 'Explosivos/Inflamáveis', 'outro' => 'Outro']],
                    ['label' => 'Outros agentes', 'key' => 'insalubridade_outros_agentes'],
                    ['label' => 'Quantidade de banheiros', 'key' => 'insalubridade_qtd_banheiro'],
                    ['label' => 'Recebia adicional', 'key' => 'insalubridade_adicional', 'type' => 'radio', 'options' => ['insalubridade' => 'Insalubridade', 'periculosidade' => 'Periculosidade', 'nao' => 'Não recebia']],
                ],
            ],
            [
                'title' => 'EPIs',
                'fields' => [
                    ['label' => 'EPI era necessário', 'key' => 'epi_necessario', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'EPIs recebidos', 'key' => 'epi_recebeu', 'type' => 'checkbox', 'options' => ['luvas' => 'Luvas', 'capacete' => 'Capacete', 'bota' => 'Bota', 'protetor_auricular' => 'Protetor auricular', 'oculos' => 'Óculos', 'mascara' => 'Máscara', 'outro' => 'Outro']],
                    ['label' => 'Outros EPIs', 'key' => 'epi_outros'],
                    ['label' => 'Recebeu treinamento para uso', 'key' => 'epi_treinamento', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'EPIs substituídos quando necessário', 'key' => 'epi_substituidos', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Usava os EPIs corretamente', 'key' => 'epi_usava', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Empresa fiscalizava o uso', 'key' => 'epi_fiscalizava', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                ],
            ],
            [
                'title' => 'Acidente de Trabalho',
                'fields' => [
                    ['label' => 'Sofreu acidente de trabalho', 'key' => 'acidente_sofreu', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Detalhes do acidente', 'key' => 'acidente_detalhes'],
                    ['label' => 'Data do acidente', 'key' => 'acidente_data'],
                    ['label' => 'CAT emitida', 'key' => 'acidente_cat', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Quem emitiu a CAT', 'key' => 'acidente_cat_emissor', 'type' => 'radio', 'options' => ['empresa' => 'Empresa', 'sindicato' => 'Sindicato', 'medico' => 'Médico', 'autoridade' => 'Autoridade pública', 'nao_emitida' => 'Não foi emitida']],
                ],
            ],
            [
                'title' => 'Problemas de Saúde Relacionados ao Trabalho',
                'fields' => [
                    ['label' => 'Tem problema de saúde relacionado', 'key' => 'saude_problema', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Quais problemas', 'key' => 'saude_quais'],
                    ['label' => 'Como se desenvolveu', 'key' => 'saude_desenvolvimento'],
                    ['label' => 'Período', 'key' => 'saude_periodo'],
                    ['label' => 'Possui relatório médico', 'key' => 'saude_relatorio', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Situações pessoais contribuíram', 'key' => 'saude_situacoes_pessoais', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Quais situações pessoais', 'key' => 'saude_situacoes_pessoais_quais'],
                    ['label' => 'Empresa ciente do problema', 'key' => 'saude_empresa_ciente', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Permanece com o problema', 'key' => 'saude_permanece', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Tipo de tratamento', 'key' => 'saude_tratamento', 'type' => 'checkbox', 'options' => ['medico' => 'Médico', 'fisioterapia' => 'Fisioterapia', 'psicologo' => 'Psicólogo', 'cirurgia' => 'Cirurgia', 'nenhum' => 'Nenhum']],
                    ['label' => 'Faz uso de medicamentos', 'key' => 'saude_medicamentos', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Quais medicamentos', 'key' => 'saude_medicamentos_quais'],
                ],
            ],
            [
                'title' => 'Afastamento do Trabalho',
                'fields' => [
                    ['label' => 'Foi afastado do trabalho', 'key' => 'afastamento_foi', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Motivo do afastamento', 'key' => 'afastamento_motivo'],
                    ['label' => 'Data de início', 'key' => 'afastamento_data_inicio'],
                    ['label' => 'Data de fim', 'key' => 'afastamento_data_fim'],
                    ['label' => 'Senha Gov.br', 'key' => 'afastamento_senha_gov'],
                ],
            ],
            [
                'title' => 'FGTS',
                'fields' => [
                    ['label' => 'Empresa recolhia o FGTS', 'key' => 'fgts_recolhia', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'parcial' => 'Parcialmente', 'nao_sabe' => 'Não sabe']],
                ],
            ],
            [
                'title' => 'Equiparação Salarial',
                'fields' => [
                    ['label' => 'Colega com salário superior', 'key' => 'equiparacao_colega', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Nome do colega paradigma', 'key' => 'equiparacao_nome_colega'],
                    ['label' => 'Função do colega', 'key' => 'equiparacao_funcao_colega'],
                    ['label' => 'Atividades exercidas pelo colega', 'key' => 'equiparacao_atividades'],
                    ['label' => 'Atividades idênticas', 'key' => 'equiparacao_identicas', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Diferença superior a 2 anos', 'key' => 'equiparacao_2_anos', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                ],
            ],
            [
                'title' => 'Desvio e Acúmulo de Função',
                'fields' => [
                    ['label' => 'Houve desvio de função', 'key' => 'desvio_funcao', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Atividades em desvio de função', 'key' => 'desvio_atividades'],
                    ['label' => 'Período início', 'key' => 'desvio_periodo_de'],
                    ['label' => 'Período fim', 'key' => 'desvio_periodo_ate'],
                    ['label' => 'Houve acúmulo de função', 'key' => 'acumulo_funcao', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Atividades acumuladas', 'key' => 'acumulo_atividades'],
                ],
            ],
            [
                'title' => 'Extinção do Contrato de Trabalho',
                'fields' => [
                    ['label' => 'Causa da extinção', 'key' => 'extincao_causa', 'type' => 'radio', 'options' => ['sem_justa_causa' => 'Dispensa sem justa causa', 'justa_causa' => 'Dispensa por justa causa', 'pedido_demissao' => 'Pedido de demissão', 'rescisao_indireta' => 'Rescisão indireta', 'comum_acordo' => 'Comum acordo', 'termino_contrato' => 'Término de contrato']],
                    ['label' => 'Forma de comunicação do término', 'key' => 'extincao_termino', 'type' => 'checkbox', 'options' => ['pessoalmente' => 'Pessoalmente', 'carta' => 'Por carta', 'whatsapp' => 'WhatsApp', 'email' => 'E-mail', 'telefone' => 'Telefone']],
                    ['label' => 'Dias de experiência restantes', 'key' => 'extincao_dias_experiencia'],
                    ['label' => 'Forma acordada de rescisão', 'key' => 'extincao_forma_acordada'],
                    ['label' => 'Recebeu verbas rescisórias', 'key' => 'extincao_verbas_certas', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'parcial' => 'Parcialmente']],
                    ['label' => 'Data de pagamento das verbas', 'key' => 'extincao_data_pagamento'],
                    ['label' => 'Valor total pago', 'key' => 'extincao_valor_pago'],
                    ['label' => 'Forma de pagamento das verbas', 'key' => 'extincao_forma_pagamento', 'type' => 'checkbox', 'options' => ['dinheiro' => 'Dinheiro', 'deposito' => 'Depósito bancário', 'pix' => 'PIX', 'cheque' => 'Cheque']],
                    ['label' => 'Data de entrega das guias', 'key' => 'extincao_data_entrega_guias'],
                    ['label' => 'Há erros nas guias', 'key' => 'extincao_erro_guias', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Quais erros', 'key' => 'extincao_quais_erros'],
                    ['label' => 'Guias recebidas', 'key' => 'extincao_guias', 'type' => 'checkbox', 'options' => ['fgts' => 'FGTS', 'seguro_desemprego' => 'Seguro-desemprego', 'ctps' => 'CTPS anotada', 'termo_rescisao' => 'Termo de rescisão']],
                    ['label' => 'Solicitou seguro-desemprego', 'key' => 'extincao_solicitou_seguro', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não', 'pendente' => 'Pendente']],
                ],
            ],
            [
                'title' => 'Testemunhas e Ações Anteriores',
                'fields' => [
                    ['label' => 'Testemunhas', 'key' => 'testemunhas_nomes'],
                    ['label' => 'Ação trabalhista anterior', 'key' => 'acao_anterior', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Data da ação anterior', 'key' => 'acao_anterior_data'],
                    ['label' => 'Número do processo', 'key' => 'acao_anterior_processo'],
                    ['label' => 'Motivos da ação anterior', 'key' => 'acao_anterior_motivos'],
                    ['label' => 'Testemunha da defesa (nome)', 'key' => 'testemunha_defesa_nome'],
                    ['label' => 'Testemunha da defesa (contato)', 'key' => 'testemunha_defesa_contato'],
                ],
            ],
            [
                'title' => 'Benefícios da Convenção Coletiva',
                'fields' => [
                    ['label' => 'Benefícios recebidos', 'key' => 'beneficios', 'type' => 'checkbox', 'options' => ['lanche' => 'Lanche', 'vale_combustivel' => 'Vale combustível', 'abono' => 'Abono', 'vale_alimentacao' => 'Vale alimentação', 'plano_saude' => 'Plano de saúde', 'transporte' => 'Vale transporte']],
                    ['label' => 'Composição do lanche', 'key' => 'beneficio_lanche_composicao'],
                    ['label' => 'Valor do vale combustível', 'key' => 'beneficio_vale_combustivel_valor'],
                    ['label' => 'Valor do abono', 'key' => 'beneficio_abono_valor'],
                    ['label' => 'Meio de recebimento do VA', 'key' => 'beneficio_vale_alimentacao_meio', 'type' => 'checkbox', 'options' => ['cartao' => 'Cartão', 'dinheiro' => 'Dinheiro', 'cheque' => 'Cheque']],
                    ['label' => 'Valor do vale alimentação', 'key' => 'beneficio_vale_alimentacao_valor'],
                    ['label' => 'Participou da homologação', 'key' => 'beneficio_homologacao', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Outros benefícios', 'key' => 'beneficio_outros'],
                ],
            ],
            [
                'title' => 'Salário Família',
                'fields' => [
                    ['label' => 'Filhos menores de 14 anos', 'key' => 'salario_familia_filhos', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Nomes dos filhos', 'key' => 'salario_familia_nomes'],
                    ['label' => 'Idades', 'key' => 'salario_familia_idades'],
                    ['label' => 'Recebeu salário família', 'key' => 'salario_familia_recebeu', 'type' => 'radio', 'options' => ['sim' => 'Sim', 'nao' => 'Não']],
                    ['label' => 'Valor recebido', 'key' => 'salario_familia_valor'],
                ],
            ],
            [
                'title' => 'Outras Informações',
                'fields' => [
                    ['label' => 'Informações adicionais', 'key' => 'outras_informacoes'],
                ],
            ],
            [
                'title' => 'Relação de Documentos',
                'fields' => [
                    ['label' => 'Documentos recebidos', 'key' => 'documentos_recebidos'],
                ],
            ],
        ];
    }
}
