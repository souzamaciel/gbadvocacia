<?php

namespace App\Services\OpenAI;

use App\Models\PetitionLog;
use App\Services\RichText;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Asks the AI which passages of a pasted petition text are specific to that case
 * (names, companies, dates, values, schedules…) and should become [variables].
 * Nothing is applied here: the lawyer approves each suggestion.
 */
class TopicVariableSuggestionService
{
    public const CATEGORIES = ['PESSOA', 'EMPRESA', 'DATA', 'PERÍODO', 'VALOR', 'JORNADA', 'LOCAL', 'DOCUMENTO', 'CARGO', 'OUTRO'];

    public function __construct(
        protected HttpFactory $http
    ) {}

    /**
     * Suggested variables plus a suggested title and group for the topic.
     *
     * @param  array<int, string>  $groupNames  groups the topic can belong to
     * @return array{suggestions: array<int, array{trecho: string, nome: string, categoria: string, atencao: bool, motivo: string}>, title: string, group: ?string}
     */
    public function suggest(string $content, array $groupNames = []): array
    {
        $text = RichText::toPlainText($content);
        $payload = $this->buildPayload($text, array_values($groupNames));

        $response = $this->http
            ->baseUrl(config('services.openai.base_url'))
            ->withToken(config('services.openai.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(90)
            ->post('/responses', $payload);

        if (! $response->successful()) {
            Log::error('Erro ao pedir sugestões de variáveis à OpenAI', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new RuntimeException('Falha na OpenAI API: '.$response->status());
        }

        $json = $response->json();

        PetitionLog::create([
            'openai_model' => config('services.openai.model'),
            'request_payload' => $payload,
            'response_payload' => $json,
        ]);

        $output = $this->extractStructuredOutput($json);
        $group = trim((string) ($output['grupo'] ?? ''));

        return [
            'suggestions' => $this->validSuggestions($output['sugestoes'] ?? [], $text),
            'title' => mb_substr(trim((string) ($output['titulo'] ?? '')), 0, 255),
            'group' => in_array($group, $groupNames, true) ? $group : null,
        ];
    }

    /**
     * Keep only suggestions whose passage really exists in the text, once each.
     *
     * @param  array<int, mixed>  $suggestions
     * @return array<int, array{trecho: string, nome: string, categoria: string, atencao: bool, motivo: string}>
     */
    private function validSuggestions(array $suggestions, string $text): array
    {
        $valid = [];
        $seen = [];

        foreach ($suggestions as $suggestion) {
            $passage = trim((string) Arr::get($suggestion, 'trecho', ''));
            $name = trim((string) Arr::get($suggestion, 'nome', ''));

            if ($passage === '' || $name === '' || isset($seen[$passage]) || ! str_contains($text, $passage)
                || preg_match('/^\[.*\]$/u', $passage)) {
                continue;
            }

            $seen[$passage] = true;
            $valid[] = [
                'trecho' => $passage,
                'nome' => str_replace(['[', ']'], '', $name),
                'categoria' => in_array($suggestion['categoria'] ?? null, self::CATEGORIES, true) ? $suggestion['categoria'] : 'OUTRO',
                'atencao' => (bool) Arr::get($suggestion, 'atencao', false),
                'motivo' => trim((string) Arr::get($suggestion, 'motivo', '')),
            ];
        }

        usort($valid, fn (array $a, array $b): int => mb_strpos($text, $a['trecho']) <=> mb_strpos($text, $b['trecho']));

        return $valid;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<int, string>  $groupNames
     */
    private function buildPayload(string $text, array $groupNames): array
    {
        $categories = implode(', ', self::CATEGORIES);
        $groups = $groupNames === [] ? '(nenhum)' : implode('; ', $groupNames);

        $instructions = <<<TEXT
Você ajuda um escritório de advocacia trabalhista a transformar o texto de uma petição já usada em um modelo reutilizável.

Tarefa: identifique no texto os trechos que são ESPECÍFICOS DO CASO e devem virar variáveis, como nomes de pessoas, empresas, CNPJ/CPF, datas, períodos, valores em dinheiro, jornadas e horários, funções/cargos, cidades e endereços, números de processos ou documentos.

Regras:
- Sugira SOMENTE dados concretos que mudam de um caso para outro: nomes próprios de pessoas e empresas, CPF/CNPJ, datas, períodos, valores em dinheiro, horários e jornadas efetivamente cumpridas, frequências e durações de fatos do caso (ex.: "duas vezes por mês", "15 minutos" de intervalo), funções/cargos, cidades, endereços e números de processos ou documentos.
- "trecho" deve ser copiado EXATAMENTE como aparece no texto, caractere por caractere (mesmas maiúsculas, acentos, pontuação e espaços), sem aspas extras.
- O trecho deve conter SOMENTE o dado, sem palavras ao redor: nunca inclua no trecho expressões como "cerca de", "apenas", "aproximadamente", "intervalo de", "pausa de", "salário de" (ex.: "15 minutos", não "intervalo de apenas 15 minutos"; "duas vezes por mês", não "cerca de duas vezes por mês"; "março de 2022 a julho de 2025", não a frase inteira).
- Quando o mesmo dado aparece mais de uma vez no texto, sugira-o UMA única vez com esse trecho mínimo; ele será substituído em todas as ocorrências.
- "nome" é um nome curto e claro da variável em português, com a primeira letra maiúscula (ex.: "Período trabalhado", "Nome da parte autora", "Empresa reclamada", "Salário").
- "categoria" deve ser uma destas: {$categories}.

NUNCA sugira (nem mesmo com "atencao"):
- Expressões genéricas que designam as partes, sem nome próprio: "reclamante", "parte reclamante", "parte autora", "parte obreira", "obreiro", "empregado", "reclamada", "empresa", "empregadora", "ré".
- Referências a outras partes da petição: "tópico 01", "item 3.1", "conforme acima".
- Títulos de tópicos e numeração (ex.: "3.1 – DO INTERVALO INTRAJORNADA").
- Limites, percentuais e parâmetros fixados em lei ou jurisprudência: "08ª diária", "44ª semanal", "adicional legal", "50%", "divisor 220", "1/3", "40%", "13° salários".
- Leis, artigos, súmulas, orientações jurisprudenciais, nomes de tribunais e expressões jurídicas que valem para todos os casos.

Use "atencao": true SOMENTE quando o trecho for um dado concreto do caso que TAMBÉM pode ser uma referência legal ou um padrão que normalmente permanece no modelo (ex.: "44 horas semanais" citado junto ao art. 7º da Constituição). Nesse caso, "motivo" explica em uma frase por que recomenda manter o texto original. Nos demais, "atencao": false e "motivo" vazio.
- Ignore trechos que já estão entre colchetes, como [NOME DA RECLAMADA].
- Não repita o mesmo trecho.
- Se o texto não tiver nenhum dado concreto do caso, retorne a lista vazia.

Título e grupo do tópico:
- "titulo": título curto do tópico em MAIÚSCULAS, no estilo da biblioteca do escritório (ex.: "DAS HORAS EXTRAS ALÉM DA 08ª DIÁRIA E 44ª SEMANAL", "DA SUPRESSÃO DO INTERVALO INTRAJORNADA"). Se o texto já tiver um título de tópico, use-o sem a numeração.
- "grupo": o nome EXATO de um destes grupos que melhor corresponde ao tema: {$groups}. Se nenhum servir, retorne string vazia.
- Retorne somente JSON válido conforme o schema.
TEXT;

        return [
            'model' => config('services.openai.model'),
            'temperature' => 0,
            'instructions' => $instructions,
            'input' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => "TEXTO DA PETIÇÃO:\n\n".$text,
                ]],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'topic_variable_suggestions',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['sugestoes', 'titulo', 'grupo'],
                        'properties' => [
                            'titulo' => ['type' => 'string'],
                            'grupo' => ['type' => 'string'],
                            'sugestoes' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['trecho', 'nome', 'categoria', 'atencao', 'motivo'],
                                    'properties' => [
                                        'trecho' => ['type' => 'string'],
                                        'nome' => ['type' => 'string'],
                                        'categoria' => ['type' => 'string', 'enum' => self::CATEGORIES],
                                        'atencao' => ['type' => 'boolean'],
                                        'motivo' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function extractStructuredOutput(array $response): array
    {
        foreach (Arr::get($response, 'output', []) as $item) {
            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && ! empty($content['text'])) {
                    $decoded = json_decode($content['text'], true);

                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            }
        }

        throw new RuntimeException('Não foi possível ler as sugestões da IA.');
    }
}
