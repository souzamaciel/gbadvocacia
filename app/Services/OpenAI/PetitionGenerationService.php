<?php

namespace App\Services\OpenAI;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PetitionGenerationService
{
    public function __construct(
        protected HttpFactory $http
    ) {}

    public function generate(array $dados, bool $usarFileSearch = true, bool $hasFormData = true): array
    {
        $payload = $this->buildPayload($dados, $usarFileSearch, $hasFormData);

        $response = $this->http
            ->baseUrl(config('services.openai.base_url'))
            ->withToken(config('services.openai.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(120)
            ->post('/responses', $payload);

        if (! $response->successful()) {
            Log::error('Erro ao chamar OpenAI Responses API', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException(
                'Falha na OpenAI API: '.$response->body()
            );
        }

        $json = $response->json();

        $structuredOutput = $this->extractStructuredOutput($json);

        if (! is_array($structuredOutput)) {
            throw new RuntimeException('Não foi possível extrair o JSON estruturado da resposta da OpenAI.');
        }

        return [
            'openai_response_id' => Arr::get($json, 'id'),
            'openai_model' => config('services.openai.model'),
            'request_payload' => $payload,
            'structured_output' => $structuredOutput,
            'blade_data' => $this->mapStructuredOutputToBladeData($dados, $structuredOutput),
            'file_search_results' => $this->extractFileSearchResults($json),
            'raw_response' => $json,
        ];
    }

    protected function buildPayload(array $dados, bool $usarFileSearch = true, bool $hasFormData = true): array
    {
        $model = config('services.openai.model');
        $vectorStoreId = config('services.openai.vector_store_id');

        $tools = [];

        if ($usarFileSearch && ! empty($vectorStoreId)) {
            $tools[] = [
                'type' => 'file_search',
                'vector_store_ids' => [$vectorStoreId],
            ];
        }

        $variableInstruction = $hasFormData
            ? <<<'TEXT'
- Cada item selecionado (tanto em "topicos_selecionados" quanto em "preliminares_selecionadas") possui um campo "content" com o texto-base.
- Esse texto-base contém variáveis delimitadas por colchetes [variável] ou chaves {variável}.
- Você DEVE substituir essas variáveis pelas informações correspondentes do formulário do caso (JSON), tanto nos tópicos de mérito quanto nas preliminares.
- Se não houver informação disponível no JSON para preencher uma variável, mantenha a variável original entre colchetes/chaves e registre em "pendencias".
- Use o texto-base do "content" como fundamento, adaptando-o ao caso concreto com os dados fornecidos.
TEXT
            : <<<'TEXT'
- Cada item selecionado (tanto em "topicos_selecionados" quanto em "preliminares_selecionadas") possui um campo "content" com o texto-base.
- Esse texto-base contém variáveis delimitadas por colchetes [variável] ou chaves {variável}.
- Como NÃO há dados de formulário do cliente, você DEVE manter todas as variáveis entre colchetes/chaves exatamente como estão, sem preenchê-las, tanto nos tópicos de mérito quanto nas preliminares.
- Use o texto-base do "content" como fundamento para a redação, preservando as variáveis no lugar dos dados do cliente.
TEXT;

        $instructions = <<<TEXT
Você é um assistente jurídico especializado em reclamações trabalhistas.

Sua tarefa é redigir os blocos variáveis de uma petição inicial trabalhista seguindo rigorosamente:
1. a estrutura do modelo do escritório;
2. os dados enviados no JSON do caso;
3. a ordem dos tópicos selecionados;
4. o texto-base (content) de cada tópico selecionado.

Regras obrigatórias:
- Não invente fatos, datas, valores, documentos, jornadas, cargos ou pedidos.
- Use somente as informações fornecidas no JSON e, se habilitado, os trechos recuperados do modelo.
- Mantenha linguagem formal, técnica e compatível com petição inicial trabalhista.
- TODOS os itens listados em "topicos_selecionados" e em "preliminares_selecionadas" devem ser gerados, sem exceção, sem omitir, unir ou descartar nenhum — independentemente de você considerá-los aplicáveis ou não ao caso.
- Se faltar dado necessário, registre em "pendencias".
- Retorne somente JSON válido conforme o schema informado.
- Os campos *_html (e o "conteudo_html" das preliminares e tópicos) devem conter HTML simples e seguro, usando apenas: <p>, <br>, <strong>, <em>, <u>, <s>, <sub>, <sup>, <h1>, <h2>, <blockquote>, <pre>, <ol>, <ul>, <li>, <a href> e <span>.
- Quando o "content" de um tópico ou preliminar vier em HTML, copie para o "conteudo_html" exatamente a mesma marcação: as mesmas tags, os atributos class (ql-align-*, ql-indent-*, ql-size-*), os atributos style de cor (color e background-color) e os links (href). Não remova, não troque e não acrescente formatação.

Regras sobre variáveis no texto-base (content):
{$variableInstruction}
TEXT;

        $inputText = <<<TEXT
DADOS DO CASO EM JSON:
{$this->jsonEncodePretty($dados)}

IMPORTANTE:
- O campo "contrato_trabalho_html" deve narrar o vínculo empregatício.
- O campo "topicos" deve retornar os tópicos de mérito adicionais, utilizando o texto-base (content) de cada tópico como fundamento.
- O campo "pedidos" deve retornar uma lista objetiva dos pedidos. NÃO inclua no campo "pedidos" nenhum item referente a gratuidade de justiça, tramitação no Juízo 100% Digital ou valor da causa — esses itens aparecem em seção própria e fixa da petição.
- Os campos "valor_causa" e "valor_causa_extenso" SÃO OBRIGATÓRIOS e nunca devem ficar vazios. Estime um valor de sugestão coerente com os pedidos selecionados e com os dados do caso (quando disponíveis: salário, tempo de contrato, verbas pleiteadas, indenizações etc.). Caso não haja dados suficientes do caso, gere uma estimativa-base razoável a partir dos pedidos. "valor_causa" deve estar no formato "R$ X.XXX,XX" e "valor_causa_extenso" deve ser a grafia por extenso correspondente.
- O campo "preliminares" deve conter EXATAMENTE UM item para CADA preliminar listada em "preliminares_selecionadas" do JSON, na mesma ordem. NUNCA omita, una ou descarte uma preliminar selecionada — mesmo que falte dado para preencher variáveis, mantenha-as entre [colchetes] e registre em "pendencias". Para cada item, use o "title" da preliminar selecionada como "titulo" e elabore "conteudo_html" a partir do "content" daquela preliminar. Se "preliminares_selecionadas" estiver vazio, retorne array vazio.
- O campo "honorarios_html" deve existir se houver honorários.
TEXT;

        $payload = [
            'model' => $model,
            'instructions' => $instructions,
            'input' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => $inputText,
                ]],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'petition_blocks',
                    'strict' => true,
                    'schema' => $this->responseSchema(),
                ],
            ],
        ];

        if (! empty($tools)) {
            $payload['tools'] = $tools;

            // A documentação mostra que a API aceita incluir detalhes dos resultados de file search.
            $payload['include'] = ['file_search_call.results'];
        }

        return $payload;
    }

    protected function responseSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'titulo_acao' => ['type' => 'string'],
                'preliminares' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'titulo' => ['type' => 'string'],
                            'conteudo_html' => ['type' => 'string'],
                        ],
                        'required' => ['titulo', 'conteudo_html'],
                    ],
                ],
                'contrato_trabalho_html' => ['type' => 'string'],
                'topicos' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'ordem' => ['type' => 'string'],
                            'titulo' => ['type' => 'string'],
                            'conteudo_html' => ['type' => 'string'],
                            'subtopicos' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'properties' => [
                                        'ordem' => ['type' => 'string'],
                                        'titulo' => ['type' => 'string'],
                                        'conteudo_html' => ['type' => 'string'],
                                    ],
                                    'required' => ['ordem', 'titulo', 'conteudo_html'],
                                ],
                            ],
                        ],
                        'required' => ['ordem', 'titulo', 'conteudo_html', 'subtopicos'],
                    ],
                ],
                'incluir_honorarios' => ['type' => 'boolean'],
                'honorarios_ordem' => ['type' => 'string'],
                'honorarios_html' => ['type' => 'string'],
                'pedidos_ordem' => ['type' => 'string'],
                'pedidos' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'descricao' => ['type' => 'string'],
                        ],
                        'required' => ['descricao'],
                    ],
                ],
                'requerimentos_finais_html' => ['type' => 'string'],
                'valor_causa' => ['type' => 'string'],
                'valor_causa_extenso' => ['type' => 'string'],
                'pendencias' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'titulo_acao',
                'preliminares',
                'contrato_trabalho_html',
                'topicos',
                'incluir_honorarios',
                'honorarios_ordem',
                'honorarios_html',
                'pedidos_ordem',
                'pedidos',
                'requerimentos_finais_html',
                'valor_causa',
                'valor_causa_extenso',
                'pendencias',
            ],
        ];
    }

    protected function extractStructuredOutput(array $response): ?array
    {
        $output = Arr::get($response, 'output', []);

        foreach ($output as $item) {
            $contents = $item['content'] ?? [];

            foreach ($contents as $content) {
                if (($content['type'] ?? null) === 'output_text' && ! empty($content['text'])) {
                    $decoded = json_decode($content['text'], true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                }
            }
        }

        // fallback
        $text = Arr::get($response, 'output_text');
        if (is_string($text) && $text !== '') {
            $decoded = json_decode($text, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function extractFileSearchResults(array $response): array
    {
        $results = [];

        foreach (($response['output'] ?? []) as $outputItem) {
            if (($outputItem['type'] ?? null) !== 'file_search_call') {
                continue;
            }

            foreach (($outputItem['results'] ?? []) as $result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    protected function mapStructuredOutputToBladeData(array $dadosOriginais, array $structured): array
    {
        return [
            // base do caso
            'titulo_documento' => 'Petição Inicial Trabalhista',
            'vara_competente' => $dadosOriginais['vara_competente'] ?? '___ VARA DO TRABALHO DE __________',
            'titulo_acao' => $structured['titulo_acao'] ?? ($dadosOriginais['titulo_acao'] ?? 'RECLAMAÇÃO TRABALHISTA'),

            // cliente
            'cliente_nome' => $dadosOriginais['cliente_nome'] ?? null,
            'cliente_nacionalidade' => $dadosOriginais['cliente_nacionalidade'] ?? null,
            'cliente_estado_civil' => $dadosOriginais['cliente_estado_civil'] ?? null,
            'cliente_profissao' => $dadosOriginais['cliente_profissao'] ?? null,
            'cliente_rg' => $dadosOriginais['cliente_rg'] ?? null,
            'cliente_orgao_expedidor' => $dadosOriginais['cliente_orgao_expedidor'] ?? null,
            'cliente_cpf' => $dadosOriginais['cliente_cpf'] ?? null,
            'cliente_endereco' => $dadosOriginais['cliente_endereco'] ?? null,
            'cliente_numero' => $dadosOriginais['cliente_numero'] ?? null,
            'cliente_complemento' => $dadosOriginais['cliente_complemento'] ?? null,
            'cliente_bairro' => $dadosOriginais['cliente_bairro'] ?? null,
            'cliente_cep' => $dadosOriginais['cliente_cep'] ?? null,
            'cliente_cidade' => $dadosOriginais['cliente_cidade'] ?? null,
            'cliente_uf' => $dadosOriginais['cliente_uf'] ?? null,
            'cliente_ctps' => $dadosOriginais['cliente_ctps'] ?? null,
            'cliente_ctps_serie' => $dadosOriginais['cliente_ctps_serie'] ?? null,
            'cliente_pis' => $dadosOriginais['cliente_pis'] ?? null,

            // empregador
            'empregador_nome' => $dadosOriginais['empregador_nome'] ?? null,
            'empregador_cnpj' => $dadosOriginais['empregador_cnpj'] ?? null,
            'empregador_endereco' => $dadosOriginais['empregador_endereco'] ?? null,
            'empregador_numero' => $dadosOriginais['empregador_numero'] ?? null,
            'empregador_complemento' => $dadosOriginais['empregador_complemento'] ?? null,
            'empregador_bairro' => $dadosOriginais['empregador_bairro'] ?? null,
            'empregador_cidade' => $dadosOriginais['empregador_cidade'] ?? null,
            'empregador_uf' => $dadosOriginais['empregador_uf'] ?? null,
            'empregador_cep' => $dadosOriginais['empregador_cep'] ?? null,
            'empregador_email' => $dadosOriginais['empregador_email'] ?? null,
            'empregador_telefone' => $dadosOriginais['empregador_telefone'] ?? null,

            // blocos variáveis gerados pela IA
            'preliminares' => $structured['preliminares'] ?? [],
            'preliminares_selecionadas_ids' => array_map('intval', array_column($dadosOriginais['preliminares_selecionadas'] ?? [], 'topic_id')),
            'contrato_trabalho_html' => $structured['contrato_trabalho_html'] ?? '',
            'topicos' => $structured['topicos'] ?? [],
            'incluir_honorarios' => $structured['incluir_honorarios'] ?? false,
            'honorarios_ordem' => $structured['honorarios_ordem'] ?? '06',
            'honorarios_html' => $structured['honorarios_html'] ?? '',
            'pedidos_ordem' => $structured['pedidos_ordem'] ?? '07',
            'pedidos' => $structured['pedidos'] ?? [],
            'requerimentos_finais_html' => $structured['requerimentos_finais_html'] ?? '',

            // fechamento
            'valor_causa' => $structured['valor_causa'] ?? ($dadosOriginais['valor_causa'] ?? 'R$ 0,00'),
            'valor_causa_extenso' => $structured['valor_causa_extenso'] ?? ($dadosOriginais['valor_causa_extenso'] ?? ''),
            'cidade_assinatura' => $dadosOriginais['cidade_assinatura'] ?? null,
            'data_assinatura' => $dadosOriginais['data_assinatura'] ?? null,
            'advogados' => $dadosOriginais['advogados'] ?? [],
            'pendencias' => $structured['pendencias'] ?? [],
        ];
    }

    protected function jsonEncodePretty(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
