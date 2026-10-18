<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TopicVariableSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private const TEXT = '<p>Durante o período de março de 2022 a julho de 2025, a parte autora, <strong>Maria Aparecida Ferreira</strong>, cumpria a jornada das 08h às 19h, além do limite de 44 horas semanais. Notificar a [NOME DA RECLAMADA].</p>';

    public function test_ai_suggestions_are_returned_in_text_order_and_only_when_the_passage_exists(): void
    {
        $this->fakeSuggestions([
            ['trecho' => 'das 08h às 19h', 'nome' => 'Jornada realizada', 'categoria' => 'JORNADA', 'atencao' => false, 'motivo' => ''],
            ['trecho' => 'março de 2022 a julho de 2025', 'nome' => 'Período trabalhado', 'categoria' => 'PERÍODO', 'atencao' => false, 'motivo' => ''],
            ['trecho' => 'Maria Aparecida Ferreira', 'nome' => '[Nome da parte autora]', 'categoria' => 'PESSOA', 'atencao' => false, 'motivo' => ''],
            ['trecho' => '44 horas semanais', 'nome' => 'Limite semanal', 'categoria' => 'OUTRO', 'atencao' => true, 'motivo' => 'Pode ser o limite constitucional.'],
            ['trecho' => 'João da Silva', 'nome' => 'Inventado', 'categoria' => 'PESSOA', 'atencao' => false, 'motivo' => ''],
            ['trecho' => '[NOME DA RECLAMADA]', 'nome' => 'Reclamada', 'categoria' => 'EMPRESA', 'atencao' => false, 'motivo' => ''],
            ['trecho' => 'das 08h às 19h', 'nome' => 'Repetido', 'categoria' => 'JORNADA', 'atencao' => false, 'motivo' => ''],
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->postJson(route('settings.topics.suggest-variables'), ['content' => self::TEXT]);

        $response->assertOk()
            ->assertJsonCount(4, 'suggestions')
            ->assertJsonPath('suggestions.0.trecho', 'março de 2022 a julho de 2025')
            ->assertJsonPath('suggestions.1.nome', 'Nome da parte autora')
            ->assertJsonPath('suggestions.2.trecho', 'das 08h às 19h')
            ->assertJsonPath('suggestions.3.atencao', true)
            ->assertJsonPath('suggestions.3.motivo', 'Pode ser o limite constitucional.');

        Http::assertSent(fn (Request $request): bool => str_contains($request['input'][0]['content'][0]['text'], 'Maria Aparecida Ferreira')
            && ! str_contains($request['input'][0]['content'][0]['text'], '<strong>'));
    }

    public function test_ai_suggests_a_title_and_one_of_the_existing_groups(): void
    {
        $group = Group::factory()->create(['name' => 'HORAS EXTRAS']);
        Group::factory()->create(['name' => 'DANOS MORAIS']);
        $this->fakeSuggestions([], 'DAS HORAS EXTRAS HABITUAIS', 'HORAS EXTRAS');

        $this->actingAs(User::factory()->create())
            ->postJson(route('settings.topics.suggest-variables'), ['content' => self::TEXT])
            ->assertOk()
            ->assertJsonPath('title', 'DAS HORAS EXTRAS HABITUAIS')
            ->assertJsonPath('group_id', $group->id);

        Http::assertSent(fn (Request $request): bool => str_contains($request['instructions'], 'HORAS EXTRAS; DANOS MORAIS')
            || str_contains($request['instructions'], 'DANOS MORAIS; HORAS EXTRAS'));
    }

    public function test_unknown_group_from_the_ai_is_ignored(): void
    {
        Group::factory()->create(['name' => 'HORAS EXTRAS']);
        $this->fakeSuggestions([], 'DO INTERVALO', 'GRUPO INVENTADO');

        $this->actingAs(User::factory()->create())
            ->postJson(route('settings.topics.suggest-variables'), ['content' => self::TEXT])
            ->assertOk()
            ->assertJsonPath('title', 'DO INTERVALO')
            ->assertJsonPath('group_id', null);
    }

    public function test_nothing_is_saved_when_asking_for_suggestions(): void
    {
        $this->fakeSuggestions([]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('settings.topics.suggest-variables'), ['content' => self::TEXT])
            ->assertOk()
            ->assertJsonPath('suggestions', []);

        $this->assertDatabaseCount('topics', 0);
    }

    public function test_empty_text_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('settings.topics.suggest-variables'), ['content' => '<p><br></p>'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content' => 'Cole o texto da petição antes de pedir sugestões à IA.']);
    }

    public function test_ai_failure_returns_a_friendly_message(): void
    {
        Http::fake(['*/responses' => Http::response(['error' => 'quota'], 429)]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('settings.topics.suggest-variables'), ['content' => self::TEXT])
            ->assertStatus(502)
            ->assertJsonPath('message', 'Não foi possível analisar o texto agora. Tente novamente ou crie as variáveis manualmente.');
    }

    public function test_guests_cannot_ask_for_suggestions(): void
    {
        $this->postJson(route('settings.topics.suggest-variables'), ['content' => self::TEXT])->assertUnauthorized();
    }

    /**
     * @param  array<int, array<string, mixed>>  $suggestions
     */
    private function fakeSuggestions(array $suggestions, string $title = '', string $group = ''): void
    {
        Http::fake(['*/responses' => Http::response([
            'id' => 'resp_test',
            'output' => [[
                'type' => 'message',
                'content' => [[
                    'type' => 'output_text',
                    'text' => json_encode(['sugestoes' => $suggestions, 'titulo' => $title, 'grupo' => $group]),
                ]],
            ]],
        ])]);
    }
}
