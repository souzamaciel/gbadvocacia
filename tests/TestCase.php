<?php

namespace Tests;

use App\Models\Petition;
use App\Services\Pedidos\PedidoValues;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/private/geracoes'));

        parent::tearDown();
    }

    /**
     * Follows work started in the background (202 + status_url) and returns its final status.
     *
     * @return array<string, mixed>
     */
    protected function generationResult(TestResponse $started): array
    {
        $started->assertAccepted();

        return $this->getJson($started->json('status_url'))->assertOk()->json();
    }

    /**
     * Follows a background generation and downloads the Word file it produced.
     */
    protected function downloadGeneration(TestResponse $started): TestResponse
    {
        $status = $this->generationResult($started);
        $this->assertSame('done', $status['status'], $status['message'] ?? '');

        return $this->get($status['download_url']);
    }

    /**
     * Marks the "Valores dos pedidos" step as done, as the lawyer does before generating the Word.
     *
     * @param  list<array<string, mixed>>  $pedidos
     */
    protected function confirmPedidos(Petition $petition, array $pedidos = [['descricao' => 'Pagamento das horas extras', 'tipo' => 'outro', 'valor' => null, 'sugerido' => null, 'memoria' => '', 'subitens' => []]]): void
    {
        $petition->update([
            'pedidos' => $pedidos,
            'pedidos_signature' => app(PedidoValues::class)->signature($petition),
            'pedidos_confirmed_at' => now(),
        ]);
    }
}
