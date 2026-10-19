<?php

namespace Tests\Feature;

use App\Http\Controllers\PetitionController;
use App\Models\Group;
use App\Models\Petition;
use App\Models\PetitionItem;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PetitionDraftFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Topic $gratuidade;

    private Topic $horasExtras;

    private Topic $multa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Group::factory()->create(['id' => Group::PRELIMINARES_ID, 'name' => 'PRELIMINARES', 'sort_order' => 1]);
        $group = Group::factory()->create(['name' => 'HORAS EXTRAS', 'sort_order' => 2]);

        $this->gratuidade = Topic::factory()->create(['group_id' => Group::PRELIMINARES_ID, 'title' => 'DA GRATUIDADE DE JUSTIÇA', 'content' => 'Requer a gratuidade.']);
        $this->horasExtras = Topic::factory()->create(['group_id' => $group->id, 'title' => 'HORAS EXTRAS', 'content' => 'Durante o período de [PERÍODO TRABALHADO], cumpria a jornada de [JORNADA].']);
        $this->multa = Topic::factory()->create(['group_id' => $group->id, 'title' => 'MULTA DO ART. 477', 'content' => 'Requer a multa.']);
    }

    public function test_saving_creates_a_draft_with_items_in_the_chosen_order(): void
    {
        $response = $this->actingAs($this->user)->post(route('petition.store'), [
            'name' => 'Petição — Maria',
            'preliminares' => [$this->gratuidade->id],
            'topics' => [$this->multa->id, $this->horasExtras->id],
            'action' => 'save',
        ]);

        $petition = Petition::query()->sole();
        $response->assertRedirect(route('petition.edit', $petition));
        $this->assertSame('Petição — Maria', $petition->name);
        $this->assertSame(Petition::STATUS_DRAFT, $petition->status);
        $this->assertSame($this->user->id, $petition->user_id);
        $this->assertSame(
            ['DA GRATUIDADE DE JUSTIÇA', 'MULTA DO ART. 477', 'HORAS EXTRAS'],
            $petition->items->pluck('title')->all(),
        );
    }

    public function test_review_action_goes_to_the_review_page(): void
    {
        $this->actingAs($this->user)->post(route('petition.store'), [
            'topics' => [$this->horasExtras->id],
            'action' => 'review',
        ])->assertRedirect(route('petition.review', Petition::query()->sole()));
    }

    public function test_draft_without_name_gets_a_default_name(): void
    {
        $this->actingAs($this->user)->post(route('petition.store'), ['topics' => [$this->multa->id]]);

        $this->assertStringStartsWith('Petição inicial — ', Petition::query()->sole()->name);
    }

    public function test_changing_the_selection_keeps_review_work_of_topics_that_stay(): void
    {
        $petition = $this->draftWith([$this->horasExtras, $this->multa]);
        $kept = $petition->items->firstWhere('topic_id', $this->horasExtras->id);
        $kept->update(['content' => 'Texto ajustado [JORNADA].', 'field_values' => ['JORNADA' => '8h às 18h'], 'reviewed_at' => now()]);

        $this->actingAs($this->user)->put(route('petition.update', $petition), [
            'name' => $petition->name,
            'topics' => [$this->horasExtras->id],
        ])->assertRedirect(route('petition.edit', $petition));

        $items = $petition->fresh()->items;
        $this->assertCount(1, $items);
        $this->assertSame('Texto ajustado [JORNADA].', $items->first()->content);
        $this->assertNotNull($items->first()->reviewed_at);
    }

    public function test_editing_a_draft_does_not_change_the_library_topic(): void
    {
        $petition = $this->draftWith([$this->horasExtras]);
        $item = $petition->items->first();

        $this->actingAs($this->user)
            ->putJson(route('petition.items.update', [$petition, $item]), ['content' => 'Texto só desta petição.'])
            ->assertOk();

        $this->assertSame('Texto só desta petição.', $item->fresh()->content);
        $this->assertStringContainsString('[PERÍODO TRABALHADO]', $this->horasExtras->fresh()->content);
    }

    public function test_placeholders_become_fields_and_fill_the_text(): void
    {
        $petition = $this->draftWith([$this->horasExtras]);
        $item = $petition->items->first();

        $response = $this->actingAs($this->user)->putJson(route('petition.items.update', [$petition, $item]), [
            'field_values' => ['PERÍODO TRABALHADO' => 'março de 2022 a julho de 2025', 'INVENTADO' => 'ignorar'],
        ]);

        $response->assertOk()
            ->assertJsonPath('item.placeholders', ['PERÍODO TRABALHADO', 'JORNADA'])
            ->assertJsonPath('item.pending', ['JORNADA'])
            ->assertJsonPath('item.rendered', '<p>Durante o período de março de 2022 a julho de 2025, cumpria a jornada de [JORNADA].</p>');
        $this->assertSame(['PERÍODO TRABALHADO' => 'março de 2022 a julho de 2025'], $item->fresh()->field_values);
    }

    public function test_topic_with_pending_information_cannot_be_marked_as_reviewed(): void
    {
        $petition = $this->draftWith([$this->horasExtras]);
        $item = $petition->items->first();

        $this->actingAs($this->user)
            ->putJson(route('petition.items.update', [$petition, $item]), ['reviewed' => true])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Preencha as informações pendentes antes de marcar o tópico como conferido.');

        $this->putJson(route('petition.items.update', [$petition, $item]), [
            'field_values' => ['PERÍODO TRABALHADO' => '2022 a 2025', 'JORNADA' => '8h às 19h'],
            'reviewed' => true,
        ])->assertOk()->assertJsonPath('item.reviewed', true)->assertJsonPath('fully_reviewed', true);
    }

    public function test_changing_a_reviewed_topic_asks_for_a_new_review(): void
    {
        $petition = $this->draftWith([$this->multa]);
        $item = $petition->items->first();
        $item->update(['reviewed_at' => now()]);

        $this->actingAs($this->user)
            ->putJson(route('petition.items.update', [$petition, $item]), ['content' => 'Requer a multa, com ajuste.'])
            ->assertOk()
            ->assertJsonPath('item.reviewed', false);
    }

    public function test_item_from_another_petition_is_not_found(): void
    {
        $petition = $this->draftWith([$this->multa]);
        $other = $this->draftWith([$this->multa]);

        $this->actingAs($this->user)
            ->putJson(route('petition.items.update', [$petition, $other->items->first()]), ['reviewed' => true])
            ->assertNotFound();
    }

    public function test_generate_page_requires_every_topic_reviewed(): void
    {
        $petition = $this->draftWith([$this->multa]);

        $this->actingAs($this->user)->get(route('petition.generate.show', $petition))
            ->assertRedirect(route('petition.review', $petition));
        $this->postJson(route('petition.generate', $petition))->assertUnprocessable();
    }

    public function test_generating_downloads_the_word_file_without_keeping_it(): void
    {
        Storage::fake('local');
        $this->fakeOpenAi();
        $petition = $this->draftWith([$this->gratuidade, $this->horasExtras]);
        $petition->items->firstWhere('topic_id', $this->horasExtras->id)->update([
            'field_values' => ['PERÍODO TRABALHADO' => 'março de 2022 a julho de 2025', 'JORNADA' => '8h às 19h'],
        ]);
        $petition->items()->update(['reviewed_at' => now()]);

        $response = $this->actingAs($this->user)->postJson(route('petition.generate', $petition), ['file_name' => 'Petição Maria']);

        $response->assertOk()->assertDownload('peticao-maria.docx');
        $petition->refresh();
        $this->assertTrue($petition->isGenerated());
        $this->assertNull($petition->file_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertSent(fn ($request): bool => str_contains($request['input'][0]['content'][0]['text'], 'março de 2022 a julho de 2025')
            && ! str_contains($request['input'][0]['content'][0]['text'], '[JORNADA]'));
    }

    public function test_internal_guidance_goes_in_green_with_the_matching_topic(): void
    {
        Storage::fake('local');
        $this->horasExtras->update(['notes' => 'Verificar na CCT o adicional aplicável.']);
        $this->fakeOpenAi([
            ['topic_id' => $this->multa->id, 'ordem' => '02', 'titulo' => 'MULTA DO ART. 477', 'conteudo_html' => '<p>Texto da multa.</p>', 'subtopicos' => []],
            ['topic_id' => $this->horasExtras->id, 'ordem' => '03', 'titulo' => 'HORAS EXTRAS', 'conteudo_html' => '<p>Texto das horas.</p>', 'subtopicos' => []],
        ]);
        $petition = $this->draftWith([$this->horasExtras, $this->multa]);
        $petition->items()->update(['reviewed_at' => now()]);

        $response = $this->actingAs($this->user)->postJson(route('petition.generate', $petition))->assertOk();

        $xml = $this->documentXml($response->baseResponse->getFile()->getPathname());
        $this->assertMatchesRegularExpression(
            '/HORAS EXTRAS\.?<\/w:t>(?:(?!<\/w:p>).)*<\/w:p><w:p><w:pPr>(?:(?!<\/w:pPr>).)*<w:shd[^>]*w:fill="E2EFDA"[^>]*\/>.*?Orientação interna: .*?Verificar na CCT o adicional aplicável\..*?Texto das horas/s',
            $xml,
        );
        $this->assertDoesNotMatchRegularExpression('/MULTA DO ART\. 477\.?<\/w:t>(?:(?!HORAS EXTRAS).)*Orientação interna/s', $xml);
    }

    public function test_review_screen_shows_the_internal_guidance(): void
    {
        $this->horasExtras->update(['notes' => 'Conferir a jornada no cartão de ponto.']);
        $petition = $this->draftWith([$this->horasExtras]);

        $response = $this->actingAs($this->user)->get(route('petition.review', $petition));

        $response->assertOk();
        $this->assertSame(
            'Conferir a jornada no cartão de ponto.',
            PetitionController::itemPayload($response->viewData('petition')->items->first())['notes'],
        );
    }

    public function test_editing_after_generating_turns_it_back_into_a_draft(): void
    {
        $petition = $this->draftWith([$this->multa]);
        $petition->update(['status' => Petition::STATUS_GENERATED]);

        $this->actingAs($this->user)
            ->putJson(route('petition.items.update', [$petition, $petition->items->first()]), ['content' => 'Novo texto.'])
            ->assertOk();

        $this->assertSame(Petition::STATUS_DRAFT, $petition->fresh()->status);
    }

    public function test_list_shows_every_petition_of_the_office_and_the_draft_to_continue(): void
    {
        $mine = $this->draftWith([$this->multa]);
        $colleagues = Petition::factory()->generated()->create(['name' => 'Petição do colega']);

        $response = $this->actingAs($this->user)->get(route('petition.index'));

        $response->assertOk()->assertSee($mine->name)->assertSee('Petição do colega')
            ->assertSee('Rascunhos · 1')->assertSee('Geradas · 1')->assertSee('Gerar novamente');
        $this->assertTrue($response->viewData('continue')->is($mine));
        $this->assertTrue($response->viewData('petitions')->contains($colleagues));
    }

    public function test_review_without_pedidos_goes_back_to_the_selection(): void
    {
        $petition = $this->draftWith([$this->gratuidade]);

        $this->actingAs($this->user)->get(route('petition.review', $petition))
            ->assertRedirect(route('petition.edit', $petition));
    }

    public function test_deleting_removes_the_petition_and_its_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('petitions/9/arquivo.docx', 'conteúdo');
        $petition = Petition::factory()->generated()->create(['file_path' => 'petitions/9/arquivo.docx']);

        $this->actingAs($this->user)->delete(route('petition.destroy', $petition))
            ->assertRedirect(route('petition.index'));

        $this->assertModelMissing($petition);
        Storage::disk('local')->assertMissing('petitions/9/arquivo.docx');
    }

    private function documentXml(string $path): string
    {
        $zip = new \ZipArchive;
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        return $xml;
    }

    /**
     * @param  array<int, Topic>  $topics
     */
    private function draftWith(array $topics): Petition
    {
        $petition = Petition::factory()->create(['user_id' => $this->user->id]);

        foreach ($topics as $position => $topic) {
            PetitionItem::factory()->create([
                'petition_id' => $petition->id,
                'topic_id' => $topic->id,
                'kind' => $topic->group_id === Group::PRELIMINARES_ID ? PetitionItem::KIND_PRELIMINAR : PetitionItem::KIND_PEDIDO,
                'position' => $position + 1,
                'title' => $topic->title,
                'content' => $topic->content,
            ]);
        }

        return $petition->load('items');
    }

    /**
     * @param  array<int, array<string, mixed>>  $topicos
     */
    private function fakeOpenAi(array $topicos = []): void
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
