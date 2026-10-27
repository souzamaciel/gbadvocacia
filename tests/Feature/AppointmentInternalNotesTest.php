<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use App\Services\InternalNotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use ZipArchive;

class AppointmentInternalNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_petition_shows_the_internal_guidance_in_green_under_its_topic(): void
    {
        Group::factory()->create(['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES']);
        $gratuidade = Topic::factory()->create(['group_id' => Group::PRELIMINARES_ID, 'title' => 'DA GRATUIDADE DE JUSTIÇA', 'notes' => 'Juntar declaração de hipossuficiência.']);
        $horas = Topic::factory()->create(['title' => 'HORAS EXTRAS', 'notes' => 'Conferir o adicional na CCT.']);
        $multa = Topic::factory()->create(['title' => 'MULTA DO ART. 477', 'notes' => null]);
        $this->fakeOpenAi(
            [['topic_id' => $gratuidade->id, 'titulo' => 'DA GRATUIDADE DE JUSTIÇA', 'conteudo_html' => '<p>Texto da gratuidade.</p>']],
            [
                ['topic_id' => $horas->id, 'ordem' => '02', 'titulo' => 'HORAS EXTRAS', 'conteudo_html' => '<p>Texto das horas.</p>', 'subtopicos' => []],
                ['topic_id' => $multa->id, 'ordem' => '03', 'titulo' => 'MULTA DO ART. 477', 'conteudo_html' => '<p>Texto da multa.</p>', 'subtopicos' => []],
            ],
        );

        $response = $this->actingAs(User::factory()->create())->post(route('appointment.store'), [
            'cliente_nome' => 'Maria Teste',
            'cliente_cpf' => '123.456.789-00',
            'empregador_nome' => 'Empresa Teste Ltda.',
            'topics' => [$horas->id, $multa->id],
            'preliminares' => [$gratuidade->id],
        ]);

        $this->generationResult($response);
        $petition = Appointment::sole()->petition;
        $petition->items()->update(['reviewed_at' => now()]);

        $this->confirmPedidos($petition);
        $download = $this->downloadGeneration($this->post(route('petition.generate', $petition)))->assertOk();
        $xml = $this->documentXml($download->baseResponse->getFile()->getPathname());

        $this->assertMatchesRegularExpression('/w:fill="E2EFDA".*?Orientação interna: .*?Juntar declaração de hipossuficiência\..*?Texto da gratuidade/s', $xml);
        $this->assertMatchesRegularExpression('/HORAS EXTRAS\.?<\/w:t>.*?w:fill="E2EFDA".*?Conferir o adicional na CCT\..*?Texto das horas/s', $xml);
        $this->assertDoesNotMatchRegularExpression('/MULTA DO ART\. 477\.?<\/w:t>(?:(?!Texto da multa).)*Orientação interna/s', $xml);
    }

    public function test_notes_follow_the_topic_order_when_the_ai_does_not_return_topic_ids(): void
    {
        $data = InternalNotes::attach(
            ['preliminares' => [], 'topicos' => [['titulo' => 'A'], ['titulo' => 'B']]],
            [10 => 'Nota do A', 20 => null],
            ['preliminares' => [], 'topicos' => [10, 20]],
        );

        $this->assertSame('Nota do A', $data['topicos'][0]['orientacao']);
        $this->assertNull($data['topicos'][1]['orientacao']);
    }

    private function documentXml(string $path): string
    {
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    }

    /**
     * @param  array<int, array<string, mixed>>  $preliminares
     * @param  array<int, array<string, mixed>>  $topicos
     */
    private function fakeOpenAi(array $preliminares, array $topicos): void
    {
        Http::fake(['*/responses' => Http::response([
            'id' => 'resp_test',
            'output' => [[
                'type' => 'message',
                'content' => [[
                    'type' => 'output_text',
                    'text' => json_encode([
                        'titulo_acao' => 'RECLAMAÇÃO TRABALHISTA',
                        'preliminares' => $preliminares,
                        'contrato_trabalho_html' => '<p>Contrato.</p>',
                        'topicos' => $topicos,
                        'incluir_honorarios' => false,
                        'honorarios_ordem' => '06',
                        'honorarios_html' => '',
                        'pedidos_ordem' => '07',
                        'pedidos' => [],
                        'requerimentos_finais_html' => '',
                        'valor_causa' => 'R$ 1.000,00',
                        'valor_causa_extenso' => 'mil reais',
                        'pendencias' => [],
                    ]),
                ]],
            ]],
        ])]);
    }
}
