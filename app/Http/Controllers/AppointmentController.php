<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\PetitionLog;
use App\Models\Topic;
use App\Services\AppointmentForm;
use App\Services\AppointmentFormDocxGenerator;
use App\Services\InternalNotes;
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
        protected PetitionGenerationService $petitionGenerationService,
        protected AppointmentForm $form,
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
            ->orderedByGroup()
            ->get(['id', 'title']);

        $steps = $this->form->steps();
        $prefill = $request->boolean('test') ? $this->testPrefillData() : null;

        return view('appointments.create', compact('preliminares', 'pedidos', 'steps', 'prefill'));
    }

    public function store(Request $request): BinaryFileResponse|JsonResponse
    {
        $missing = $this->form->missingRequired($request->all());

        if ($missing !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Preencha os campos obrigatórios: '.implode(', ', array_column($missing, 'label')).'.',
                'missing' => $missing,
            ], 422);
        }

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

        $notesByTopicId = Topic::whereIn('id', [...$topicIds, ...$preliminarIds])->pluck('notes', 'id')->all();

        $normalized = PetitionFormSerializer::normalizeFormData($request->all(), $topics, $preliminares);

        try {
            $resultado = $this->petitionGenerationService->generate($normalized, false, true);

            PetitionLog::create([
                'openai_model' => $resultado['openai_model'],
                'request_payload' => $resultado['request_payload'],
                'response_payload' => $resultado['raw_response'],
            ]);

            $generator = new PetitionDocxGenerator;
            $filePath = $generator->generateFull(InternalNotes::attach($resultado['blade_data'], $notesByTopicId, [
                'preliminares' => array_column($preliminares, 'topic_id'),
                'topicos' => array_column($topics, 'topic_id'),
            ]));

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
            $topicIds = array_map('intval', (array) $request->input('topics', []));
            $titles = Topic::whereIn('id', [...$topicIds, ...array_map('intval', (array) $request->input('preliminares', []))])->pluck('title', 'id');

            $filePath = (new AppointmentFormDocxGenerator)->generate(
                $this->form->answers($request->all()),
                Topic::query()
                    ->whereIn('id', (array) $request->input('preliminares', []))
                    ->where('group_id', Group::PRELIMINARES_ID)
                    ->orderBy('sort_order')
                    ->pluck('title')
                    ->all(),
                array_values(array_filter(array_map(fn (int $id): ?string => $titles[$id] ?? null, $topicIds))),
                $request->user()?->name,
            );

            $clientName = $request->input('cliente_nome') ?: 'formulario';
            $filename = 'ficha-atendimento-'.str()->slug($clientName).'.docx';

            return response()
                ->download($filePath, $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->deleteFileAfterSend();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar a ficha: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sample answers to try the form quickly (?test=1), keyed by question key.
     *
     * @return array{answers: array<string, mixed>, pedidos: array<int, array{id: int, title: string}>, preliminares: array<int, array{id: int, title: string}>}
     */
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
            'answers' => [
                'cliente_nome' => 'Maria Aparecida Ferreira',
                'cliente_profissao' => 'Auxiliar de limpeza',
                'cliente_nacionalidade' => 'brasileira',
                'cliente_estado_civil' => 'solteira',
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
                'cliente_bairro' => 'Bom Retiro',
                'cliente_cidade' => 'Ipatinga',
                'cliente_uf' => 'MG',
                'contato_celular' => '(31) 98800-1234',
                'empregador_nome' => 'Servilimpe Terceirizados Ltda.',
                'empregador_cnpj' => '12.345.678/0001-90',
                'empregador_cep' => '35160-100',
                'empregador_endereco' => 'Av. Pedro Linhares Gomes',
                'empregador_numero' => '1000',
                'empregador_bairro' => 'Centro',
                'empregador_cidade' => 'Ipatinga',
                'empregador_uf' => 'MG',
                'tomadoras' => [['nome' => 'Hospital Municipal de Ipatinga', 'cnpj' => '98.765.432/0001-10', 'cidade' => 'Ipatinga', 'uf' => 'MG']],
                'urgencia_motivos' => ['Prescrição próxima'],
                'contrato_admissao' => '2021-03-01',
                'contrato_dispensa' => '2025-07-31',
                'contrato_registro_ctps' => 'Sim',
                'contrato_modalidade' => 'Prazo indeterminado',
                'funcoes' => [['nome' => 'Auxiliar de limpeza', 'de' => '2021-03-01', 'ate' => '2025-07-31']],
                'jornada_cidade' => 'Ipatinga',
                'jornada_uf' => 'MG',
                'jornada_horario_contratual' => '07h às 16h',
                'jornada_horario_realizado' => '06h30 às 18h',
                'jornada_dias' => ['De segunda-feira a sábado'],
                'jornada_intervalo_refeicao' => '30 minutos',
                'ponto_tinha_controle' => 'Sim',
                'ponto_tipo' => ['Biométrico'],
                'salario_ultimo' => 'R$ 1.650,00',
                'insalubridade_exposto' => 'Sim',
                'insalubridade_agentes' => ['Higienizava banheiros', 'Produtos químicos'],
                'insalubridade_pessoas_banheiro' => '200',
                'insalubridade_adicional' => '20% (médio)',
                'extincao_causa' => 'Sem justa causa',
                'extincao_recebeu_verbas' => 'Não',
                'outras_informacoes' => 'Cliente relata que trabalhava sem pausas nos fins de semana.',
            ],
            'pedidos' => $pedidos,
            'preliminares' => $preliminares,
        ];
    }
}
