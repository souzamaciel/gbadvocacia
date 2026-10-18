<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePetitionRequest;
use App\Http\Requests\SavePetitionRequest;
use App\Http\Requests\UpdatePetitionItemRequest;
use App\Models\Group;
use App\Models\Petition;
use App\Models\PetitionItem;
use App\Models\Topic;
use App\Services\PetitionDocumentService;
use App\Services\PetitionDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PetitionController extends Controller
{
    public function __construct(
        protected PetitionDraftService $draftService,
        protected PetitionDocumentService $documentService,
    ) {}

    public function index(): View
    {
        $petitions = Petition::query()
            ->with('user')
            ->withCount(['items', 'items as reviewed_items_count' => fn ($query) => $query->whereNotNull('reviewed_at')])
            ->latest('updated_at')
            ->get();

        $continue = $petitions->firstWhere('status', Petition::STATUS_DRAFT);

        return view('petitions.index', compact('petitions', 'continue'));
    }

    public function create(): View
    {
        return $this->builder(null);
    }

    public function store(SavePetitionRequest $request): RedirectResponse
    {
        $petition = Petition::create([
            'user_id' => $request->user()->id,
            'name' => $this->petitionName($request),
            'status' => Petition::STATUS_DRAFT,
        ]);

        return $this->saveSelection($request, $petition);
    }

    public function edit(Petition $petition): View
    {
        return $this->builder($petition);
    }

    public function update(SavePetitionRequest $request, Petition $petition): RedirectResponse
    {
        $petition->update(['name' => $this->petitionName($request)]);

        return $this->saveSelection($request, $petition);
    }

    public function review(Petition $petition): View|RedirectResponse
    {
        $petition->load('items');

        if ($petition->items->where('kind', PetitionItem::KIND_PEDIDO)->isEmpty()) {
            return redirect()
                ->route('petition.edit', $petition)
                ->with('status', 'Adicione ao menos um pedido antes de revisar.');
        }

        return view('petitions.review', compact('petition'));
    }

    public function updateItem(UpdatePetitionItemRequest $request, Petition $petition, PetitionItem $item): JsonResponse
    {
        if ($request->has('field_values')) {
            $item->field_values = array_intersect_key(
                $request->input('field_values', []),
                array_flip($item->placeholders()),
            );
        }

        if ($request->has('content')) {
            $item->content = $request->string('content')->toString();
            $item->field_values = array_intersect_key($item->field_values ?? [], array_flip($item->placeholders()));
        }

        if ($request->has('reviewed')) {
            if ($request->boolean('reviewed') && $item->pendingPlaceholders() !== []) {
                return response()->json([
                    'message' => 'Preencha as informações pendentes antes de marcar o tópico como conferido.',
                ], 422);
            }

            $item->reviewed_at = $request->boolean('reviewed') ? now() : null;
        } elseif ($item->isDirty(['content', 'field_values'])) {
            $item->reviewed_at = null;
        }

        $item->save();

        if ($petition->isGenerated() && $item->wasChanged(['content', 'field_values'])) {
            $petition->update(['status' => Petition::STATUS_DRAFT]);
        }

        $petition->touch();

        return response()->json([
            'item' => $this->itemPayload($item),
            'reviewed_count' => $petition->items()->whereNotNull('reviewed_at')->count(),
            'fully_reviewed' => $petition->fresh()->isFullyReviewed(),
        ]);
    }

    public function showGenerate(Petition $petition): View|RedirectResponse
    {
        $petition->load('items');

        if (! $petition->isFullyReviewed()) {
            return redirect()
                ->route('petition.review', $petition)
                ->with('status', 'Revise todos os tópicos antes de gerar o documento.');
        }

        return view('petitions.generate', compact('petition'));
    }

    public function generate(GeneratePetitionRequest $request, Petition $petition): JsonResponse
    {
        if (! $petition->isFullyReviewed()) {
            return response()->json(['message' => 'Revise todos os tópicos antes de gerar o documento.'], 422);
        }

        try {
            $petition = $this->documentService->generate($petition, $request->input('file_name'));
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Erro ao gerar o documento: '.$e->getMessage()], 500);
        }

        return response()->json([
            'file_name' => $petition->file_name,
            'generated_at' => $petition->generated_at->locale('pt_BR')->translatedFormat('d/m/Y \à\s H:i'),
            'download_url' => route('petition.download', $petition),
        ]);
    }

    public function download(Petition $petition): StreamedResponse|RedirectResponse
    {
        if (! $petition->file_path || ! Storage::disk('local')->exists($petition->file_path)) {
            return redirect()
                ->route('petition.index')
                ->with('status', 'O arquivo desta petição não está disponível. Gere o documento novamente.');
        }

        return Storage::disk('local')->download($petition->file_path, $petition->file_name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function destroy(Petition $petition): RedirectResponse
    {
        if ($petition->file_path) {
            Storage::disk('local')->delete($petition->file_path);
        }

        $petition->delete();

        return redirect()
            ->route('petition.index')
            ->with('status', 'Petição excluída.');
    }

    /**
     * @return array<string, mixed>
     */
    public static function itemPayload(PetitionItem $item): array
    {
        return [
            'id' => $item->id,
            'kind' => $item->kind,
            'title' => $item->title,
            'group_name' => $item->group_name,
            'content' => $item->content,
            'rendered' => $item->renderedContent(),
            'placeholders' => $item->placeholders(),
            'pending' => $item->pendingPlaceholders(),
            'field_values' => (object) ($item->field_values ?? []),
            'reviewed' => $item->reviewed_at !== null,
        ];
    }

    private function builder(?Petition $petition): View
    {
        $groups = Group::query()
            ->where('active', true)
            ->orderByRaw('id = ? desc', [Group::PRELIMINARES_ID])
            ->orderBy('sort_order')
            ->with(['topics' => fn ($q) => $q->where('active', true)])
            ->get();

        $pedidos = Topic::query()
            ->where('active', true)
            ->where('group_id', '!=', Group::PRELIMINARES_ID)
            ->orderedByGroup()
            ->get(['id', 'title']);

        $preliminares = $groups->firstWhere('id', Group::PRELIMINARES_ID)?->topics
            ->map(fn (Topic $topic): array => ['id' => $topic->id, 'title' => $topic->title])
            ->values() ?? collect();

        $selection = $petition ? $this->draftService->selection($petition) : null;

        return view('petitions.create', compact('groups', 'preliminares', 'pedidos', 'petition', 'selection'));
    }

    private function saveSelection(SavePetitionRequest $request, Petition $petition): RedirectResponse
    {
        $this->draftService->syncItems($petition, $request->input('preliminares', []), $request->input('topics', []));

        if ($request->input('action') === 'review') {
            return redirect()->route('petition.review', $petition);
        }

        return redirect()
            ->route('petition.edit', $petition)
            ->with('status', 'Rascunho salvo.');
    }

    private function petitionName(SavePetitionRequest $request): string
    {
        return trim((string) $request->input('name')) ?: 'Petição inicial — '.now()->format('d/m/Y H:i');
    }
}
