<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\Group;
use App\Models\Petition;
use App\Models\PetitionItem;
use App\Models\Topic;
use App\Models\User;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use ZipArchive;

class PedidoValuesFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Topic $horas;

    private Topic $danos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-26 10:00:00');
        $this->user = User::factory()->create();
        Group::factory()->create(['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES', 'sort_order' => 1]);
        $this->horas = Topic::factory()->create(['title' => 'HORAS EXTRAS', 'content' => 'Cumpria jornada das 7h às 19h.']);
        $this->danos = Topic::factory()->create(['title' => 'DANOS MORAIS', 'content' => 'Requer indenização.']);
    }

    public function test_values_step_needs_every_topic_reviewed(): void
    {
        $petition = $this->petition(reviewed: false);

        $this->actingAs($this->user)->get(route('petition.values.show', $petition))->assertRedirect(route('petition.review', $petition));
        $this->actingAs($this->user)->postJson(route('petition.values.calculate', $petition))->assertUnprocessable();
    }

    public function test_first_visit_calculates_in_the_background(): void
    {
        $petition = $this->petition();

        $this->actingAs($this->user)->get(route('petition.values.show', $petition))
            ->assertOk()
            ->assertSee('Calculando os valores dos pedidos')
            ->assertSee('Valores dos pedidos');
    }

    public function test_ai_classifies_and_the_system_does_the_math(): void
    {
        $this->fakeClassification();
        $petition = $this->petition();

        $status = $this->generationResult($this->actingAs($this->user)->postJson(route('petition.values.calculate', $petition)));

        $this->assertSame('done', $status['status']);
        $this->assertSame(route('petition.values.show', $petition), $status['redirect']);

        $petition->refresh();
        $this->assertNull($petition->pedidos_confirmed_at);
        $this->assertSame(3567330, $petition->pedidos[0]['valor']);
        $this->assertSame(3567330, $petition->pedidos[0]['sugerido']);
        $this->assertSame(594555, $petition->pedidos[0]['subitens'][0]['valor']);
        $this->assertNull($petition->pedidos[1]['valor']);

        Http::assertSent(fn (Request $request): bool => $request['text']['format']['name'] === 'pedidos_calculo'
            && str_contains($request['input'][0]['content'][0]['text'], 'Cumpria jornada das 7h às 19h')
            && str_contains($request['input'][0]['content'][0]['text'], 'ficha_atendimento'));

        $this->actingAs($this->user)->get(route('petition.values.show', $petition))
            ->assertOk()
            ->assertSee('Pagamento das horas extras')
            ->assertSee('value="R$ 35.673,30"', false)
            ->assertSee('78 h extras/mês × hora de R$ 10,00')
            ->assertSee('R$ 41.618,85')
            ->assertSee('1 valor em branco')
            ->assertSee('Informe o valor pretendido');
    }

    public function test_failed_calculation_explains_without_saving(): void
    {
        Http::fake(['*/responses' => Http::response(['error' => 'x'], 500)]);
        $petition = $this->petition();

        $status = $this->generationResult($this->actingAs($this->user)->postJson(route('petition.values.calculate', $petition)));

        $this->assertSame('failed', $status['status']);
        $this->assertSame('Não foi possível calcular os valores agora. Tente novamente em instantes.', $status['message']);
        $this->assertNull($petition->fresh()->pedidos);
    }

    public function test_confirming_keeps_the_typed_amounts_and_opens_the_generation(): void
    {
        $petition = $this->calculated();

        $this->actingAs($this->user)->put(route('petition.values.update', $petition), ['pedidos' => [
            ['valor' => 'R$ 30.000,00', 'subitens' => [['valor' => '5.000,00']]],
            ['valor' => '20000'],
        ]])->assertRedirect(route('petition.generate.show', $petition));

        $petition->refresh();
        $this->assertTrue($petition->hasConfirmedPedidos());
        $this->assertSame(3000000, $petition->pedidos[0]['valor']);
        $this->assertSame(3567330, $petition->pedidos[0]['sugerido']);
        $this->assertSame(500000, $petition->pedidos[0]['subitens'][0]['valor']);
        $this->assertSame(2000000, $petition->pedidos[1]['valor']);

        $this->actingAs($this->user)->get(route('petition.values.show', $petition))
            ->assertSee('Valores confirmados')
            ->assertSee('Cálculo do sistema: R$ 35.673,30');
        $this->actingAs($this->user)->get(route('petition.generate.show', $petition))->assertOk();
    }

    public function test_blank_amounts_stay_blank_and_invalid_ones_are_refused(): void
    {
        $petition = $this->calculated();

        $this->actingAs($this->user)->from(route('petition.values.show', $petition))
            ->put(route('petition.values.update', $petition), ['pedidos' => [['valor' => 'trinta mil']]])
            ->assertRedirect(route('petition.values.show', $petition))
            ->assertSessionHasErrors(['pedidos.0.valor' => 'Use o formato R$ 1.234,56.']);
        $this->assertNull($petition->fresh()->pedidos_confirmed_at);

        $this->actingAs($this->user)->put(route('petition.values.update', $petition), ['pedidos' => [['valor' => '', 'subitens' => [['valor' => '']]]]]);

        $this->assertNull($petition->fresh()->pedidos[0]['valor']);
        $this->assertTrue($petition->fresh()->hasConfirmedPedidos());
    }

    public function test_generation_waits_for_the_confirmed_values(): void
    {
        $petition = $this->calculated();

        $this->actingAs($this->user)->get(route('petition.generate.show', $petition))->assertRedirect(route('petition.values.show', $petition));
        $this->actingAs($this->user)->postJson(route('petition.generate', $petition))
            ->assertUnprocessable()
            ->assertJson(['message' => 'Confira os valores dos pedidos antes de gerar o documento.']);
    }

    public function test_changing_the_topics_or_the_ficha_asks_for_a_new_calculation(): void
    {
        $petition = $this->calculated();
        $this->actingAs($this->user)->put(route('petition.values.update', $petition), ['pedidos' => [['valor' => '1,00']]]);
        $this->assertTrue($petition->fresh()->hasConfirmedPedidos());

        $petition->appointment->update(['answers' => [...$petition->appointment->answers, 'salario_ultimo' => 'R$ 3.000,00']]);

        $this->assertFalse($petition->fresh()->hasConfirmedPedidos());
        $this->actingAs($this->user)->get(route('petition.values.show', $petition))->assertSee('Calculando os valores dos pedidos');
        $this->actingAs($this->user)->put(route('petition.values.update', $petition), ['pedidos' => [['valor' => '1,00']]])
            ->assertRedirect(route('petition.values.show', $petition));
    }

    public function test_word_prints_the_confirmed_amounts_without_highlight(): void
    {
        $this->fakeGeneration();
        $petition = $this->calculated();
        $this->actingAs($this->user)->put(route('petition.values.update', $petition), ['pedidos' => [
            ['valor' => 'R$ 30.000,00', 'subitens' => [['valor' => '']]],
            ['valor' => 'R$ 20.000,00'],
        ]]);

        $download = $this->downloadGeneration($this->actingAs($this->user)->postJson(route('petition.generate', $petition)))->assertOk();
        $text = $this->documentText($download->baseResponse->getFile()->getPathname());
        $xml = $this->documentXml($download->baseResponse->getFile()->getPathname());

        $this->assertStringContainsString("a) Pagamento das horas extras\tR$ 30.000,00", $text);
        $this->assertStringContainsString("a.1) Reflexos em RSR\tR$ ________", $text);
        $this->assertStringContainsString("b) Indenização por danos morais\tR$ 20.000,00", $text);
        $this->assertStringContainsString('Dá à causa o valor de R$ 50.000,00 (cinquenta mil reais)', $text);
        $this->assertStringNotContainsString('Pedido inventado pela IA', $text);
        $this->assertStringNotContainsString('<w:highlight', $xml);
    }

    public function test_editor_without_the_generate_permission_stays_on_the_values(): void
    {
        $editor = User::factory()->withPermissions([Permission::PetitionsEdit])->create();
        $petition = $this->calculated();

        $this->actingAs($editor)->get(route('petition.review', $petition))->assertSee(route('petition.values.show', $petition), false);
        $this->actingAs($editor)->put(route('petition.values.update', $petition), ['pedidos' => [['valor' => '1,00']]])
            ->assertRedirect(route('petition.values.show', $petition))
            ->assertSessionHas('status', 'Valores confirmados. A geração do documento fica com quem tem essa permissão no perfil.');
    }

    private function petition(bool $reviewed = true): Petition
    {
        $appointment = Appointment::factory()->create(['answers' => [
            'cliente_nome' => 'Maria Ferreira',
            'contrato_admissao' => '2024-03-01',
            'contrato_dispensa' => '2026-09-15',
            'salario_ultimo' => 'R$ 2.200,00',
            'extincao_causa' => 'Sem justa causa',
            'jornada_horario_realizado' => '7h às 19h, com 1 h de intervalo',
        ]]);
        $petition = Petition::factory()->create(['user_id' => $this->user->id, 'appointment_id' => $appointment->id]);

        foreach ([$this->horas, $this->danos] as $position => $topic) {
            PetitionItem::query()->create([
                'petition_id' => $petition->id,
                'topic_id' => $topic->id,
                'kind' => PetitionItem::KIND_PEDIDO,
                'position' => $position + 1,
                'title' => $topic->title,
                'content' => $topic->content,
                'reviewed_at' => $reviewed ? now() : null,
            ]);
        }

        return $petition;
    }

    private function calculated(): Petition
    {
        $this->fakeClassification();
        $petition = $this->petition();
        $this->generationResult($this->actingAs($this->user)->postJson(route('petition.values.calculate', $petition)));

        return $petition->fresh();
    }

    private function fakeClassification(): void
    {
        $line = fn (string $description, string $type, ?float $quantity = null, array $subitems = [], string $explanation = ''): array => [
            'descricao' => $description,
            'tipo' => $type,
            'quantidade' => $quantity,
            'percentual' => null,
            'valor_informado' => null,
            'explicacao' => $explanation,
            'subitens' => $subitems,
        ];

        Http::fake(['*/responses' => Http::response($this->aiResponse([
            'divisor' => null,
            'pedidos' => [
                $line('Pagamento das horas extras', 'horas_extras', 78, [
                    array_diff_key($line('Reflexos em RSR', 'reflexo_rsr'), ['subitens' => true]),
                ], '3 h extras/dia × 26 dias.'),
                $line('Indenização por danos morais', 'indenizacao'),
            ],
        ]))]);
    }

    private function fakeGeneration(): void
    {
        Http::fake(['*/responses' => Http::sequence()
            ->push($this->aiResponse([
                'divisor' => null,
                'pedidos' => [
                    ['descricao' => 'Pagamento das horas extras', 'tipo' => 'horas_extras', 'quantidade' => 78, 'percentual' => null, 'valor_informado' => null, 'explicacao' => '', 'subitens' => [
                        ['descricao' => 'Reflexos em RSR', 'tipo' => 'reflexo_rsr', 'quantidade' => null, 'percentual' => null, 'valor_informado' => null, 'explicacao' => ''],
                    ]],
                    ['descricao' => 'Indenização por danos morais', 'tipo' => 'indenizacao', 'quantidade' => null, 'percentual' => null, 'valor_informado' => null, 'explicacao' => '', 'subitens' => []],
                ],
            ]))
            ->push($this->aiResponse([
                'titulo_acao' => 'RECLAMAÇÃO TRABALHISTA',
                'cidade_vara' => '',
                'preliminares' => [],
                'contrato_trabalho_html' => '<p>Contrato.</p>',
                'topicos' => [],
                'incluir_honorarios' => false,
                'honorarios_ordem' => '06',
                'honorarios_html' => '',
                'pedidos_ordem' => '07',
                'pedidos' => [['descricao' => 'Pedido inventado pela IA', 'valor' => 'R$ 99.999,00', 'subitens' => []]],
                'requerimentos_finais_html' => '',
                'valor_causa' => 'R$ 99.999,00',
                'valor_causa_extenso' => '',
                'pendencias' => [],
            ]))]);
    }

    /**
     * @param  array<string, mixed>  $output
     * @return array<string, mixed>
     */
    private function aiResponse(array $output): array
    {
        return ['id' => 'resp_test', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($output)]]]]];
    }

    private function documentXml(string $path): string
    {
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    }

    private function documentText(string $path): string
    {
        $dom = new DOMDocument;
        $dom->loadXML($this->documentXml($path));
        $paragraphs = [];

        foreach ($dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'p') as $paragraph) {
            $text = '';
            foreach ($paragraph->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', '*') as $node) {
                $text .= match ($node->localName) {
                    't' => $node->textContent,
                    'tab' => $node->parentNode->localName === 'r' ? "\t" : '',
                    default => '',
                };
            }
            $paragraphs[] = $text;
        }

        return implode("\n", $paragraphs);
    }
}
