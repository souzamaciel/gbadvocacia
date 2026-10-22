<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportAppointmentRequest;
use App\Models\Appointment;
use App\Services\BackgroundGeneration;
use App\Services\DocxText;
use App\Services\GenerationFailed;
use App\Services\OpenAI\AppointmentImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

/**
 * Imports an attendance form filled in Word: the AI fills the system's form and
 * suggests the petition topics. The uploaded file is read and discarded, never stored.
 */
class AppointmentImportController extends Controller
{
    public function create(): View
    {
        return view('appointments.import');
    }

    public function store(ImportAppointmentRequest $request, DocxText $docx, AppointmentImportService $importer, BackgroundGeneration $generations): JsonResponse
    {
        $file = $request->file('file');

        try {
            $text = $docx->extract($file->getRealPath());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (mb_strlen(preg_replace('/[\s_().:|\[\]-]+/u', '', $text) ?? '') < 200) {
            return response()->json(['message' => 'O arquivo está vazio ou tem muito pouco texto. Confira se é a ficha de atendimento preenchida.'], 422);
        }

        $userId = $request->user()->id;
        $fileName = mb_substr($file->getClientOriginalName(), 0, 255);
        $redacted = DocxText::redactPrivate($text);

        $token = $generations->start($userId, function () use ($importer, $redacted, $userId, $fileName): array {
            try {
                $result = $importer->import($redacted);
            } catch (Throwable $e) {
                report($e);

                throw new GenerationFailed('Não foi possível ler a ficha agora. Tente novamente em instantes.');
            }

            $appointment = Appointment::create([
                'user_id' => $userId,
                'client_name' => $result['answers']['cliente_nome'] ?? null,
                'answers' => $result['answers'],
                'preliminares' => $result['preliminares'],
                'pedidos' => $result['pedidos'],
                'import_file_name' => $fileName,
                'import_notes' => ['observacoes' => $result['notes'], 'pedidos' => $result['reasons']],
            ]);

            return ['redirect' => route('appointment.edit', $appointment)];
        });

        return response()->json(['status_url' => route('generation.status', $token)], 202);
    }
}
