<?php

namespace Tests\Feature;

use App\Models\AppointmentFormQuestion;
use App\Models\AppointmentFormStep;
use App\Models\User;
use App\Services\AppointmentForm;
use App\Services\PetitionFormSerializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use ZipArchive;

class AppointmentFormAnswersTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_updated_office_form_is_loaded_as_the_first_version(): void
    {
        $this->assertSame(12, AppointmentFormStep::count());
        $this->assertSame(
            count(AppointmentFormQuestion::SYSTEM_KEYS),
            AppointmentFormQuestion::query()->where('is_system', true)->count(),
        );
        $this->assertEqualsCanonicalizing(
            ['cliente_nome', 'cliente_cpf', 'empregador_nome'],
            AppointmentFormQuestion::query()->where('required', true)->pluck('key')->all(),
        );
        $this->assertFalse(AppointmentFormQuestion::query()->where('key', 'afastamento_senha_gov')->value('send_to_ai'));
        $this->assertContains('Facial', AppointmentFormQuestion::query()->where('key', 'ponto_tipo')->first()->choices());
    }

    public function test_attendance_page_shows_every_step_and_the_fixed_orders_step(): void
    {
        $this->actingAs(User::factory()->create())->get(route('appointment.create'))
            ->assertOk()
            ->assertSee('Urgência e prazos')
            ->assertSee('Há urgência na ação?')
            ->assertSee('1 de 13')
            ->assertSee('Configurar ficha');
    }

    public function test_answers_sent_to_the_ai_are_readable_and_skip_hidden_and_private_ones(): void
    {
        $normalized = PetitionFormSerializer::normalizeFormData([
            'cliente_nome' => 'Maria Ferreira',
            'cliente_cpf' => '123.456.789-00',
            'empregador_nome' => 'Empresa Ltda.',
            'urgencia_motivos' => ['Prescrição próxima', 'Outros'],
            'urgencia_motivos_outro' => 'Cliente mudará de cidade',
            'urgencia_seguro_beneficio' => 'Não',
            'urgencia_qual_beneficio' => 'Auxílio-doença',
            'contrato_admissao' => '2021-03-01',
            'afastamento_houve' => 'Sim',
            'afastamento_senha_gov' => 'segredo123',
            'funcoes' => [['nome' => 'Auxiliar', 'de' => '2021-03-01', 'ate' => '']],
        ]);

        $this->assertSame('Maria Ferreira', $normalized['cliente_nome']);
        $pairs = collect($normalized['ficha_atendimento'])->keyBy('pergunta');

        $this->assertSame(['Prescrição próxima', 'Outros: Cliente mudará de cidade'], $pairs['Há urgência na ação?']['resposta']);
        $this->assertSame('01/03/2021', $pairs['Admissão']['resposta']);
        $this->assertSame(['Função: Auxiliar | De: 01/03/2021'], $pairs['Quais foram as funções exercidas e os períodos de cada uma?']['resposta']);
        $this->assertFalse($pairs->has('Qual benefício?'));
        $this->assertStringNotContainsString('segredo123', json_encode($normalized));
    }

    public function test_required_fields_must_be_filled_before_generating_the_petition(): void
    {
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->postJson(route('appointment.store'), ['cliente_nome' => 'Maria'])
            ->assertStatus(422)
            ->assertJsonPath('missing.0.key', 'cliente_cpf')
            ->assertJsonPath('missing.1.key', 'empregador_nome');

        Http::assertNothingSent();
    }

    public function test_hidden_required_question_is_not_demanded(): void
    {
        AppointmentFormQuestion::query()->where('key', 'urgencia_qual_beneficio')->update(['required' => true]);

        $base = ['cliente_nome' => 'Maria', 'cliente_cpf' => '1', 'empregador_nome' => 'Empresa'];
        $form = app(AppointmentForm::class);

        $this->assertSame([], $form->missingRequired([...$base, 'urgencia_seguro_beneficio' => 'Não']));
        $this->assertSame('urgencia_qual_beneficio', app(AppointmentForm::class)->missingRequired([...$base, 'urgencia_seguro_beneficio' => 'Sim'])[0]['key']);
    }

    public function test_gov_password_never_reaches_the_ai(): void
    {
        Http::fake(['*/responses' => Http::response(['id' => 'x', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode(['titulo_acao' => 'RECLAMAÇÃO', 'preliminares' => [], 'contrato_trabalho_html' => '', 'topicos' => [], 'incluir_honorarios' => false, 'honorarios_ordem' => '06', 'honorarios_html' => '', 'pedidos_ordem' => '07', 'pedidos' => [], 'requerimentos_finais_html' => '', 'valor_causa' => 'R$ 1,00', 'valor_causa_extenso' => 'um real', 'pendencias' => []])]]]]])]);

        $this->actingAs(User::factory()->create())->post(route('appointment.store'), [
            'cliente_nome' => 'Maria',
            'cliente_cpf' => '123.456.789-00',
            'empregador_nome' => 'Empresa',
            'afastamento_houve' => 'Sim',
            'afastamento_senha_gov' => 'segredo123',
        ])->assertAccepted();

        Http::assertSent(fn (Request $request): bool => ! str_contains($request->body(), 'segredo123'));
    }

    public function test_printed_form_follows_the_configuration_and_names_the_attendant(): void
    {
        $user = User::factory()->create(['name' => 'Carla Mendes']);

        $response = $this->actingAs($user)->post(route('appointment.print'), [
            'cliente_nome' => 'Maria Ferreira',
            'ponto_tinha_controle' => 'Sim',
            'ponto_tipo' => ['Facial'],
            'afastamento_houve' => 'Sim',
            'afastamento_senha_gov' => 'segredo123',
        ]);

        $response->assertOk();
        $zip = new ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertStringContainsString('FICHA DE ATENDIMENTO', $xml);
        $this->assertStringContainsString('Facial', $xml);
        $this->assertStringContainsString('segredo123', $xml);
        $this->assertStringContainsString('Carla Mendes', $xml);
        $this->assertStringContainsString('Maria Ferreira', $xml);
        $this->assertStringNotContainsString('?:', $xml);
        $this->assertStringContainsString('<w:tbl>', $xml);
    }
}
