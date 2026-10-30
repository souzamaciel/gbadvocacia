<?php

namespace Tests\Feature;

use App\Mail\SuggestionReceived;
use App\Models\ActivityLog;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuggestionTest extends TestCase
{
    use RefreshDatabase;

    private const VALID = [
        'type' => 'novo',
        'area' => 'peticoes',
        'impact' => 'alta',
        'title' => 'Duplicar uma petição gerada',
        'description' => 'Seria ótimo poder copiar uma petição pronta para outro cliente.',
        'page_title' => 'Petições iniciais',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.sugestoes.email' => 'dono@escritorio.test']);
        Mail::fake();
        Storage::fake('local');
    }

    public function test_any_user_can_send_a_suggestion_that_is_saved_logged_and_emailed(): void
    {
        $user = User::factory()->withPermissions([])->create(['name' => 'Carla', 'email' => 'carla@escritorio.test']);

        $this->actingAs($user)->postJson(route('suggestions.store'), [...self::VALID, 'page_url' => url('/petitions')])
            ->assertCreated()
            ->assertJsonPath('message', 'Sugestão enviada. Obrigado por ajudar a melhorar o sistema!');

        $suggestion = Suggestion::sole();
        $this->assertSame('Carla', $suggestion->user_name);
        $this->assertSame(url('/petitions'), $suggestion->page_url);
        $this->assertNotNull($suggestion->emailed_at);
        $this->assertSame('suggestion.sent', ActivityLog::query()->sole()->event);

        Mail::assertSent(SuggestionReceived::class, function (SuggestionReceived $mail): bool {
            return $mail->hasTo('dono@escritorio.test')
                && $mail->hasReplyTo('carla@escritorio.test')
                && $mail->envelope()->subject === '[Sugestão #'.$mail->suggestion->id.'] Novo recurso: Duplicar uma petição gerada';
        });
    }

    public function test_screenshots_are_stored_and_attached(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('suggestions.store'), [
            ...self::VALID,
            'images' => [UploadedFile::fake()->image('Tela erro.png'), UploadedFile::fake()->image('outra.png')],
        ], ['Accept' => 'application/json'])->assertCreated();

        $suggestion = Suggestion::sole();
        $this->assertSame(['1-tela-erro.png', '2-outra.png'], array_column($suggestion->attachments, 'name'));
        Storage::disk('local')->assertExists($suggestion->attachments[0]['path']);
        Mail::assertSent(SuggestionReceived::class, fn (SuggestionReceived $mail): bool => count($mail->attachments()) === 2);

        $html = (new SuggestionReceived($suggestion))->render();
        $this->assertStringContainsString('2 prints enviados (também em anexo):', $html);
        $this->assertSame(2, substr_count($html, '<img src="data:image/png;base64,'));
        $this->assertStringContainsString('Novo recurso', $html);
    }

    public function test_invalid_suggestions_get_portuguese_messages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('suggestions.store'), ['type' => 'x', 'title' => 'Oi'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'area', 'impact', 'title', 'description'])
            ->assertJsonPath('errors.title.0', 'O título precisa ter pelo menos 5 caracteres.');

        $this->actingAs($user)->post(route('suggestions.store'), [...self::VALID, 'images' => [UploadedFile::fake()->create('virus.exe', 10)]], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors(['images.0' => 'Envie apenas imagens (prints da tela).']);

        $this->assertSame(0, Suggestion::count());
        Mail::assertNothingSent();
    }

    public function test_the_impact_answer_follows_the_type(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('suggestions.store'), [...self::VALID, 'type' => 'erro', 'impact' => 'alta'])->assertCreated();

        $suggestion = Suggestion::sole();
        $this->assertSame('Impede o meu trabalho', $suggestion->impactLabel());
        $html = (new SuggestionReceived($suggestion))->render();
        $this->assertStringContainsString('Qual a gravidade do erro?', $html);
        $this->assertStringContainsString('Impede o meu trabalho', $html);

        $this->actingAs(User::factory()->create())->postJson(route('suggestions.store'), [...self::VALID, 'impact' => 'ajudaria'])
            ->assertJsonValidationErrors(['impact']);
    }

    public function test_external_page_addresses_are_not_kept(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('suggestions.store'), [...self::VALID, 'page_url' => 'https://site-estranho.example/x'])->assertCreated();

        $this->assertNull(Suggestion::sole()->page_url);
    }

    public function test_without_a_destination_the_suggestion_is_still_saved(): void
    {
        config(['services.sugestoes.email' => null]);

        $this->actingAs(User::factory()->create())->postJson(route('suggestions.store'), self::VALID)->assertCreated();

        $this->assertNull(Suggestion::sole()->emailed_at);
        Mail::assertNothingSent();
    }

    public function test_the_button_is_on_every_screen_with_the_area_already_chosen(): void
    {
        $this->actingAs(User::factory()->create())->get(route('petition.index'))
            ->assertOk()
            ->assertSee('Sugerir melhoria')
            ->assertSee('<option value="peticoes" selected>Petições</option>', false);
    }

    public function test_guests_cannot_send_suggestions(): void
    {
        $this->postJson(route('suggestions.store'), self::VALID)->assertUnauthorized();
    }
}
