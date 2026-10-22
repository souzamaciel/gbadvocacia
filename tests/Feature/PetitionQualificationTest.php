<?php

namespace Tests\Feature;

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
use ZipArchive;

class PetitionQualificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Topic $pedido;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Group::factory()->create(['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES', 'sort_order' => 1]);
        $this->pedido = Topic::factory()->create(['title' => 'HORAS EXTRAS', 'content' => 'Texto das horas.']);
    }

    public function test_new_petition_starts_with_the_qualification_to_review(): void
    {
        $this->actingAs($this->user)->post(route('petition.store'), ['topics' => [$this->pedido->id]]);

        $first = Petition::query()->sole()->items->first();
        $this->assertSame(PetitionItem::KIND_QUALIFICACAO, $first->kind);
        $this->assertSame(PetitionQualification::TITLE, $first->title);
        $this->assertContains('NOME DO RECLAMANTE', $first->placeholders());
        $this->assertContains('CNPJ', $first->placeholders());
        $this->assertContains('CIDADE DA VARA', $first->placeholders());
    }

    public function test_changing_the_selection_keeps_the_filled_qualification(): void
    {
        $this->actingAs($this->user)->post(route('petition.store'), ['topics' => [$this->pedido->id]]);
        $petition = Petition::query()->sole();
        $qualification = $petition->items->first();
        $qualification->update(['field_values' => ['CPF' => '090.179.646-85'], 'reviewed_at' => now()]);
        $other = Topic::factory()->create();

        $this->actingAs($this->user)->put(route('petition.update', $petition), ['name' => $petition->name, 'topics' => [$other->id]]);

        $this->assertSame(['CPF' => '090.179.646-85'], $qualification->fresh()->field_values);
        $this->assertSame(1, $petition->items()->where('kind', PetitionItem::KIND_QUALIFICACAO)->count());
    }

    public function test_older_drafts_get_the_qualification_when_the_review_opens(): void
    {
        $petition = Petition::factory()->create(['user_id' => $this->user->id]);
        PetitionItem::factory()->create(['petition_id' => $petition->id, 'topic_id' => $this->pedido->id, 'kind' => PetitionItem::KIND_PEDIDO, 'position' => 1]);

        $this->actingAs($this->user)->get(route('petition.review', $petition))->assertOk();

        $this->assertSame(PetitionItem::KIND_QUALIFICACAO, $petition->fresh()->items->first()->kind);
        $this->assertFalse($petition->fresh()->isFullyReviewed());
    }

    public function test_the_word_opens_with_the_reviewed_qualification_which_is_not_sent_to_the_ai(): void
    {
        $this->actingAs($this->user)->post(route('petition.store'), ['topics' => [$this->pedido->id]]);
        $petition = Petition::query()->sole();
        $petition->items()->where('kind', PetitionItem::KIND_QUALIFICACAO)->sole()->update(['field_values' => [
            'NOME DO RECLAMANTE' => 'Gabriel Alves de Macedo',
            'CIDADE DA VARA' => 'Coronel Fabriciano',
            'ESTADO DA VARA' => 'Minas Gerais',
            'CPF' => '090.179.646-85',
        ]]);
        $petition->items()->update(['reviewed_at' => now()]);
        $this->fakeOpenAi();

        $response = $this->downloadGeneration($this->actingAs($this->user)->postJson(route('petition.generate', $petition)))->assertOk();

        $text = $this->documentText($response->baseResponse->getFile()->getPathname());
        $this->assertStringContainsString('AO DOUTO JUÍZO DA ___ª VARA DO TRABALHO DE CORONEL FABRICIANO/MINAS GERAIS', $text);
        $this->assertStringContainsString('GABRIEL ALVES DE MACEDO, [nacionalidade]', $text);
        $this->assertStringContainsString('CPF 090.179.646-85', $text);
        $this->assertSame(1, substr_count($text, 'AÇÃO TRABALHISTA'));
        $this->assertStringNotContainsString(PetitionQualification::TITLE, $text);

        Http::assertSent(fn (Request $request): bool => ! str_contains($request->body(), '090.179.646-85')
            && ! str_contains($request->body(), 'Gabriel'));
    }

    private function documentText(string $path): string
    {
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return html_entity_decode(strip_tags(str_replace('</w:p>', "\n", $xml)));
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
                        'cidade_vara' => '',
                        'preliminares' => [],
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
