<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportAppointmentRequest;
use App\Models\Appointment;
use App\Services\DocxText;
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

    public function store(ImportAppointmentRequest $request, DocxText $docx, AppointmentImportService $importer): JsonResponse
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

        try {
            $result = $importer->import(DocxText::redactPrivate($text));
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Não foi possível ler a ficha agora. Tente novamente em instantes.'], 502);
        }

        $appointment = Appointment::create([
            'user_id' => $request->user()->id,
            'client_name' => $result['answers']['cliente_nome'] ?? null,
            'answers' => $result['answers'],
            'preliminares' => $result['preliminares'],
            'pedidos' => $result['pedidos'],
            'import_file_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'import_notes' => ['observacoes' => $result['notes'], 'pedidos' => $result['reasons']],
        ]);

        return response()->json([
            'redirect' => route('appointment.edit', $appointment),
            'answered' => count(array_filter(array_keys($result['answers']), fn (string $key): bool => ! str_ends_with($key, '_outro'))),
        ]);
    }
}
