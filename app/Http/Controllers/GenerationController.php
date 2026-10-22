<?php

namespace App\Http\Controllers;

use App\Services\BackgroundGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Status and download of work running in the background (see BackgroundGeneration).
 */
class GenerationController extends Controller
{
    public function __construct(
        protected BackgroundGeneration $generations,
    ) {}

    public function status(Request $request, string $token): JsonResponse
    {
        $status = $this->generations->status($token, $request->user()->id);

        if ($status === null) {
            return response()->json(['status' => 'failed', 'message' => 'Esta geração não foi encontrada ou expirou. Tente novamente.'], 404);
        }

        $response = ['status' => $status['status']];

        if ($status['status'] === 'failed') {
            $response['message'] = $status['message'] ?? 'Não foi possível concluir agora. Tente novamente em instantes.';
        }

        if ($status['status'] === 'done') {
            if (isset($status['file_name'])) {
                $response['download_url'] = route('generation.download', $token);
                $response['file_name'] = $status['file_name'];
            }

            foreach (['redirect', 'generated_at'] as $key) {
                if (isset($status[$key])) {
                    $response[$key] = $status[$key];
                }
            }
        }

        return response()->json($response);
    }

    public function download(Request $request, string $token): BinaryFileResponse|JsonResponse
    {
        $status = $this->generations->status($token, $request->user()->id);
        $path = $this->generations->filePath($token);

        if ($status === null || ($status['status'] ?? null) !== 'done' || ! is_file($path)) {
            return response()->json(['message' => 'O arquivo não está mais disponível. Gere novamente.'], 404);
        }

        $this->generations->forget($token);

        return response()
            ->download($path, $status['file_name'] ?? 'peticao-inicial.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend();
    }
}
