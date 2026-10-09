<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\PetitionLog;
use App\Models\Topic;
use App\Services\OpenAI\PetitionGenerationService;
use App\Services\PetitionDocxGenerator;
use App\Services\PetitionFormSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PetitionController extends Controller
{
    public function __construct(
        protected PetitionGenerationService $petitionGenerationService
    ) {}

    public function create(): View
    {
        $groups = Group::query()
            ->where('active', true)
            ->where('id', '!=', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->with(['topics' => fn ($q) => $q->where('active', true)])
            ->get();

        $preliminares = Topic::query()
            ->where('active', true)
            ->where('group_id', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->get(['id', 'title']);

        $pedidos = Topic::query()
            ->where('active', true)
            ->where('group_id', '!=', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->get(['id', 'title']);

        return view('petitions.create', compact('groups', 'preliminares', 'pedidos'));
    }

    public function store(Request $request): BinaryFileResponse|JsonResponse
    {
        $request->validate([
            'topics' => ['required', 'array', 'min:1'],
            'topics.*' => ['integer', 'exists:topics,id'],
            'preliminares' => ['sometimes', 'array'],
            'preliminares.*' => ['integer', 'exists:topics,id'],
        ]);

        $topicIds = $request->input('topics', []);
        $preliminarIds = $request->input('preliminares', []);

        $topics = Topic::whereIn('id', $topicIds)
            ->get(['id', 'title', 'content'])
            ->sortBy(fn ($t) => array_search($t->id, $topicIds))
            ->values()
            ->map(fn ($t, $i) => ['sort_order' => $i + 1, 'topic_id' => $t->id, 'title' => $t->title, 'content' => $t->content])
            ->all();

        $preliminares = Topic::whereIn('id', $preliminarIds)
            ->where('group_id', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->get(['id', 'title', 'content'])
            ->values()
            ->map(fn ($t, $i) => ['sort_order' => $i + 1, 'topic_id' => $t->id, 'title' => $t->title, 'content' => $t->content])
            ->all();

        $normalized = PetitionFormSerializer::normalizeFormData($request->all(), $topics, $preliminares);

        try {
            $resultado = $this->petitionGenerationService->generate($normalized, false, false);

            PetitionLog::create([
                'openai_model' => $resultado['openai_model'],
                'request_payload' => $resultado['request_payload'],
                'response_payload' => $resultado['raw_response'],
            ]);

            $generator = new PetitionDocxGenerator;
            $filePath = $generator->generateMerit($resultado['blade_data']);

            return response()
                ->download($filePath, 'peticao-merito.docx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->deleteFileAfterSend();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar petição: '.$e->getMessage(),
            ], 500);
        }
    }
}
