<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Petition;
use App\Models\Topic;
use App\Models\User;
use App\Services\PetitionDocxGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class PetitionDocxPreliminaresTest extends TestCase
{
    use RefreshDatabase;

    private const JUIZO_DIGITAL_TEXT = 'A tramitação do processo no Juízo 100% digital';

    private const GRATUIDADE_TEXT = 'Os benefícios da Assistência Judiciária Gratuita';

    public function test_final_requests_include_both_preliminares_when_selected(): void
    {
        $text = $this->generateMeritText([Topic::JUIZO_100_DIGITAL_ID, Topic::GRATUIDADE_JUSTICA_ID]);

        $this->assertStringContainsString(self::JUIZO_DIGITAL_TEXT, $text);
        $this->assertStringContainsString(self::GRATUIDADE_TEXT, $text);
    }

    public function test_unselected_preliminar_is_left_out_of_the_final_requests(): void
    {
        $text = $this->generateMeritText([Topic::GRATUIDADE_JUSTICA_ID]);

        $this->assertStringNotContainsString(self::JUIZO_DIGITAL_TEXT, $text);
        $this->assertStringContainsString(self::GRATUIDADE_TEXT, $text);
    }

    public function test_no_preliminar_text_when_none_are_selected(): void
    {
        $text = $this->generateMeritText([]);

        $this->assertStringNotContainsString(self::JUIZO_DIGITAL_TEXT, $text);
        $this->assertStringNotContainsString(self::GRATUIDADE_TEXT, $text);
    }

    public function test_unchecking_a_preliminar_on_the_petition_screen_removes_it_from_the_document(): void
    {
        Group::factory()->create(['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES', 'sort_order' => 1]);
        Topic::factory()->create(['id' => Topic::JUIZO_100_DIGITAL_ID, 'group_id' => Group::PRELIMINARES_ID, 'title' => 'JUÍZO 100% DIGITAL']);
        Topic::factory()->create(['id' => Topic::GRATUIDADE_JUSTICA_ID, 'group_id' => Group::PRELIMINARES_ID, 'title' => 'DA GRATUIDADE DE JUSTIÇA']);
        $pedido = Topic::factory()->create();
        $this->fakeOpenAi();

        Storage::fake('local');
        $this->actingAs(User::factory()->create())->post(route('petition.store'), [
            'topics' => [$pedido->id],
            'preliminares' => [Topic::GRATUIDADE_JUSTICA_ID],
        ]);
        $petition = Petition::query()->sole();
        $petition->items()->update(['reviewed_at' => now()]);

        $response = $this->downloadGeneration($this->post(route('petition.generate', $petition)))->assertOk();

        $text = $this->docxText($response->baseResponse->getFile()->getPathname());
        $this->assertStringNotContainsString(self::JUIZO_DIGITAL_TEXT, $text);
        $this->assertStringContainsString(self::GRATUIDADE_TEXT, $text);
    }

    /**
     * @param  array<int, int>  $selectedIds
     */
    private function generateMeritText(array $selectedIds): string
    {
        $path = (new PetitionDocxGenerator)->generateMerit([
            'preliminares_selecionadas_ids' => $selectedIds,
            'contrato_trabalho_html' => '<p>Contrato.</p>',
        ]);

        return $this->docxText($path);
    }

    private function docxText(string $path): string
    {
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($path);

        return html_entity_decode(strip_tags($xml));
    }

    private function fakeOpenAi(): void
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
