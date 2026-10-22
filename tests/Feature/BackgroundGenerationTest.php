<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackgroundGeneration;
use App\Services\GenerationFailed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class BackgroundGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Route::middleware('web')->post('/_teste/gerar/{modo}', function (string $modo, BackgroundGeneration $generations) {
            $token = $generations->start(auth()->id(), function (string $token) use ($modo, $generations): array {
                return match ($modo) {
                    'arquivo' => (function () use ($token, $generations): array {
                        $path = tempnam(sys_get_temp_dir(), 'w');
                        file_put_contents($path, 'conteúdo');
                        $generations->storeFile($token, $path, 'teste.docx');

                        return [];
                    })(),
                    'aviso' => throw new GenerationFailed('Mensagem clara para o usuário.'),
                    default => throw new RuntimeException('detalhe técnico'),
                };
            });

            return response()->json(['status_url' => route('generation.status', $token)], 202);
        })->middleware('auth');
    }

    public function test_work_runs_after_the_response_and_the_file_is_downloaded_once(): void
    {
        $started = $this->actingAs($this->user)->postJson('/_teste/gerar/arquivo');

        $status = $this->generationResult($started);
        $this->assertSame('done', $status['status']);
        $this->assertSame('teste.docx', $status['file_name']);

        $this->get($status['download_url'])->assertOk()->assertDownload('teste.docx');
        $this->getJson($status['download_url'])->assertNotFound()->assertJsonPath('message', 'O arquivo não está mais disponível. Gere novamente.');
    }

    public function test_another_user_cannot_follow_or_download_the_generation(): void
    {
        $started = $this->actingAs($this->user)->postJson('/_teste/gerar/arquivo');
        $status = $this->generationResult($started);

        $intruso = User::factory()->create();
        $this->actingAs($intruso)->getJson($started->json('status_url'))->assertNotFound();
        $this->actingAs($intruso)->getJson($status['download_url'])->assertNotFound();
    }

    public function test_failures_show_a_friendly_message_without_technical_details(): void
    {
        $this->assertSame(
            ['status' => 'failed', 'message' => 'Mensagem clara para o usuário.'],
            $this->generationResult($this->actingAs($this->user)->postJson('/_teste/gerar/aviso')),
        );

        $this->assertSame(
            ['status' => 'failed', 'message' => 'Não foi possível concluir agora. Tente novamente em instantes.'],
            $this->generationResult($this->actingAs($this->user)->postJson('/_teste/gerar/erro')),
        );
    }

    public function test_unknown_or_expired_generation_is_reported(): void
    {
        $this->actingAs($this->user)->getJson(route('generation.status', str_repeat('a', 40)))
            ->assertNotFound()
            ->assertJsonPath('message', 'Esta geração não foi encontrada ou expirou. Tente novamente.');
    }
}
