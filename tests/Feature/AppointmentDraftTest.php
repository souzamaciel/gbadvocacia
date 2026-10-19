<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Appointment;
use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AppointmentDraftTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_draft_keeps_the_answers_and_topics_but_not_private_answers(): void
    {
        $pedido = Topic::factory()->create();

        $this->actingAs($this->user)->postJson(route('appointment.draft.store'), [
            'cliente_nome' => 'Maria Ferreira',
            'urgencia_motivos' => ['Outros'],
            'urgencia_motivos_outro' => 'Mudança de cidade',
            'funcoes' => [['nome' => 'Auxiliar', 'de' => '', 'ate' => ''], ['nome' => '', 'de' => '', 'ate' => '']],
            'afastamento_houve' => 'Sim',
            'afastamento_senha_gov' => 'segredo123',
            'campo_inexistente' => 'x',
            'topics' => [$pedido->id],
            'preliminares' => [],
        ])->assertOk()->assertJsonStructure(['id', 'edit_url', 'update_url', 'saved_at']);

        $appointment = Appointment::sole();
        $this->assertSame('Maria Ferreira', $appointment->client_name);
        $this->assertSame($this->user->id, $appointment->user_id);
        $this->assertSame('Mudança de cidade', $appointment->answers['urgencia_motivos_outro']);
        $this->assertCount(1, $appointment->answers['funcoes']);
        $this->assertArrayNotHasKey('afastamento_senha_gov', $appointment->answers);
        $this->assertArrayNotHasKey('campo_inexistente', $appointment->answers);
        $this->assertSame([$pedido->id], $appointment->pedidos);
    }

    public function test_draft_is_updated_and_reopened_with_its_answers(): void
    {
        $preliminar = Topic::factory()->create(['group_id' => Group::factory()->create(['id' => Group::PRELIMINARES_ID])->id, 'title' => 'DA GRATUIDADE']);
        $appointment = Appointment::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->putJson(route('appointment.draft.update', $appointment), [
            'cliente_nome' => 'João Silva',
            'preliminares' => [$preliminar->id],
        ])->assertOk();

        $this->assertSame('João Silva', $appointment->fresh()->client_name);

        $this->actingAs($this->user)->get(route('appointment.edit', $appointment))
            ->assertOk()
            ->assertSee('João Silva')
            ->assertSee('Rascunho salvo em')
            ->assertViewHas('prefill', fn (array $prefill): bool => $prefill['answers']['cliente_nome'] === 'João Silva'
                && $prefill['preliminares'] === [['id' => $preliminar->id, 'title' => 'DA GRATUIDADE']]);
    }

    public function test_list_shows_the_office_drafts_and_deletes_one(): void
    {
        $mine = Appointment::factory()->create(['user_id' => $this->user->id, 'client_name' => 'Maria Ferreira']);
        Appointment::factory()->create(['client_name' => null]);

        $this->actingAs($this->user)->get(route('appointment.index'))
            ->assertOk()
            ->assertSee('Maria Ferreira')
            ->assertSee('Atendimento sem nome');

        $this->actingAs($this->user)->delete(route('appointment.destroy', $mine))->assertRedirect(route('appointment.index'));
        $this->assertModelMissing($mine);
    }

    public function test_generating_the_petition_saves_the_appointment_as_a_draft(): void
    {
        Http::fake(['*/responses' => Http::response(['id' => 'x', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['titulo_acao' => 'RECLAMAÇÃO', 'preliminares' => [], 'contrato_trabalho_html' => '', 'topicos' => [], 'incluir_honorarios' => false, 'honorarios_ordem' => '06', 'honorarios_html' => '', 'pedidos_ordem' => '07', 'pedidos' => [], 'requerimentos_finais_html' => '', 'valor_causa' => 'R$ 1,00', 'valor_causa_extenso' => 'um real', 'pendencias' => []])]]]]])]);

        $response = $this->actingAs($this->user)->post(route('appointment.store'), [
            'cliente_nome' => 'Maria Ferreira',
            'cliente_cpf' => '123.456.789-00',
            'empregador_nome' => 'Empresa Ltda.',
        ]);

        $response->assertOk();
        $appointment = Appointment::sole();
        $this->assertNotNull($appointment->generated_at);
        $this->assertSame((string) $appointment->id, $response->headers->get('X-Appointment-Id'));

        $this->actingAs($this->user)->post(route('appointment.store'), [
            'appointment_id' => $appointment->id,
            'cliente_nome' => 'Maria Ferreira Souza',
            'cliente_cpf' => '123.456.789-00',
            'empregador_nome' => 'Empresa Ltda.',
        ])->assertOk();

        $this->assertSame(1, Appointment::count());
        $this->assertSame('Maria Ferreira Souza', $appointment->fresh()->client_name);
    }

    public function test_drafts_need_the_appointments_permission(): void
    {
        $user = User::factory()->withPermissions([Permission::PetitionsEdit])->create();
        $appointment = Appointment::factory()->create();

        $this->actingAs($user)->get(route('appointment.index'))->assertForbidden();
        $this->actingAs($user)->postJson(route('appointment.draft.store'), ['cliente_nome' => 'X'])->assertForbidden();
        $this->actingAs($user)->delete(route('appointment.destroy', $appointment))->assertForbidden();
    }
}
