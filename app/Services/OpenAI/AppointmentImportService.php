<?php

namespace App\Services\OpenAI;

use App\Enums\QuestionType;
use App\Models\AppointmentFormQuestion;
use App\Models\Group;
use App\Models\PetitionLog;
use App\Models\Topic;
use App\Services\AppointmentForm;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Reads the text of an attendance form filled in Word and asks the AI to answer the
 * questions of the configured form, plus suggest preliminaries and claims from the
 * library. Answers are checked against the form before being used.
 */
class AppointmentImportService
{
    public function __construct(
        protected HttpFactory $http,
        protected AppointmentForm $form,
    ) {}

    /**
     * @return array{answers: array<string, mixed>, preliminares: list<int>, pedidos: list<int>, reasons: list<array{titulo: string, motivo: string}>, notes: list<string>}
     */
    public function import(string $text): array
    {
        $questions = $this->form->questions()
            ->filter(fn (AppointmentFormQuestion $question): bool => $question->type->isAnswerable() && $question->send_to_ai);
        $topics = Topic::query()->where('active', true)->with('group')->orderedByGroup()->get(['id', 'title', 'group_id']);
        $payload = $this->buildPayload($text, $questions, $topics);

        $response = $this->http
            ->baseUrl(config('services.openai.base_url'))
            ->withToken(config('services.openai.api_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(240)
            ->post('/responses', $payload);

        if (! $response->successful()) {
            Log::error('Erro ao importar ficha de atendimento pela OpenAI', [
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
        $notes = array_values(array_filter(array_map(fn (mixed $note): string => trim((string) $note), (array) ($output['observacoes'] ?? []))));
        $answers = $this->answers((array) ($output['respostas'] ?? []), $questions, $notes);

        $preliminarIds = $topics->filter(fn (Topic $topic): bool => $topic->group_id === Group::PRELIMINARES_ID)->pluck('id')->all();
        $pedidoTitles = $topics->reject(fn (Topic $topic): bool => $topic->group_id === Group::PRELIMINARES_ID)->pluck('title', 'id');
        $pedidos = [];
        $reasons = [];

        foreach ((array) ($output['pedidos'] ?? []) as $pedido) {
            $id = (int) Arr::get($pedido, 'id');

            if (! $pedidoTitles->has($id) || in_array($id, $pedidos, true)) {
                continue;
            }

            $pedidos[] = $id;
            $reasons[] = ['titulo' => $pedidoTitles[$id], 'motivo' => trim((string) Arr::get($pedido, 'motivo', ''))];
        }

        return [
            'answers' => $answers,
            'preliminares' => array_values(array_unique(array_intersect(array_map('intval', (array) ($output['preliminares'] ?? [])), $preliminarIds))),
            'pedidos' => $pedidos,
            'reasons' => $reasons,
            'notes' => $notes,
        ];
    }

    /**
     * Keeps only answers that fit the form: known questions, existing options and valid dates.
     *
     * @param  array<int, mixed>  $items
     * @param  Collection<string, AppointmentFormQuestion>  $questions
     * @param  list<string>  $notes  receives a note for each discarded answer
     * @return array<string, mixed>
     */
    private function answers(array $items, Collection $questions, array &$notes): array
    {
        $answers = [];

        foreach ($items as $item) {
            $question = $questions->get((string) Arr::get($item, 'chave'));

            if ($question === null) {
                continue;
            }

            $value = trim((string) Arr::get($item, 'valor', ''));
            $values = array_values(array_filter(array_map(fn (mixed $v): string => trim((string) $v), (array) Arr::get($item, 'valores', []))));
            $other = trim((string) Arr::get($item, 'outro', ''));
            $key = $question->key;

            switch ($question->type) {
                case QuestionType::SingleChoice:
                case QuestionType::YesNo:
                    if ($value === '') {
                        break;
                    }

                    $choice = $this->matchChoice($value, $question->choices());

                    if ($choice !== null) {
                        $answers[$key] = $choice;
                    } elseif ($question->allowsOther()) {
                        $answers[$key] = AppointmentForm::OTHER;
                        $answers[$key.'_outro'] = $other !== '' ? $other : $value;
                    } else {
                        $notes[] = "“{$question->label}”: a ficha diz “{$value}”, que não é uma das opções. Confira.";
                    }

                    break;

                case QuestionType::MultipleChoice:
                    $chosen = [];
                    $unmatched = [];

                    foreach ($values as $candidate) {
                        $choice = $this->matchChoice($candidate, $question->choices());
                        $choice !== null ? $chosen[] = $choice : $unmatched[] = $candidate;
                    }

                    if ($other !== '') {
                        $unmatched[] = $other;
                    }

                    if ($unmatched !== [] && $question->allowsOther()) {
                        $chosen[] = AppointmentForm::OTHER;
                        $answers[$key.'_outro'] = implode('; ', array_unique($unmatched));
                    } elseif ($unmatched !== []) {
                        $notes[] = "“{$question->label}”: não encontrei a opção para “".implode('; ', $unmatched).'”. Confira.';
                    }

                    if ($chosen !== []) {
                        $answers[$key] = array_values(array_unique($chosen));
                    }

                    break;

                case QuestionType::Date:
                    $date = $this->date($value);

                    if ($date !== null) {
                        $answers[$key] = $date;
                    } elseif ($value !== '') {
                        $notes[] = "“{$question->label}”: a data “{$value}” não pôde ser lida. Confira.";
                    }

                    break;

                case QuestionType::Repeater:
                    $rows = $this->rows((array) Arr::get($item, 'linhas', []), $question);

                    if ($rows !== []) {
                        $answers[$key] = $rows;
                    }

                    break;

                default:
                    if ($value !== '') {
                        $answers[$key] = $value;
                    }
            }
        }

        return $answers;
    }

    /**
     * @param  list<string>  $choices
     */
    private function matchChoice(string $value, array $choices): ?string
    {
        $normalize = fn (string $text): string => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $text) ?? ''));

        foreach ($choices as $choice) {
            if ($normalize($choice) === $normalize($value)) {
                return $choice;
            }
        }

        return null;
    }

    private function date(string $value): ?string
    {
        foreach (['d/m/Y', 'Y-m-d', 'd/m/y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);

                if ($date !== false && $date->format($format) === $value) {
                    return $date->format('Y-m-d');
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return list<array<string, string>>
     */
    private function rows(array $rows, AppointmentFormQuestion $question): array
    {
        $result = [];

        foreach ($rows as $row) {
            $cells = [];

            foreach ((array) Arr::get($row, 'celulas', []) as $cell) {
                $name = mb_strtolower(trim((string) Arr::get($cell, 'coluna', '')));
                $column = collect($question->columns())->first(fn (array $column): bool => $column['key'] === $name || mb_strtolower($column['label']) === $name);
                $value = trim((string) Arr::get($cell, 'valor', ''));

                if ($column === null || $value === '') {
                    continue;
                }

                $cells[$column['key']] = $column['type'] === QuestionType::Date->value ? ($this->date($value) ?? $value) : $value;
            }

            if ($cells !== []) {
                $result[] = $cells;
            }
        }

        return $result;
    }

    /**
     * @param  Collection<string, AppointmentFormQuestion>  $questions
     * @param  Collection<int, Topic>  $topics
     * @return array<string, mixed>
     */
    private function buildPayload(string $text, Collection $questions, Collection $topics): array
    {
        $stepTitles = $this->form->steps()->pluck('title', 'id');

        $form = $questions->map(fn (AppointmentFormQuestion $question): array => array_filter([
            'chave' => $question->key,
            'assunto' => $stepTitles[$question->appointment_form_step_id] ?? null,
            'pergunta' => $question->label,
            'tipo' => $question->type->value,
            'opcoes' => $question->type->hasChoices() || $question->type === QuestionType::YesNo ? $question->choices() : null,
            'aceita_outros' => $question->allowsOther() ?: null,
            'colunas' => $question->type === QuestionType::Repeater ? array_map(fn (array $column): string => $column['key'], $question->columns()) : null,
        ], fn (mixed $value): bool => $value !== null))->values()->all();

        $library = $topics->map(fn (Topic $topic): array => [
            'id' => $topic->id,
            'titulo' => $topic->title,
            'grupo' => $topic->group?->name,
            'tipo' => $topic->group_id === Group::PRELIMINARES_ID ? 'preliminar' : 'pedido',
        ])->values()->all();

        $instructions = <<<'TEXT'
Você ajuda um escritório de advocacia trabalhista. Recebe o texto de uma FICHA DE ATENDIMENTO preenchida no Word durante a conversa com o cliente e deve transferir as respostas para a ficha do sistema.

Como ler a ficha:
- Opções marcadas aparecem como "(X)", "( X )", "[X]", "(x)" ou com algum texto dentro dos parênteses. Opções vazias "( )" ou "[ ]" NÃO foram marcadas.
- Linhas "____" ou espaços em branco significam que a informação não foi preenchida.
- As anotações podem estar logo após a pergunta, na linha seguinte ou em tabelas.

Regras para as respostas ("respostas"):
- Responda SOMENTE as perguntas da ficha do sistema (lista "FICHA DO SISTEMA"), usando a "chave" exata de cada uma.
- Inclua apenas perguntas que a ficha preenchida realmente responde. Não invente, não deduza dados que não estão escritos e não copie textos de instrução da ficha (ex.: "VERIFICAR DATA CORRETA").
- Copie nomes, números, valores e textos como estão escritos, corrigindo apenas espaços sobrando.
- Tipos "single_choice" e "yes_no": "valor" deve ser exatamente uma das "opcoes". Se a resposta não for nenhuma das opções e a pergunta "aceita_outros", use "valor": "Outros" e escreva a resposta em "outro".
- Tipo "multiple_choice": "valores" com as opções marcadas, escritas exatamente como em "opcoes". Respostas fora das opções vão em "outro" (separe por "; ").
- Tipo "date": "valor" no formato dd/mm/aaaa.
- Tipo "money": "valor" como "R$ 1.234,56".
- Tipo "repeater": uma entrada em "linhas" para cada item (ex.: cada função, empresa ou testemunha), com "celulas" usando as "colunas" indicadas.
- Nos demais tipos, "valor" com o texto da resposta.
- Para campos que não se aplicam, deixe "valor" vazio, "valores" e "linhas" como listas vazias e "outro" vazio.
- Nunca informe senhas.

Sugestão de petição:
- "preliminares": ids das preliminares da BIBLIOTECA que se aplicam ao caso (em geral gratuidade de justiça e Juízo 100% Digital, salvo indicação contrária).
- "pedidos": os pedidos da BIBLIOTECA com fundamento CLARO nos fatos escritos na ficha, na ordem lógica de uma petição trabalhista. Para cada um, "id" e "motivo" (uma frase citando o fato da ficha que o sustenta).
- Sugira um pedido SOMENTE quando a ficha descreve um fato que corresponde diretamente ao título do pedido. Na dúvida, não sugira.
- Nunca sugira pedido sobre um benefício, verba ou situação que a ficha não menciona ou que o cliente não tinha (ex.: não sugira vale-alimentação se o cliente não recebia vale-alimentação; não sugira indenização do seguro-desemprego se o cliente conseguiu solicitá-lo).
- Escolha a variação do pedido que corresponde ao caso (ex.: horas extras comuns × jornada 12x36) e não repita pedidos sobre o mesmo fato.

"observacoes": frases curtas e ESPECÍFICAS DESTE CASO para o advogado conferir: informações importantes que faltam, respostas confusas ou contraditórias e fatos relevantes que não têm pedido correspondente na biblioteca. Não repita instruções padrão da ficha (ex.: "solicitar documentos", "verificar data da dispensa"). No máximo 8.

Retorne somente JSON válido conforme o schema.
TEXT;

        return [
            ...ModelOptions::payload(),
            'instructions' => $instructions,
            'input' => [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => "FICHA DO SISTEMA (perguntas a responder):\n".json_encode($form, JSON_UNESCAPED_UNICODE)
                        ."\n\nBIBLIOTECA (preliminares e pedidos disponíveis):\n".json_encode($library, JSON_UNESCAPED_UNICODE)
                        ."\n\nFICHA PREENCHIDA (texto do Word):\n".$text,
                ]],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'appointment_import',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['respostas', 'preliminares', 'pedidos', 'observacoes'],
                        'properties' => [
                            'respostas' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['chave', 'valor', 'valores', 'outro', 'linhas'],
                                    'properties' => [
                                        'chave' => ['type' => 'string'],
                                        'valor' => ['type' => 'string'],
                                        'valores' => ['type' => 'array', 'items' => ['type' => 'string']],
                                        'outro' => ['type' => 'string'],
                                        'linhas' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'object',
                                                'additionalProperties' => false,
                                                'required' => ['celulas'],
                                                'properties' => [
                                                    'celulas' => [
                                                        'type' => 'array',
                                                        'items' => [
                                                            'type' => 'object',
                                                            'additionalProperties' => false,
                                                            'required' => ['coluna', 'valor'],
                                                            'properties' => [
                                                                'coluna' => ['type' => 'string'],
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
                            'preliminares' => ['type' => 'array', 'items' => ['type' => 'integer']],
                            'pedidos' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'additionalProperties' => false,
                                    'required' => ['id', 'motivo'],
                                    'properties' => [
                                        'id' => ['type' => 'integer'],
                                        'motivo' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                            'observacoes' => ['type' => 'array', 'items' => ['type' => 'string']],
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
