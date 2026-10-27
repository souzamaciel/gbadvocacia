<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Petition;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PetitionCreatePageTest extends TestCase
{
    use RefreshDatabase;

    private Group $secondGroup;

    private Group $firstGroup;

    protected function setUp(): void
    {
        parent::setUp();

        Group::factory()->create(['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES', 'sort_order' => 1]);
        Topic::factory()->create(['group_id' => Group::PRELIMINARES_ID, 'title' => 'DA GRATUIDADE DE JUSTIÇA', 'sort_order' => 1]);

        $this->secondGroup = Group::factory()->create(['name' => 'GRUPO SEGUNDO', 'sort_order' => 3]);
        $this->firstGroup = Group::factory()->create(['name' => 'GRUPO PRIMEIRO', 'sort_order' => 2]);

        Topic::factory()->create(['group_id' => $this->secondGroup->id, 'title' => 'PEDIDO B1', 'sort_order' => 1]);
        Topic::factory()->create(['group_id' => $this->firstGroup->id, 'title' => 'PEDIDO A2', 'sort_order' => 2]);
        Topic::factory()->create(['group_id' => $this->firstGroup->id, 'title' => 'PEDIDO A1', 'sort_order' => 1]);
        Topic::factory()->create(['group_id' => $this->firstGroup->id, 'title' => 'PEDIDO INATIVO', 'sort_order' => 3, 'active' => false]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('petition.create'))->assertRedirect(route('login'));
    }

    public function test_groups_are_listed_in_their_configured_order(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('petition.create'));

        $response->assertOk();
        $response->assertSeeInOrder(['PRELIMINARES', 'DA GRATUIDADE DE JUSTIÇA', 'GRUPO PRIMEIRO', 'PEDIDO A1', 'PEDIDO A2', 'GRUPO SEGUNDO', 'PEDIDO B1']);
        $this->assertSame(
            ['PRELIMINARES', 'GRUPO PRIMEIRO', 'GRUPO SEGUNDO'],
            $response->viewData('groups')->pluck('name')->all(),
        );
    }

    public function test_pedidos_search_list_follows_group_order_then_topic_order(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('petition.create'));

        $this->assertSame(
            ['PEDIDO A1', 'PEDIDO A2', 'PEDIDO B1'],
            $response->viewData('pedidos')->pluck('title')->all(),
        );
    }

    public function test_preliminares_are_listed_as_the_first_group_and_mirrored_in_the_right_panel(): void
    {
        $gratuidade = Topic::query()->where('title', 'DA GRATUIDADE DE JUSTIÇA')->firstOrFail();

        $response = $this->actingAs(User::factory()->create())->get(route('petition.create'));

        $response->assertSeeInOrder(['data-id="'.$gratuidade->id.'"', 'data-kind="preliminar"'], false);
        $response->assertSee('id="selected-preliminares"', false);
        $response->assertDontSee('preliminares-count', false);
        $this->assertSame(
            [['id' => $gratuidade->id, 'title' => 'DA GRATUIDADE DE JUSTIÇA']],
            $response->viewData('preliminares')->all(),
        );
        $this->assertNotContains('DA GRATUIDADE DE JUSTIÇA', $response->viewData('pedidos')->pluck('title')->all());
    }

    public function test_preliminares_stay_first_even_if_another_group_has_a_lower_position(): void
    {
        $this->firstGroup->update(['sort_order' => 0]);

        $response = $this->actingAs(User::factory()->create())->get(route('petition.create'));

        $this->assertSame('PRELIMINARES', $response->viewData('groups')->first()->name);
    }

    public function test_appointment_page_uses_the_same_pedidos_order(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('appointment.create'));

        $response->assertOk();
        $this->assertSame(
            ['PEDIDO A1', 'PEDIDO A2', 'PEDIDO B1'],
            $response->viewData('pedidos')->pluck('title')->all(),
        );
    }

    public function test_checked_preliminares_are_sent_to_the_ai_as_preliminares(): void
    {
        Http::fake(['*/responses' => Http::response([
            'id' => 'resp_test',
            'output' => [[
                'type' => 'message',
                'content' => [[
                    'type' => 'output_text',
                    'text' => json_encode([
                        'titulo_acao' => 'RECLAMAÇÃO TRABALHISTA',
                        'preliminares' => [['titulo' => 'DA GRATUIDADE DE JUSTIÇA', 'conteudo_html' => '<p>Gratuidade.</p>']],
                        'contrato_trabalho_html' => '<p>Contrato.</p>',
                        'topicos' => [],
                        'incluir_honorarios' => false,
                        'honorarios_ordem' => '06',
                        'honorarios_html' => '',
                        'pedidos_ordem' => '07',
                        'pedidos' => [],
                        'requerimentos_finais_html' => '<p>Requerimentos.</p>',
                        'valor_causa' => 'R$ 1.000,00',
                        'valor_causa_extenso' => 'mil reais',
                        'pendencias' => [],
                    ]),
                ]],
            ]],
        ])]);

        $gratuidade = Topic::query()->where('title', 'DA GRATUIDADE DE JUSTIÇA')->firstOrFail();
        $pedido = Topic::query()->where('title', 'PEDIDO A1')->firstOrFail();

        Storage::fake('local');
        $this->actingAs(User::factory()->create())->post(route('petition.store'), [
            'topics' => [$pedido->id],
            'preliminares' => [$gratuidade->id],
        ]);
        $petition = Petition::query()->sole();
        $petition->items()->update(['reviewed_at' => now()]);

        $this->confirmPedidos($petition);
        $response = $this->post(route('petition.generate', $petition));

        $this->assertSame('done', $this->generationResult($response)['status']);
        Http::assertSent(function (Request $request): bool {
            $caseData = $request['input'][0]['content'][0]['text'];

            return str_contains($caseData, '"preliminares_selecionadas"')
                && str_contains($caseData, 'DA GRATUIDADE DE JUSTIÇA')
                && str_contains($caseData, 'PEDIDO A1');
        });
    }

    public function test_appointment_page_keeps_its_own_preliminares_panel(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('appointment.create'));

        $response->assertSee('preliminares-count', false);
        $response->assertSee('1 preliminar selecionada');
        $response->assertSeeInOrder(['preliminar-checkbox', 'checked'], false);
        $response->assertDontSee('id="preliminares-selected"', false);
    }
}
