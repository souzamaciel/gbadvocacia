<?php

namespace App\Services\OpenAI;

use App\Models\PetitionItem;
use App\Models\PetitionLog;
use App\Services\RichText;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Suggests, from the attendance form, the value of each [variable] of the petition's
 * topics. Nothing is final: the lawyer checks every value on the review screen, and a
 * variable the form does not answer stays blank.
 */
class TopicVariableFillService
{
    public function __construct(
        protected HttpFactory $http
    ) {}

    /**
     * @param  array<string, mixed>  $caseData  qualification keys plus "ficha_atendimento" (question/answer pairs)
     * @param  Collection<int, PetitionItem>  $items  items whose variables are still blank
     * @return array<int, array<string, string>> suggested values by item id, then by variable name
     */
    public function fill(array $caseData, Collection $items): array
    {
        $pending = $items
            ->map(fn (PetitionItem $item): array => [
                'id' => $item->id,
                'titulo' => $item->title,
                'texto' => RichText::toPlainText($item->content),
                'variaveis' => $item->pendingPlaceholders(),
            ])
            ->filter(fn (array $item): bool => $item['variaveis'] !== [])
            ->values();

        if ($pending->isEmpty()) {
            return [];
        }

        $payload = $this->buildPayload($caseData, $pending->all());

        $response = $this->http
            ->baseUrl(config('services.openai.base_url'))
            ->withToken(config('services.openai.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(180)
            ->post('/responses', $payload);

        if (! $response->successful()) {
            Log::error('Erro ao preencher variáveis dos tópicos pela OpenAI', [
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

        $allowed = $pending->mapWithKeys(fn (array $item): array => [$item['id'] => $item['variaveis']]);
        $values = [];

        foreach ($this->extractStructuredOutput($json)['itens'] ?? [] as $item) {
            $id = (int) ($item['id'] ?? 0);

            foreach ($item['valores'] ?? [] as $pair) {
                $name = (string) ($pair['variavel'] ?? '');
                $value = trim((string) ($pair['valor'] ?? ''));

                if ($value !== '' && in_array($name, $allowed[$id] ?? [], true)) {
                    $values[$id][$name] = mb_substr($value, 0, 1000);
                }
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $caseData
     * @param  list<array{id: int, titulo: string, texto: string, variaveis: list<string>}>  $items
     * @return array<string, mixed>
     */
    private function buildPayload(array $caseData, array $items): array
    {
        $instructions = <<<'TEXT'
Você ajuda um escritório de advocacia trabalhista a montar uma petição inicial.

Recebe os DADOS DO CASO (qualificação e respostas da ficha de atendimento) e os TÓPICOS da petição. Cada tópico tem um texto-modelo com variáveis entre colchetes e a lista "variaveis" que precisam de valor.

Para cada variável, informe o valor que deve substituí-la no texto, usando SOMENTE o que os dados do caso dizem:
- O valor entra no lugar da variável dentro da frase: escreva só o trecho que cabe ali, com a concordância certa, sem repetir o texto ao redor e sem colchetes.
- Copie nomes, datas, números e valores como estão nos dados do caso.
- Quando a variável oferece alternativas separadas por "/" (ex.: "convencional de 100% (cl. XX da CCT)/legal"), escolha a que os dados do caso indicam.
- "CIDADE DA VARA" e "ESTADO DA VARA" são da Vara do Trabalho competente: a cidade do local da prestação de serviço informada na ficha e o seu estado escrito POR EXTENSO (ex.: "Minas Gerais", nunca "MG").
- Se os dados do caso não informam o valor com segurança, devolva "valor": "" (vazio). Nunca invente nem estime.
- Responda todas as variáveis listadas, usando o nome exato de cada uma.

Retorne somente JSON válido conforme o schema.
TEXT;

        return [
            ...ModelOptions::payload(true),
            'instructions' => $instructions,
            'input' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => "DADOS DO CASO:\n".json_encode($caseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                        ."\n\nTÓPICOS:\n".json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                ]],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'topic_variables',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['itens'],
                        'properties' => [
                            'itens' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['id', 'valores'],
                                    'properties' => [
                                        'id' => ['type' => 'integer'],
                                        'valores' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'additionalProperties' => false,
                                                'required' => ['variavel', 'valor'],
                                                'properties' => [
                                                    'variavel' => ['type' => 'string'],
                                                    'valor' => ['type' => 'string'],
                                                ],
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
