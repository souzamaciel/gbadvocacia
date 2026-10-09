<?php

namespace App\Services;

/**
 * Serializes the legal attendance form into two simultaneous outputs:
 *
 *   A) Structured JSON  → persistence in the database
 *   B) Semantic HTML    → prompt context for the OpenAI API
 *
 * --- Contracts ---
 *
 * INPUT  normalizeFormData(array $raw, array $topicos)
 *   $raw     : Laravel $request->all()  (or any flat associative array)
 *   $topicos : enriched list already resolved by the controller, shape:
 *              [['sort_order' => int, 'topic_id' => int, 'title' => string, 'content' => string], ...]
 *
 * OUTPUT buildPetitionFormJson(array $normalized) : array
 * {
 *   "sections": [
 *     {
 *       "key":    "cliente",
 *       "title":  "Informações do Cliente",
 *       "fields": {
 *         "cliente_nome": { "label": "Nome Completo", "type": "scalar", "value": "João Silva" },
 *         "urgencia_motivos": { "label": "Motivos de urgência", "type": "list", "value": ["prescricao"] },
 *         "funcoes": { "label": "Funções Exercidas", "type": "matrix", "value": [{"nome":"…","de":"…","ate":"…"}] }
 *       }
 *     }
 *   ],
 *   "serialized_at": "2026-03-19T10:00:00+00:00"
 * }
 *
 * OUTPUT buildPetitionFormHtml(array $normalized) : string
 *   Semantic HTML with <section>, <h2>, <p>, <ul>/<ol>, <strong>.
 *   Empty / null fields are omitted entirely.
 *   Ready for direct injection into an OpenAI prompt.
 *
 * --- DB suggestion ---
 *   Table `atendimentos`:
 *     id            BIGINT PK
 *     form_json     JSON     → cast to array on the model
 *     form_html     LONGTEXT → pre-built HTML for AI prompts
 *     created_at / updated_at
 */
class PetitionFormSerializer
{
    // ──────────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Step 1 – normalize raw request data against the schema.
     *
     * Rules:
     *   scalar  → string|null  (trimmed; empty string becomes null)
     *   list    → string[]     (array of non-empty strings; missing key → [])
     *   matrix  → array[]      (rows with all-null values are discarded)
     *   pedidos → passed through from $topicos
     *
     * @param  array<string, mixed>  $raw
     * @param  array<int, array{sort_order: int, topic_id: int, title: string, content: string}>  $topicos
     * @param  array<int, array{sort_order: int, topic_id: int, title: string, content: string}>  $preliminares
     * @return array<string, mixed>
     */
    public static function normalizeFormData(array $raw, array $topicos = [], array $preliminares = []): array
    {
        $normalized = [];

        foreach (self::schema() as $section) {
            foreach ($section['fields'] as $key => $definition) {
                $normalized[$key] = match ($definition['type']) {
                    'scalar' => self::normalizeScalar($raw[$key] ?? null),
                    'list' => self::normalizeList($raw[$key] ?? []),
                    'matrix' => self::normalizeMatrix($raw[$key] ?? [], array_keys($definition['fields'])),
                    'pedidos' => $topicos,
                    'preliminares' => $preliminares,
                    default => null,
                };
            }
        }

        return $normalized;
    }

    /**
     * Step 2A – build the structured JSON payload for DB persistence.
     *
     * @param  array<string, mixed>  $normalized  output of normalizeFormData()
     * @return array<string, mixed>
     */
    public static function buildPetitionFormJson(array $normalized): array
    {
        $sections = [];

        foreach (self::schema() as $sectionKey => $section) {
            $fields = [];

            foreach ($section['fields'] as $key => $definition) {
                $fields[$key] = [
                    'label' => $definition['label'],
                    'type' => $definition['type'],
                    'value' => $normalized[$key] ?? ($definition['type'] === 'list' ? [] : null),
                ];
            }

            $sections[] = [
                'key' => $sectionKey,
                'title' => $section['title'],
                'fields' => $fields,
            ];
        }

        return [
            'sections' => $sections,
            'serialized_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Step 2B – build the semantic HTML for AI prompts.
     *
     * Sections with no filled fields are omitted entirely.
     * Values are HTML-escaped. Newlines in textareas become <br>.
     *
     * @param  array<string, mixed>  $normalized  output of normalizeFormData()
     */
    public static function buildPetitionFormHtml(array $normalized): string
    {
        $html = '';

        foreach (self::schema() as $section) {
            $body = '';

            foreach ($section['fields'] as $key => $definition) {
                $value = $normalized[$key] ?? null;
                $label = $definition['label'];

                $body .= match ($definition['type']) {
                    'scalar' => self::htmlScalar($label, $value),
                    'list' => self::htmlList($label, $value),
                    'matrix' => self::htmlMatrix($label, $value, $definition['fields']),
                    'pedidos' => self::htmlPedidos($label, $value),
                    'preliminares' => self::htmlPreliminares($label, $value),
                    default => '',
                };
            }

            if ($body !== '') {
                $html .= "<section>\n  <h2>".e($section['title'])."</h2>\n".$body."</section>\n\n";
            }
        }

        return $html;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // NORMALIZATION HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private static function normalizeScalar(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * @return array<string>
     */
    private static function normalizeList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(
            array_filter(
                array_map(fn ($v) => is_string($v) ? trim($v) : '', $value),
                fn ($v) => $v !== ''
            )
        );
    }

    /**
     * @param  array<string>  $fields
     * @return array<int, array<string, string|null>>
     */
    private static function normalizeMatrix(mixed $rows, array $fields): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $result = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized = [];

            foreach ($fields as $field) {
                $normalized[$field] = self::normalizeScalar($row[$field] ?? null);
            }

            if (array_filter($normalized) !== []) {
                $result[] = $normalized;
            }
        }

        return $result;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HTML HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private static function htmlScalar(string $label, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return '  <p><strong>'.e($label).':</strong> '.nl2br(e($value))."</p>\n";
    }

    /**
     * @param  array<string>|null  $value
     */
    private static function htmlList(string $label, mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        $items = implode('', array_map(fn ($v) => '    <li>'.e($v)."</li>\n", $value));

        return '  <p><strong>'.e($label).":</strong></p>\n  <ul>\n".$items."  </ul>\n";
    }

    /**
     * @param  array<int, array<string, string|null>>|null  $rows
     * @param  array<string, string>  $subFields  key → label
     */
    private static function htmlMatrix(string $label, mixed $rows, array $subFields): string
    {
        if (empty($rows)) {
            return '';
        }

        $items = '';

        foreach ($rows as $row) {
            $parts = [];

            foreach ($subFields as $key => $subLabel) {
                if (! empty($row[$key])) {
                    $parts[] = $subLabel.': '.$row[$key];
                }
            }

            if ($parts !== []) {
                $items .= '    <li>'.e(implode(' | ', $parts))."</li>\n";
            }
        }

        if ($items === '') {
            return '';
        }

        return '  <p><strong>'.e($label).":</strong></p>\n  <ul>\n".$items."  </ul>\n";
    }

    /**
     * @param  array<int, array{sort_order: int, topic_id: int, title: string, content: string}>|null  $pedidos
     */
    private static function htmlPedidos(string $label, mixed $pedidos): string
    {
        if (empty($pedidos)) {
            return '';
        }

        $items = implode('', array_map(
            fn ($p) => '    <li>'.e($p['title'])."</li>\n",
            $pedidos
        ));

        return '  <p><strong>'.e($label).":</strong></p>\n  <ol>\n".$items."  </ol>\n";
    }

    /**
     * @param  array<int, array{sort_order: int, topic_id: int, title: string, content: string}>|null  $preliminares
     */
    private static function htmlPreliminares(string $label, mixed $preliminares): string
    {
        if (empty($preliminares)) {
            return '';
        }

        $items = implode('', array_map(
            fn ($p) => '    <li>'.e($p['title'])."</li>\n",
            $preliminares
        ));

        return '  <p><strong>'.e($label).":</strong></p>\n  <ul>\n".$items."  </ul>\n";
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SCHEMA  (single source of truth)
    //
    // Each section has:
    //   title  → human-readable section name
    //   fields → key (= HTML name attr without []) => { label, type, fields? }
    //
    // Field types:
    //   scalar  → text / date / radio / textarea
    //   list    → checkbox[] (multiple values)
    //   matrix  → repeatable group (funcoes, tomadoras, etc.)
    //              'fields' sub-key maps column key → column label
    //   pedidos → special type for ordered topico list
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, array{title: string, fields: array<string, array{label: string, type: string, fields?: array<string, string>}>}>
     */
    public static function schema(): array
    {
        return [
            'cliente' => [
                'title' => 'Informações do Cliente',
                'fields' => [
                    'cliente_nome' => ['label' => 'Nome Completo',       'type' => 'scalar'],
                    'cliente_profissao' => ['label' => 'Profissão',           'type' => 'scalar'],
                    'cliente_nacionalidade' => ['label' => 'Nacionalidade',       'type' => 'scalar'],
                    'cliente_estado_civil' => ['label' => 'Estado Civil',        'type' => 'scalar'],
                    'cliente_nascimento' => ['label' => 'Data de Nascimento',  'type' => 'scalar'],
                    'cliente_rg' => ['label' => 'RG',                 'type' => 'scalar'],
                    'cliente_orgao_expedidor' => ['label' => 'Órgão Expedidor',     'type' => 'scalar'],
                    'cliente_cpf' => ['label' => 'CPF',                'type' => 'scalar'],
                    'cliente_pis' => ['label' => 'PIS',                'type' => 'scalar'],
                    'cliente_ctps' => ['label' => 'CTPS',               'type' => 'scalar'],
                    'cliente_ctps_serie' => ['label' => 'Série da CTPS',      'type' => 'scalar'],
                    'cliente_nome_mae' => ['label' => 'Nome da Mãe',        'type' => 'scalar'],
                    'cliente_cep' => ['label' => 'CEP',                'type' => 'scalar'],
                    'cliente_endereco' => ['label' => 'Endereço',           'type' => 'scalar'],
                    'cliente_numero' => ['label' => 'Número',             'type' => 'scalar'],
                    'cliente_complemento' => ['label' => 'Complemento',        'type' => 'scalar'],
                    'cliente_bairro' => ['label' => 'Bairro',             'type' => 'scalar'],
                    'cliente_cidade' => ['label' => 'Cidade',             'type' => 'scalar'],
                    'cliente_uf' => ['label' => 'UF',                 'type' => 'scalar'],
                ],
            ],

            'contatos' => [
                'title' => 'Contatos',
                'fields' => [
                    'contato_tel_residencial' => ['label' => 'Telefone Residencial',  'type' => 'scalar'],
                    'contato_celular' => ['label' => 'Celular',               'type' => 'scalar'],
                    'contato_tel_terceiro' => ['label' => 'Telefone de Terceiro',  'type' => 'scalar'],
                    'contato_nome_terceiro' => ['label' => 'Nome do Terceiro',      'type' => 'scalar'],
                    'contato_grau_parentesco' => ['label' => 'Grau de Parentesco',    'type' => 'scalar'],
                ],
            ],

            'empregador' => [
                'title' => 'Informações do Empregador',
                'fields' => [
                    'empregador_nome' => ['label' => 'Empregador',                          'type' => 'scalar'],
                    'empregador_cnpj' => ['label' => 'CNPJ',                                'type' => 'scalar'],
                    'empregador_cep' => ['label' => 'CEP',                                 'type' => 'scalar'],
                    'empregador_endereco' => ['label' => 'Endereço',                            'type' => 'scalar'],
                    'empregador_cidade' => ['label' => 'Cidade',                              'type' => 'scalar'],
                    'empregador_uf' => ['label' => 'UF',                                  'type' => 'scalar'],
                    'empregador_bairro' => ['label' => 'Bairro',                              'type' => 'scalar'],
                    'empregador_email' => ['label' => 'E-mail',                              'type' => 'scalar'],
                    'empregador_telefone' => ['label' => 'Telefone',                            'type' => 'scalar'],
                    'empregador_superior' => ['label' => 'Superior Hierárquico',                'type' => 'scalar'],
                    'grupo_economico' => ['label' => 'Empresas do Grupo Econômico',         'type' => 'scalar'],
                    'tomadoras' => ['label' => 'Tomadoras de Serviço',                'type' => 'matrix',
                        'fields' => ['nome' => 'Nome', 'cnpj' => 'CNPJ']],
                    'empregador_autoriza_polo' => ['label' => 'Autoriza incluir no polo passivo?',   'type' => 'scalar'],
                ],
            ],

            'urgencia' => [
                'title' => 'Urgência na Ação',
                'fields' => [
                    'urgencia_motivos' => ['label' => 'Motivos de urgência',                   'type' => 'list'],
                    'urgencia_outros' => ['label' => 'Outros motivos',                         'type' => 'scalar'],
                    'urgencia_prescricao_bienal' => ['label' => 'Prescrição Bienal',                     'type' => 'scalar'],
                    'urgencia_seguro_beneficio' => ['label' => 'Possui seguro-desemprego ou benefício?', 'type' => 'scalar'],
                    'urgencia_qual_beneficio' => ['label' => 'Qual benefício?',                        'type' => 'scalar'],
                ],
            ],

            'contrato' => [
                'title' => 'Contrato de Trabalho',
                'fields' => [
                    'contrato_admissao' => ['label' => 'Data de Admissão',      'type' => 'scalar'],
                    'contrato_dispensa' => ['label' => 'Data de Dispensa',      'type' => 'scalar'],
                    'contrato_registro_ctps' => ['label' => 'Registro na CTPS?',     'type' => 'scalar'],
                    'contrato_modalidade' => ['label' => 'Modalidade do Contrato', 'type' => 'scalar'],
                    'contrato_dias_experiencia' => ['label' => 'Dias de Experiência',   'type' => 'scalar'],
                    'funcoes' => ['label' => 'Funções Exercidas',      'type' => 'matrix',
                        'fields' => ['nome' => 'Função', 'de' => 'De', 'ate' => 'Até']],
                    'vigilante_armado' => ['label' => 'Armado ou desarmado?',  'type' => 'scalar'],
                    'vigilante_colete' => ['label' => 'Portava colete?',        'type' => 'scalar'],
                ],
            ],

            'jornada' => [
                'title' => 'Local e Jornada de Trabalho',
                'fields' => [
                    'jornada_cidade' => ['label' => 'Cidade de trabalho',                   'type' => 'scalar'],
                    'jornada_uf' => ['label' => 'UF',                                   'type' => 'scalar'],
                    'jornada_empresa_local' => ['label' => 'Empresa / Local',                      'type' => 'scalar'],
                    'jornada_tomadoras' => ['label' => 'Tomadoras de Serviço',                 'type' => 'matrix',
                        'fields' => ['nome' => 'Nome', 'cnpj' => 'CNPJ']],
                    'jornada_horario_contratual' => ['label' => 'Horário Contratual',                   'type' => 'scalar'],
                    'jornada_horario_realizado' => ['label' => 'Horário Realizado',                    'type' => 'list'],
                    'jornada_horario_outros' => ['label' => 'Outros horários',                      'type' => 'scalar'],
                    'jornada_mais_cedo_tarde' => ['label' => 'Saía mais cedo ou ficava mais tarde?', 'type' => 'scalar'],
                    'jornada_minutos_extras' => ['label' => 'Minutos além da jornada',              'type' => 'scalar'],
                    'jornada_vezes_semana' => ['label' => 'Vezes por semana',                     'type' => 'scalar'],
                ],
            ],

            'jornada_domingos' => [
                'title' => 'Jornada – Domingos, Feriados e Intervalo',
                'fields' => [
                    'jornada_domingos' => ['label' => 'Trabalhava aos domingos?',         'type' => 'scalar'],
                    'jornada_feriados' => ['label' => 'Trabalhava em feriados?',          'type' => 'scalar'],
                    'jornada_frequencia' => ['label' => 'Frequência (domingos/feriados)',    'type' => 'scalar'],
                    'jornada_folga_dias' => ['label' => 'Dia(s) de folga',                  'type' => 'scalar'],
                    'jornada_domingo_mes' => ['label' => 'Folga no domingo do mês?',         'type' => 'scalar'],
                    'jornada_6_dias' => ['label' => 'Trabalhava 6 dias consecutivos?',  'type' => 'scalar'],
                    'jornada_dias_consecutivos' => ['label' => 'Quantos dias consecutivos?',       'type' => 'scalar'],
                    'jornada_intervalo_refeicao' => ['label' => 'Intervalo de refeição',            'type' => 'scalar'],
                    'jornada_intervalo_inferior' => ['label' => 'Intervalo inferior ao legal?',     'type' => 'scalar'],
                    'jornada_intervalo_vezes_semana' => ['label' => 'Quantas vezes por semana?',        'type' => 'scalar'],
                ],
            ],

            'ponto' => [
                'title' => 'Cartão de Ponto',
                'fields' => [
                    'ponto_tem' => ['label' => 'Tinha controle de ponto?',              'type' => 'scalar'],
                    'ponto_tipo' => ['label' => 'Tipo de ponto',                          'type' => 'list'],
                    'ponto_celular' => ['label' => 'Celular pessoal ou da empresa?',         'type' => 'scalar'],
                    'ponto_mais_20_func' => ['label' => 'Empresa com mais de 20 funcionários?',  'type' => 'scalar'],
                    'ponto_verdadeiro' => ['label' => 'Ponto registrava a jornada verdadeira?', 'type' => 'scalar'],
                    'ponto_alteracao' => ['label' => 'Havia alteração dos cartões de ponto?',  'type' => 'scalar'],
                ],
            ],

            'salario' => [
                'title' => 'Salário e Remuneração',
                'fields' => [
                    'salario_ultimo' => ['label' => 'Último Salário',                   'type' => 'scalar'],
                    'salario_por_fora' => ['label' => 'Recebia pagamento "por fora"?',     'type' => 'scalar'],
                    'salario_valor_por_fora' => ['label' => 'Valor pago "por fora"',            'type' => 'scalar'],
                    'salario_forma_pagamento' => ['label' => 'Forma de pagamento',                'type' => 'list'],
                    'salario_prova_por_fora' => ['label' => 'Provas do pagamento "por fora"',   'type' => 'scalar'],
                    'comissoes_recebia' => ['label' => 'Recebia comissões?',                'type' => 'scalar'],
                    'comissoes_valor' => ['label' => 'Valor / Percentual das comissões',  'type' => 'scalar'],
                ],
            ],

            'transferencia' => [
                'title' => 'Transferência',
                'fields' => [
                    'transferencia_foi' => ['label' => 'Foi transferido?',                  'type' => 'scalar'],
                    'transferencia_data' => ['label' => 'Data da Transferência',             'type' => 'scalar'],
                    'transferencia_motivo' => ['label' => 'Motivo da transferência',           'type' => 'scalar'],
                    'transferencia_mudanca_endereco' => ['label' => 'Implicou mudança de endereço?',     'type' => 'scalar'],
                    'transferencia_tipo' => ['label' => 'Provisória ou definitiva?',         'type' => 'scalar'],
                    'transferencia_despesas' => ['label' => 'Empresa arcou com as despesas?',    'type' => 'scalar'],
                    'transferencia_detalhes' => ['label' => 'Detalhes adicionais',               'type' => 'scalar'],
                ],
            ],

            'fora_ipatinga' => [
                'title' => 'Trabalho Fora de Ipatinga',
                'fields' => [
                    'fora_ipatinga' => ['label' => 'Trabalhou fora de Ipatinga?',       'type' => 'scalar'],
                    'fora_ipatinga_localidade' => ['label' => 'Localidade',                       'type' => 'scalar'],
                    'fora_ipatinga_contrato_telefone' => ['label' => 'Contrato celebrado por telefone?', 'type' => 'scalar'],
                ],
            ],

            'insalubridade' => [
                'title' => 'Insalubridade e Periculosidade',
                'fields' => [
                    'insalubridade_exposto' => ['label' => 'Estava exposto a agentes nocivos?',                'type' => 'scalar'],
                    'insalubridade_agentes' => ['label' => 'Agentes nocivos',                                  'type' => 'list'],
                    'insalubridade_outros_agentes' => ['label' => 'Outros agentes',                                   'type' => 'scalar'],
                    'insalubridade_qtd_banheiro' => ['label' => 'Quantidade de banheiros',                          'type' => 'scalar'],
                    'insalubridade_adicional' => ['label' => 'Recebia adicional de insalubridade/periculosidade?', 'type' => 'scalar'],
                ],
            ],

            'epi' => [
                'title' => 'EPIs',
                'fields' => [
                    'epi_necessario' => ['label' => 'EPI era necessário?',                       'type' => 'scalar'],
                    'epi_recebeu' => ['label' => 'EPIs recebidos',                            'type' => 'list'],
                    'epi_outros' => ['label' => 'Outros EPIs',                               'type' => 'scalar'],
                    'epi_treinamento' => ['label' => 'Recebeu treinamento para uso?',             'type' => 'scalar'],
                    'epi_substituidos' => ['label' => 'EPIs eram substituídos quando necessário?', 'type' => 'scalar'],
                    'epi_usava' => ['label' => 'Usava os EPIs corretamente?',               'type' => 'scalar'],
                    'epi_fiscalizava' => ['label' => 'Empresa fiscalizava o uso?',                'type' => 'scalar'],
                ],
            ],

            'acidente' => [
                'title' => 'Acidente de Trabalho',
                'fields' => [
                    'acidente_sofreu' => ['label' => 'Sofreu acidente de trabalho?', 'type' => 'scalar'],
                    'acidente_detalhes' => ['label' => 'Detalhes do acidente',         'type' => 'scalar'],
                    'acidente_data' => ['label' => 'Data do acidente',             'type' => 'scalar'],
                    'acidente_cat' => ['label' => 'CAT foi emitida?',             'type' => 'scalar'],
                    'acidente_cat_emissor' => ['label' => 'Quem emitiu a CAT?',           'type' => 'scalar'],
                ],
            ],

            'saude' => [
                'title' => 'Problemas de Saúde Relacionados ao Trabalho',
                'fields' => [
                    'saude_problema' => ['label' => 'Tem problema de saúde relacionado ao trabalho?', 'type' => 'scalar'],
                    'saude_quais' => ['label' => 'Quais problemas de saúde?',                      'type' => 'scalar'],
                    'saude_desenvolvimento' => ['label' => 'Como se desenvolveu?',                           'type' => 'scalar'],
                    'saude_periodo' => ['label' => 'Período',                                        'type' => 'scalar'],
                    'saude_relatorio' => ['label' => 'Possui relatório médico?',                       'type' => 'scalar'],
                    'saude_situacoes_pessoais' => ['label' => 'Situações pessoais que podem ter contribuído?',  'type' => 'scalar'],
                    'saude_situacoes_pessoais_quais' => ['label' => 'Quais situações pessoais?',                      'type' => 'scalar'],
                    'saude_empresa_ciente' => ['label' => 'Empresa estava ciente do problema?',             'type' => 'scalar'],
                    'saude_permanece' => ['label' => 'Permanece com o problema?',                      'type' => 'scalar'],
                    'saude_tratamento' => ['label' => 'Tipo de tratamento',                             'type' => 'list'],
                    'saude_medicamentos' => ['label' => 'Faz uso de medicamentos?',                       'type' => 'scalar'],
                    'saude_medicamentos_quais' => ['label' => 'Quais medicamentos?',                            'type' => 'scalar'],
                ],
            ],

            'afastamento' => [
                'title' => 'Afastamento do Trabalho',
                'fields' => [
                    'afastamento_foi' => ['label' => 'Foi afastado do trabalho?', 'type' => 'scalar'],
                    'afastamento_motivo' => ['label' => 'Motivo do afastamento',     'type' => 'scalar'],
                    'afastamento_data_inicio' => ['label' => 'Data de início',            'type' => 'scalar'],
                    'afastamento_data_fim' => ['label' => 'Data de fim',               'type' => 'scalar'],
                    'afastamento_senha_gov' => ['label' => 'Senha Gov.br',              'type' => 'scalar'],
                ],
            ],

            'fgts' => [
                'title' => 'FGTS',
                'fields' => [
                    'fgts_recolhia' => ['label' => 'Empresa recolhia o FGTS corretamente?', 'type' => 'scalar'],
                ],
            ],

            'equiparacao' => [
                'title' => 'Equiparação Salarial',
                'fields' => [
                    'equiparacao_colega' => ['label' => 'Tem colega com salário superior realizando as mesmas funções?', 'type' => 'scalar'],
                    'equiparacao_nome_colega' => ['label' => 'Nome do colega paradigma',                                     'type' => 'scalar'],
                    'equiparacao_funcao_colega' => ['label' => 'Função do colega',                                             'type' => 'scalar'],
                    'equiparacao_atividades' => ['label' => 'Atividades exercidas pelo colega',                             'type' => 'scalar'],
                    'equiparacao_identicas' => ['label' => 'As atividades eram idênticas?',                                'type' => 'scalar'],
                    'equiparacao_2_anos' => ['label' => 'Diferença superior a 2 anos na função?',                      'type' => 'scalar'],
                ],
            ],

            'desvio' => [
                'title' => 'Desvio e Acúmulo de Função',
                'fields' => [
                    'desvio_funcao' => ['label' => 'Houve desvio de função?',                         'type' => 'scalar'],
                    'desvio_atividades' => ['label' => 'Atividades realizadas além da função contratada', 'type' => 'scalar'],
                    'desvio_periodo_de' => ['label' => 'Período de (início)',                             'type' => 'scalar'],
                    'desvio_periodo_ate' => ['label' => 'Período até (fim)',                               'type' => 'scalar'],
                    'acumulo_funcao' => ['label' => 'Houve acúmulo de função?',                        'type' => 'scalar'],
                    'acumulo_atividades' => ['label' => 'Atividades acumuladas',                           'type' => 'scalar'],
                ],
            ],

            'extincao' => [
                'title' => 'Extinção do Contrato de Trabalho',
                'fields' => [
                    'extincao_causa' => ['label' => 'Causa da extinção',                          'type' => 'scalar'],
                    'extincao_termino' => ['label' => 'Forma de comunicação do término',            'type' => 'list'],
                    'extincao_dias_experiencia' => ['label' => 'Dias de experiência restantes',              'type' => 'scalar'],
                    'extincao_forma_acordada' => ['label' => 'Forma acordada de rescisão',                 'type' => 'scalar'],
                    'extincao_verbas_certas' => ['label' => 'Recebeu todas as verbas rescisórias certas?', 'type' => 'scalar'],
                    'extincao_data_pagamento' => ['label' => 'Data de pagamento das verbas',               'type' => 'scalar'],
                    'extincao_valor_pago' => ['label' => 'Valor total pago',                           'type' => 'scalar'],
                    'extincao_forma_pagamento' => ['label' => 'Forma de pagamento das verbas',              'type' => 'list'],
                    'extincao_data_entrega_guias' => ['label' => 'Data de entrega das guias',                  'type' => 'scalar'],
                    'extincao_erro_guias' => ['label' => 'Há erros nas guias?',                        'type' => 'scalar'],
                    'extincao_quais_erros' => ['label' => 'Quais erros nas guias?',                     'type' => 'scalar'],
                    'extincao_guias' => ['label' => 'Guias recebidas',                            'type' => 'list'],
                    'extincao_solicitou_seguro' => ['label' => 'Solicitou seguro-desemprego?',               'type' => 'scalar'],
                ],
            ],

            'testemunhas' => [
                'title' => 'Testemunhas e Ações Anteriores',
                'fields' => [
                    'testemunhas_nomes' => ['label' => 'Nomes das testemunhas',            'type' => 'scalar'],
                    'acao_anterior' => ['label' => 'Possui ação trabalhista anterior?', 'type' => 'scalar'],
                    'acao_anterior_data' => ['label' => 'Data da ação anterior',             'type' => 'scalar'],
                    'acao_anterior_processo' => ['label' => 'Número do processo',                'type' => 'scalar'],
                    'acao_anterior_motivos' => ['label' => 'Motivos da ação anterior',          'type' => 'scalar'],
                    'testemunha_defesa_nome' => ['label' => 'Nome da testemunha da defesa',      'type' => 'scalar'],
                    'testemunha_defesa_contato' => ['label' => 'Contato da testemunha da defesa',   'type' => 'scalar'],
                ],
            ],

            'beneficios' => [
                'title' => 'Benefícios da Convenção Coletiva',
                'fields' => [
                    'beneficios' => ['label' => 'Benefícios recebidos',                     'type' => 'list'],
                    'beneficio_lanche_composicao' => ['label' => 'Composição do lanche',                     'type' => 'scalar'],
                    'beneficio_vale_combustivel_valor' => ['label' => 'Valor do vale combustível',                'type' => 'scalar'],
                    'beneficio_abono_valor' => ['label' => 'Valor do abono',                           'type' => 'scalar'],
                    'beneficio_vale_alimentacao_meio' => ['label' => 'Meio de recebimento do vale alimentação',  'type' => 'list'],
                    'beneficio_vale_alimentacao_valor' => ['label' => 'Valor do vale alimentação',                'type' => 'scalar'],
                    'beneficio_homologacao' => ['label' => 'Participou da homologação?',               'type' => 'scalar'],
                    'beneficio_outros' => ['label' => 'Outros benefícios',                        'type' => 'scalar'],
                ],
            ],

            'salario_familia' => [
                'title' => 'Salário Família',
                'fields' => [
                    'salario_familia_filhos' => ['label' => 'Possui filhos menores de 14 anos?', 'type' => 'scalar'],
                    'salario_familia_nomes' => ['label' => 'Nomes dos filhos',                  'type' => 'scalar'],
                    'salario_familia_idades' => ['label' => 'Idade(s)',                           'type' => 'scalar'],
                    'salario_familia_recebeu' => ['label' => 'Recebeu salário família?',          'type' => 'scalar'],
                    'salario_familia_valor' => ['label' => 'Valor recebido',                    'type' => 'scalar'],
                ],
            ],

            'outras' => [
                'title' => 'Outras Informações',
                'fields' => [
                    'outras_informacoes' => ['label' => 'Informações adicionais relevantes', 'type' => 'scalar'],
                ],
            ],

            'documentos' => [
                'title' => 'Relação de Documentos',
                'fields' => [
                    'documentos_recebidos' => ['label' => 'Documentos recebidos', 'type' => 'scalar'],
                ],
            ],

            'preliminares' => [
                'title' => 'Preliminares Selecionadas',
                'fields' => [
                    'preliminares_selecionadas' => ['label' => 'Preliminares', 'type' => 'preliminares'],
                ],
            ],

            'pedidos' => [
                'title' => 'Pedidos Selecionados',
                'fields' => [
                    'topicos' => ['label' => 'Pedidos', 'type' => 'pedidos'],
                ],
            ],
        ];
    }
}
