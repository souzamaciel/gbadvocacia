<?php

namespace App\Services\OpenAI;

use App\Enums\CalculationType;
use App\Models\PetitionLog;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Writes the list of pedidos of the petition (same style as the Word) and, for each
 * pedido and reflexo, tells how its amount is calculated and which quantities the case
 * gives (hours per month, days, number of salaries…). The AI never writes an amount:
 * the math is done by App\Services\Pedidos\PedidoCalculator.
 */
class PedidoClassificationService
{
    public function __construct(
        protected HttpFactory $http
    ) {}

    /**
     * @param  array<string, mixed>  $caseData  qualification keys, "ficha_atendimento" and "topicos_selecionados"
     * @return array{divisor: ?int, pedidos: list<array<string, mixed>>}
     */
    public function classify(array $caseData): array
    {
        $payload = $this->buildPayload($caseData);

        $response = $this->http
            ->baseUrl(config('services.openai.base_url'))
            ->withToken(config('services.openai.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(180)
            ->post('/responses', $payload);

        if (! $response->successful()) {
            Log::error('Erro ao classificar os pedidos pela OpenAI', [
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

        return [
            'divisor' => is_numeric($output['divisor'] ?? null) ? (int) $output['divisor'] : null,
            'pedidos' => array_values(array_filter(
                array_map(fn (mixed $pedido): ?array => is_array($pedido) ? $this->line($pedido, true) : null, $output['pedidos'] ?? []),
                fn (?array $pedido): bool => $pedido !== null && $pedido['descricao'] !== '',
            )),
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    private function line(array $line, bool $withSubitems): array
    {
        $type = CalculationType::tryFrom((string) ($line['tipo'] ?? '')) ?? CalculationType::Outro;

        return [
            'descricao' => mb_substr(rtrim(trim((string) ($line['descricao'] ?? '')), '.;'), 0, 500),
            'tipo' => $type->value,
            'quantidade' => is_numeric($line['quantidade'] ?? null) ? (float) $line['quantidade'] : null,
            'percentual' => is_numeric($line['percentual'] ?? null) ? (float) $line['percentual'] : null,
            'valor_informado' => is_numeric($line['valor_informado'] ?? null) ? (float) $line['valor_informado'] : null,
            'explicacao' => mb_substr(trim((string) ($line['explicacao'] ?? '')), 0, 500),
            ...($withSubitems ? ['subitens' => array_values(array_filter(
                array_map(fn (mixed $subitem): ?array => is_array($subitem) ? $this->line($subitem, false) : null, $line['subitens'] ?? []),
                fn (?array $subitem): bool => $subitem !== null && $subitem['descricao'] !== '',
            ))] : []),
        ];
    }

    /**
     * @param  array<string, mixed>  $caseData
     * @return array<string, mixed>
     */
    private function buildPayload(array $caseData): array
    {
        $types = implode("\n", array_map(
            fn (CalculationType $type): string => "- \"{$type->value}\": {$type->instruction()}",
            CalculationType::cases(),
        ));

        $instructions = <<<TEXT
Você ajuda um escritório de advocacia trabalhista a montar o rol de pedidos de uma petição inicial e a preparar o cálculo estimado de cada pedido.

Recebe os DADOS DO CASO (qualificação e respostas da ficha de atendimento) e os TÓPICOS DE MÉRITO já revisados pelo advogado ("topicos_selecionados", na ordem da petição).

1. Monte o rol de pedidos no estilo do escritório: um pedido para cada tópico de mérito, na mesma ordem dos tópicos, e por último os honorários advocatícios, se algum tópico os pedir. Cada "descricao" é uma frase objetiva e completa, sem letra de ordem, sem pontilhado e sem valor em reais (ex.: "Pagamento das horas extras além da 08ª diária e 44ª semanal, acrescidas do adicional legal, divisor 220"; "Honorários advocatícios (15%)"). Em "subitens", liste cada parcela ou reflexo pedido no texto do tópico, um por item e sem letra de ordem (ex.: "Reflexos em 13º salários", "Reflexos em férias + 1/3 constitucional", "Reflexos em FGTS + 40%", "Reflexos em aviso prévio", "Reflexos em RSR"). Se o tópico não tiver parcelas ou reflexos, deixe "subitens" vazio. Não inclua gratuidade de justiça, Juízo 100% Digital nem valor da causa.

2. Para cada pedido e subitem, escolha em "tipo" como o valor é calculado, entre:
{$types}

3. Preencha "quantidade", "percentual" e "valor_informado" SOMENTE com números tirados dos dados do caso ou do texto do tópico, conforme a regra do tipo; use null quando o dado não existir. Nunca invente nem estime valores em reais. Faça as contas de jornada com os horários da ficha (ex.: jornada realizada das 7h às 19h com 1 h de intervalo, de segunda a sábado, contra 8 h contratadas = 3 h extras/dia × 26 dias = 78 h/mês; use 22 dias/mês de segunda a sexta e 26 de segunda a sábado). Nas horas extras, conte por dia as horas trabalhadas (descontado o intervalo efetivamente usufruído) além da 8ª diária, ou além da jornada contratual se ela for menor, inclusive as dos dias sem trabalho contratado (ex.: sábado); depois acrescente só o que ainda passar da 44ª semanal sem ter sido contado. NÃO some as horas além da 8ª diária com todas as além da 44ª semanal, porque as semanais já contêm as diárias. Converta semana em mês multiplicando por 4,33. O intervalo suprimido é pedido em tipo próprio e não entra nas horas extras.

4. Em "explicacao", diga em uma frase curta de onde saíram os números (ex.: "Jornada realizada 7h–19h com 1 h de intervalo, de segunda a sábado: 3 h extras/dia × 26 dias."). Deixe vazio quando não usar números do caso.

5. "divisor": o divisor da hora indicado nos tópicos ou na ficha (ex.: 220, 200, 180); null se não houver.

Retorne somente JSON válido conforme o schema.
TEXT;

        $nullableNumber = ['type' => ['number', 'null']];
        $lineProperties = [
            'descricao' => ['type' => 'string'],
            'tipo' => ['type' => 'string', 'enum' => array_map(fn (CalculationType $type): string => $type->value, CalculationType::cases())],
            'quantidade' => $nullableNumber,
            'percentual' => $nullableNumber,
            'valor_informado' => $nullableNumber,
            'explicacao' => ['type' => 'string'],
        ];

        return [
            ...ModelOptions::payload(true),
            'instructions' => $instructions,
            'input' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => "DADOS DO CASO:\n".json_encode($caseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                ]],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'pedidos_calculo',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['divisor', 'pedidos'],
                        'properties' => [
                            'divisor' => $nullableNumber,
                            'pedidos' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => [...array_keys($lineProperties), 'subitens'],
                                    'properties' => [
                                        ...$lineProperties,
                                        'subitens' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'additionalProperties' => false,
                                                'required' => array_keys($lineProperties),
                                                'properties' => $lineProperties,
                                            ],
                                        ],
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

        throw new RuntimeException('Não foi possível ler a resposta da IA.');
    }
}
