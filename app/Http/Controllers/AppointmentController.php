<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\PetitionLog;
use App\Models\Topic;
use App\Services\AppointmentFormDocxGenerator;
use App\Services\OpenAI\PetitionGenerationService;
use App\Services\PetitionDocxGenerator;
use App\Services\PetitionFormSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AppointmentController extends Controller
{
    public function __construct(
        protected PetitionGenerationService $petitionGenerationService
    ) {}

    public function create(Request $request): View
    {
        $preliminares = Topic::query()
            ->where('active', true)
            ->where('group_id', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->get(['id', 'title']);

        $pedidos = Topic::query()
            ->where('active', true)
            ->where('group_id', '!=', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->get(['id', 'title']);

        $prefill = $request->boolean('test') ? $this->testPrefillData() : null;

        return view('appointments.create', compact('preliminares', 'pedidos', 'prefill'));
    }

    public function store(Request $request): BinaryFileResponse|JsonResponse
    {
        $topicIds = $request->input('topics', []);
        $preliminarIds = $request->input('preliminares', []);

        $topics = Topic::whereIn('id', $topicIds)
            ->get(['id', 'title', 'content'])
            ->sortBy(fn ($t) => array_search($t->id, $topicIds))
            ->values()
            ->map(fn ($t, $i) => ['sort_order' => $i + 1, 'topic_id' => $t->id, 'title' => $t->title, 'content' => $t->content])
            ->all();

        $preliminares = Topic::whereIn('id', $preliminarIds)
            ->where('group_id', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'content'])
            ->values()
            ->map(fn ($t, $i) => ['sort_order' => $i + 1, 'topic_id' => $t->id, 'title' => $t->title, 'content' => $t->content])
            ->all();

        $normalized = PetitionFormSerializer::normalizeFormData($request->all(), $topics, $preliminares);

        try {
            $resultado = $this->petitionGenerationService->generate($normalized, false, true);

            PetitionLog::create([
                'openai_model' => $resultado['openai_model'],
                'request_payload' => $resultado['request_payload'],
                'response_payload' => $resultado['raw_response'],
            ]);

            $generator = new PetitionDocxGenerator;
            $filePath = $generator->generateFull($resultado['blade_data']);

            $clientName = $normalized['cliente_nome'] ?? 'peticao';
            $filename = 'peticao-inicial-'.str()->slug($clientName).'.docx';

            return response()
                ->download($filePath, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->deleteFileAfterSend();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar petição: '.$e->getMessage(),
            ], 500);
        }
    }

    public function print(Request $request): BinaryFileResponse|JsonResponse
    {
        try {
            $generator = new AppointmentFormDocxGenerator;
            $filePath = $generator->generate($request->all());

            $clientName = $request->input('cliente_nome', 'formulario');
            $filename = 'formulario-atendimento-'.str()->slug($clientName).'.docx';

            return response()
                ->download($filePath, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->deleteFileAfterSend();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar formulário: '.$e->getMessage(),
            ], 500);
        }
    }

    /** @return array<string, mixed> */
    private function testPrefillData(): array
    {
        $pedidos = Topic::where('active', true)
            ->where('group_id', '!=', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->limit(3)
            ->get(['id', 'title'])
            ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title])
            ->all();

        $preliminares = Topic::where('active', true)
            ->where('group_id', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->limit(2)
            ->get(['id', 'title'])
            ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title])
            ->all();

        return [
            'scalars' => [
                'cliente_nome' => 'Maria Aparecida Ferreira',
                'cliente_profissao' => 'Auxiliar de Limpeza',
                'cliente_nacionalidade' => 'Brasileira',
                'cliente_estado_civil' => 'Solteira',
                'cliente_nascimento' => '1988-03-22',
                'cliente_rg' => '12.345.678',
                'cliente_orgao_expedidor' => 'SSP/MG',
                'cliente_cpf' => '123.456.789-00',
                'cliente_pis' => '123.45678.90-1',
                'cliente_ctps' => '0012345',
                'cliente_ctps_serie' => '0023-MG',
                'cliente_nome_mae' => 'Josefa Maria Ferreira',
                'cliente_cep' => '35162-200',
                'cliente_endereco' => 'Rua das Acácias',
                'cliente_numero' => '142',
                'cliente_complemento' => 'Apto 03',
                'cliente_bairro' => 'Bom Retiro',
                'cliente_cidade' => 'Ipatinga',
                'cliente_uf' => 'MG',
                'contato_tel_residencial' => '(31) 3823-0000',
                'contato_celular' => '(31) 98800-1234',
                'contato_tel_terceiro' => '(31) 99700-5678',
                'contato_nome_terceiro' => 'Carlos Ferreira',
                'contato_grau_parentesco' => 'Irmão',
                'empregador_nome' => 'Servilimpe Terceirizados Ltda.',
                'empregador_cnpj' => '12.345.678/0001-90',
                'empregador_cep' => '35160-100',
                'empregador_endereco' => 'Av. Pedro Linhares Gomes, 1000',
                'empregador_cidade' => 'Ipatinga',
                'empregador_uf' => 'MG',
                'empregador_bairro' => 'Centro',
                'empregador_email' => 'rh@servilimpe.com.br',
                'empregador_telefone' => '(31) 3822-3897',
                'empregador_superior' => 'José Carlos Mendes',
                'grupo_economico' => 'Servilimpe Grupo',
                'urgencia_outros' => '',
                'urgencia_prescricao_bienal' => '2026-08-15',
                'urgencia_qual_beneficio' => '',
                'contrato_admissao' => '2019-04-01',
                'contrato_dispensa' => '2024-08-15',
                'contrato_dias_experiencia' => '45',
                'jornada_cidade' => 'Ipatinga',
                'jornada_uf' => 'MG',
                'jornada_empresa_local' => 'Usiminas – Planta Ipatinga',
                'jornada_horario_contratual' => '07:00 às 17:00 com 1h de intervalo',
                'jornada_horario_outros' => '',
                'jornada_minutos_extras' => '45',
                'jornada_vezes_semana' => '5',
                'jornada_frequencia' => 'Todo domingo e feriados nacionais',
                'jornada_folga_dias' => 'Segunda-feira',
                'jornada_dias_consecutivos' => '6',
                'jornada_intervalo_refeicao' => '30 minutos',
                'jornada_intervalo_vezes_semana' => '5',
                'salario_ultimo' => 'R$ 1.850,00',
                'salario_valor_por_fora' => 'R$ 300,00',
                'salario_prova_por_fora' => 'Transferência via PIX identificada como "ajuda" na conta da autora',
                'comissoes_valor' => '',
                'transferencia_data' => '',
                'transferencia_motivo' => '',
                'transferencia_detalhes' => '',
                'fora_ipatinga_localidade' => '',
                'insalubridade_outros_agentes' => 'Dejetos humanos e resíduos hospitalares',
                'insalubridade_qtd_banheiro' => '8',
                'epi_outros' => 'Avental impermeável',
                'acidente_detalhes' => 'Queda ao escorregar em piso molhado sem sinalização, resultando em fratura no punho direito',
                'acidente_data' => '2023-11-10',
                'saude_quais' => 'Tendinite no punho direito e síndrome do ombro doloroso',
                'saude_desenvolvimento' => 'Movimentos repetitivos de limpeza pesada por mais de 5 anos sem pausas adequadas',
                'saude_periodo' => '2022 a 2024',
                'saude_situacoes_pessoais_quais' => '',
                'saude_medicamentos_quais' => 'Anti-inflamatório e analgésico',
                'afastamento_motivo' => 'Acidente de trabalho – fratura no punho',
                'afastamento_data_inicio' => '2023-11-10',
                'afastamento_data_fim' => '2024-01-20',
                'afastamento_senha_gov' => 'Não disponível',
                'equiparacao_nome_colega' => 'Paulo Roberto Lima',
                'equiparacao_funcao_colega' => 'Auxiliar de Limpeza',
                'equiparacao_atividades' => 'Executa as mesmas atividades de higienização de banheiros industriais',
                'desvio_atividades' => 'Realizava também funções de copeira e recepcionista na portaria',
                'desvio_periodo_de' => '2021-01-01',
                'desvio_periodo_ate' => '2024-08-15',
                'acumulo_atividades' => 'Copeira, recepcionista e auxiliar de almoxarifado',
                'extincao_dias_experiencia' => '0',
                'extincao_forma_acordada' => 'Rescisão sem justa causa com pagamento de verbas em 10 dias',
                'extincao_data_pagamento' => '2024-08-25',
                'extincao_valor_pago' => 'R$ 4.200,00',
                'extincao_data_entrega_guias' => '2024-08-25',
                'extincao_quais_erros' => 'Data de admissão registrada incorretamente como 2020',
                'testemunhas_nomes' => "Sandra Lucia Costa – (31) 98700-1111\nAna Paula Souza – (31) 99600-2222",
                'acao_anterior_data' => '',
                'acao_anterior_processo' => '',
                'acao_anterior_motivos' => '',
                'testemunha_defesa_nome' => 'Robson Alves Pereira',
                'testemunha_defesa_contato' => '(31) 97700-3333',
                'beneficio_lanche_composicao' => 'Pão, manteiga e café',
                'beneficio_vale_combustivel_valor' => '',
                'beneficio_abono_valor' => 'R$ 150,00',
                'beneficio_vale_alimentacao_valor' => 'R$ 220,00',
                'beneficio_outros' => 'Cesta básica semestral',
                'salario_familia_nomes' => "Lucas Ferreira – nascido em 10/04/2015\nGabriela Ferreira – nascida em 22/07/2018",
                'salario_familia_idades' => '9, 6',
                'salario_familia_valor' => 'R$ 62,04',
                'outras_informacoes' => 'Cliente relatou que supervisora a chamava de "devagar" e "incompetente" na frente dos colegas. Há grupo de WhatsApp com mensagens de pressão.',
                'documentos_recebidos' => "RG e CPF\nCTPS\nHolerites (jan/2022 a ago/2024)\nCartões de ponto (2022 a 2024)\nAtestados médicos\nCAT emitida pelo sindicato\nTermo de rescisão\nGuia do FGTS",
            ],
            'radios' => [
                'empregador_autoriza_polo' => 'sim',
                'urgencia_seguro_beneficio' => 'seguro',
                'contrato_registro_ctps' => 'sim',
                'contrato_modalidade' => 'prazo_indeterminado',
                'vigilante_armado' => 'nao_aplicavel',
                'vigilante_colete' => 'nao_aplicavel',
                'jornada_mais_cedo_tarde' => 'mais_tarde',
                'jornada_domingos' => 'sim',
                'jornada_feriados' => 'sim',
                'jornada_domingo_mes' => 'nao',
                'jornada_6_dias' => 'sim',
                'jornada_intervalo_inferior' => 'sim',
                'ponto_tem' => 'sim',
                'ponto_celular' => 'nao_aplicavel',
                'ponto_mais_20_func' => 'sim',
                'ponto_verdadeiro' => 'nao',
                'ponto_alteracao' => 'sim',
                'salario_por_fora' => 'sim',
                'comissoes_recebia' => 'nao',
                'transferencia_foi' => 'nao',
                'transferencia_mudanca_endereco' => 'nao',
                'transferencia_tipo' => 'provisoria',
                'transferencia_despesas' => 'nao',
                'fora_ipatinga' => 'nao',
                'fora_ipatinga_contrato_telefone' => 'nao',
                'insalubridade_exposto' => 'sim',
                'insalubridade_adicional' => 'nao',
                'epi_necessario' => 'sim',
                'epi_treinamento' => 'nao',
                'epi_substituidos' => 'nao',
                'epi_usava' => 'sim',
                'epi_fiscalizava' => 'nao',
                'acidente_sofreu' => 'sim',
                'acidente_cat' => 'sim',
                'acidente_cat_emissor' => 'sindicato',
                'saude_problema' => 'sim',
                'saude_relatorio' => 'sim',
                'saude_situacoes_pessoais' => 'nao',
                'saude_empresa_ciente' => 'sim',
                'saude_permanece' => 'sim',
                'saude_medicamentos' => 'sim',
                'afastamento_foi' => 'sim',
                'fgts_recolhia' => 'parcial',
                'equiparacao_colega' => 'sim',
                'equiparacao_identicas' => 'sim',
                'equiparacao_2_anos' => 'nao',
                'desvio_funcao' => 'sim',
                'acumulo_funcao' => 'sim',
                'extincao_causa' => 'sem_justa_causa',
                'extincao_verbas_certas' => 'parcial',
                'extincao_erro_guias' => 'sim',
                'extincao_solicitou_seguro' => 'sim',
                'acao_anterior' => 'nao',
                'beneficio_homologacao' => 'sim',
                'salario_familia_filhos' => 'sim',
                'salario_familia_recebeu' => 'nao',
            ],
            'lists' => [
                'urgencia_motivos' => ['prescricao', 'financeiro'],
                'jornada_horario_realizado' => ['horas_extras'],
                'ponto_tipo' => ['biometrico'],
                'salario_forma_pagamento' => ['deposito', 'pix'],
                'insalubridade_agentes' => ['biologico', 'quimico'],
                'epi_recebeu' => ['luvas', 'mascara', 'bota'],
                'saude_tratamento' => ['fisioterapia', 'medico'],
                'extincao_termino' => ['pessoalmente', 'whatsapp'],
                'extincao_forma_pagamento' => ['deposito'],
                'extincao_guias' => ['fgts', 'seguro_desemprego', 'ctps', 'termo_rescisao'],
                'beneficios' => ['lanche', 'vale_alimentacao', 'abono'],
                'beneficio_vale_alimentacao_meio' => ['cartao'],
            ],
            'funcoes' => [
                ['nome' => 'Auxiliar de Limpeza', 'de' => '2019-04-01', 'ate' => '2020-12-31'],
                ['nome' => 'Copeira / Auxiliar de Serviços Gerais', 'de' => '2021-01-01', 'ate' => '2024-08-15'],
            ],
            'tomadoras' => [
                ['nome' => 'Usiminas S.A.', 'cnpj' => '60.894.730/0001-05'],
            ],
            'jornada_tomadoras' => [
                ['nome' => 'Usiminas S.A.', 'local' => 'Av. Pedro Linhares Gomes, s/n – Ipatinga/MG'],
            ],
            'pedidos' => $pedidos,
            'preliminares' => $preliminares,
        ];
    }
}
