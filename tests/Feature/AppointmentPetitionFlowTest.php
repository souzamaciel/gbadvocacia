<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Group;
use App\Models\Petition;
use App\Models\PetitionItem;
use App\Models\Topic;
use App\Models\User;
use App\Services\PetitionQualification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppointmentPetitionFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Topic $horas;

    private Topic $gratuidade;

    private const FORM = [
        'cliente_nome' => 'Maria Ferreira',
        'cliente_estado_civil' => 'casada',
        'cliente_cpf' => '123.456.789-00',
        'cliente_endereco' => 'Rua Vinólia',
        'cliente_numero' => '598',
        'cliente_complemento' => 'apto 2',
        'empregador_nome' => 'Empresa Ltda.',
        'empregador_cnpj' => '42.446.277/0001-92',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Group::factory()->create(['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES', 'sort_order' => 1]);
        $this->gratuidade = Topic::factory()->create(['group_id' => Group::PRELIMINARES_ID, 'title' => 'DA GRATUIDADE DE JUSTIÇA', 'content' => 'Requer a gratuidade.']);
        $this->horas = Topic::factory()->create(['title' => 'HORAS EXTRAS', 'content' => 'Cumpria a jornada de [JORNADA], com adicional [ADICIONAL].']);
    }

    public function test_concluding_builds_a_petition_with_the_form_qualification_and_ai_suggestions(): void
    {
        $this->fakeAi(['itens' => [['id' => 0, 'valores' => []]]], fillFor: 'HORAS EXTRAS', values: [
            ['variavel' => 'JORNADA', 'valor' => '7h às 19h'],
            ['variavel' => 'INVENTADA', 'valor' => 'não deve entrar'],
        ]);

        $petition = $this->conclude();

        $this->assertSame('Petição — Maria Ferreira', $petition->name);
        $this->assertSame(Appointment::sole()->id, $petition->appointment_id);
        $this->assertSame(
            [PetitionItem::KIND_QUALIFICACAO, PetitionItem::KIND_PRELIMINAR, PetitionItem::KIND_PEDIDO],
            $petition->items->pluck('kind')->all(),
        );

        $qualification = $petition->items->first();
        $this->assertSame('Maria Ferreira', $qualification->field_values['NOME DO RECLAMANTE']);
        $this->assertSame('598, apto 2', $qualification->field_values['número do reclamante']);
        $this->assertSame('42.446.277/0001-92', $qualification->field_values['CNPJ']);
        $this->assertStringContainsString('residente e domiciliada na', $qualification->content);

        $horas = $petition->items->firstWhere('topic_id', $this->horas->id);
        $this->assertSame(['JORNADA' => '7h às 19h'], $horas->field_values);
        $this->assertSame(['ADICIONAL'], $horas->pendingPlaceholders());
        $this->assertTrue($petition->items->every(fn (PetitionItem $item): bool => $item->reviewed_at === null));
    }

    public function test_service_takers_become_extra_defendants_in_the_opening(): void
    {
        $this->fakeAi(['itens' => []]);

        $petition = $this->conclude(['tomadoras' => [
            ['nome' => 'Hospital Municipal de Ipatinga', 'cnpj' => '98.765.432/0001-10', 'endereco' => 'Rua das Flores, 10', 'bairro' => 'Centro', 'cidade' => 'Ipatinga', 'uf' => 'MG', 'cep' => '35160-000'],
            ['nome' => '', 'cnpj' => ''],
        ]]);

        $qualification = $petition->items->first();
        $this->assertSame(1, PetitionQualification::extraDefendants($qualification->content));
        $this->assertSame('Hospital Municipal de Ipatinga', $qualification->field_values['NOME DA 2ª RECLAMADA']);
        $this->assertSame('98.765.432/0001-10', $qualification->field_values['CNPJ da 2ª reclamada']);

        $text = strip_tags(str_replace('</p>', "\n", $qualification->renderedContent()));
        $this->assertStringContainsString('EMPRESA LTDA., pessoa jurídica', $text);
        $this->assertMatchesRegularExpression('/CEP \[CEP da reclamada\];\n/u', $text);
        $this->assertStringContainsString('HOSPITAL MUNICIPAL DE IPATINGA, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº 98.765.432/0001-10, com sede na Rua das Flores, 10, Centro, Ipatinga/MG, CEP 35160-000, de acordo com as razões a seguir aduzidas:', $text);
    }

    public function test_removing_the_service_taker_brings_back_the_single_defendant_opening(): void
    {
        $this->fakeAi(['itens' => []]);
        $this->conclude(['tomadoras' => [['nome' => 'Hospital Municipal de Ipatinga']]]);

        $petition = $this->conclude(['appointment_id' => Appointment::sole()->id, 'tomadoras' => []]);

        $this->assertSame(0, PetitionQualification::extraDefendants($petition->items->first()->content));
        $this->assertStringEndsWith('CEP [CEP da reclamada], de acordo com as razões a seguir aduzidas:</p>', $petition->items->first()->content);
    }

    public function test_the_gov_password_never_reaches_the_ai(): void
    {
        $this->fakeAi(['itens' => []]);

        $this->conclude(['afastamento_houve' => 'Sim', 'afastamento_senha_gov' => 'segredo123']);

        Http::assertSent(fn (Request $request): bool => str_contains($request->body(), 'Maria Ferreira'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->body(), 'segredo123'));
    }

    public function test_ai_failure_still_opens_the_review_with_blank_variables(): void
    {
        Http::fake(['*/responses' => Http::response(['error' => 'indisponível'], 500)]);

        $petition = $this->conclude();

        $this->assertSame([], $petition->items->firstWhere('topic_id', $this->horas->id)->field_values ?? []);
        $this->assertSame('Maria Ferreira', $petition->items->first()->field_values['NOME DO RECLAMANTE']);
    }

    public function test_concluding_again_keeps_the_review_of_topics_that_stay(): void
    {
        $this->fakeAi(['itens' => []]);
        $petition = $this->conclude();
        $horas = $petition->items->firstWhere('topic_id', $this->horas->id);
        $horas->update(['field_values' => ['JORNADA' => '8h às 18h', 'ADICIONAL' => 'legal'], 'reviewed_at' => now()]);

        $again = $this->conclude(['appointment_id' => Appointment::sole()->id, 'cliente_nome' => 'Maria Ferreira Souza']);

        $this->assertSame($petition->id, $again->id);
        $this->assertNotNull($again->items->firstWhere('topic_id', $this->horas->id)->reviewed_at);
        $this->assertSame('Maria Ferreira Souza', $again->items->first()->field_values['NOME DO RECLAMANTE']);
        $this->assertNull($again->items->first()->reviewed_at);
    }

    public function test_generating_sends_the_form_to_the_ai_and_marks_the_appointment_as_generated(): void
    {
        $this->fakeAi(['itens' => []]);
        $petition = $this->conclude(['jornada_cidade' => 'Coronel Fabriciano']);
        $petition->items()->update(['reviewed_at' => now()]);

        $this->downloadGeneration($this->actingAs($this->user)->post(route('petition.generate', $petition)))->assertOk();

        Http::assertSent(fn (Request $request): bool => str_contains($request->body(), 'ficha_atendimento')
            && str_contains($request->body(), 'Coronel Fabriciano')
            && str_contains($request->body(), 'contrato_trabalho_html'));
        $this->assertNotNull(Appointment::sole()->generated_at);
    }

    public function test_lists_link_the_appointment_and_its_petition(): void
    {
        $this->fakeAi(['itens' => []]);
        $petition = $this->conclude();

        $this->actingAs($this->user)->get(route('appointment.index'))
            ->assertSee('Abrir petição')
            ->assertSee(route('petition.review', $petition), false);
        $this->actingAs($this->user)->get(route('petition.index'))
            ->assertSee('Do atendimento de Maria Ferreira');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function conclude(array $extra = []): Petition
    {
        $response = $this->actingAs($this->user)->post(route('appointment.store'), [
            ...self::FORM,
            'topics' => [$this->horas->id],
            'preliminares' => [$this->gratuidade->id],
            ...$extra,
        ]);
        $this->generationResult($response);

        return Appointment::sole()->petition()->with('items')->sole();
    }

    /**
     * Fakes every AI call: the variable suggestions answer with $fill (pointing at the
     * item titled $fillFor) and the generation with an empty petition body.
     *
     * @param  array<string, mixed>  $fill
     * @param  list<array{variavel: string, valor: string}>  $values
     */
    private function fakeAi(array $fill, ?string $fillFor = null, array $values = []): void
    {
        Http::fake(['*/responses' => function (Request $request) use ($fill, $fillFor, $values) {
            if (str_contains($request->body(), 'topic_variables')) {
                if ($fillFor !== null) {
                    $id = PetitionItem::query()->where('title', $fillFor)->value('id');
                    $fill = ['itens' => [['id' => $id, 'valores' => $values]]];
                }

                return Http::response(['id' => 'x', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode($fill)]]]]]);
            }

            return Http::response(['id' => 'y', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode([
                'titulo_acao' => 'RECLAMAÇÃO', 'cidade_vara' => '', 'preliminares' => [], 'contrato_trabalho_html' => '<p>Contrato.</p>', 'topicos' => [],
                'incluir_honorarios' => false, 'honorarios_ordem' => '06', 'honorarios_html' => '', 'pedidos_ordem' => '07', 'pedidos' => [],
                'requerimentos_finais_html' => '', 'valor_causa' => 'R$ 1,00', 'valor_causa_extenso' => 'um real', 'pendencias' => [],
            ])]]]]]);
        }]);
    }
}
