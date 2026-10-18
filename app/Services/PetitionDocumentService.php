<?php

namespace App\Services;

use App\Models\Petition;
use App\Models\PetitionItem;
use App\Models\PetitionLog;
use App\Services\OpenAI\PetitionGenerationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PetitionDocumentService
{
    public function __construct(
        protected PetitionGenerationService $petitionGenerationService
    ) {}

    /**
     * Generate the Word file from the reviewed texts and keep it with the petition.
     */
    public function generate(Petition $petition, ?string $fileName = null): Petition
    {
        $items = $petition->items()->with('topic')->get();

        $toPayload = fn (PetitionItem $item, int $index): array => [
            'sort_order' => $index + 1,
            'topic_id' => $item->topic_id,
            'title' => $item->title,
            'content' => $item->renderedContent(),
        ];

        $preliminares = $items->where('kind', PetitionItem::KIND_PRELIMINAR)->values()->map($toPayload)->all();
        $topics = $items->where('kind', PetitionItem::KIND_PEDIDO)->values()->map($toPayload)->all();

        $normalized = PetitionFormSerializer::normalizeFormData([], $topics, $preliminares);
        $resultado = $this->petitionGenerationService->generate($normalized, false, false);

        PetitionLog::create([
            'openai_model' => $resultado['openai_model'],
            'request_payload' => $resultado['request_payload'],
            'response_payload' => $resultado['raw_response'],
        ]);

        $temporaryPath = (new PetitionDocxGenerator)->generateMerit($this->withInternalNotes($resultado['blade_data'], $items));

        $baseName = Str::slug($fileName ?: $petition->name) ?: 'peticao-inicial';
        $storedPath = "petitions/{$petition->id}/{$baseName}.docx";

        if ($petition->file_path && $petition->file_path !== $storedPath) {
            Storage::disk('local')->delete($petition->file_path);
        }

        Storage::disk('local')->put($storedPath, file_get_contents($temporaryPath));
        @unlink($temporaryPath);

        $petition->update([
            'status' => Petition::STATUS_GENERATED,
            'file_name' => "{$baseName}.docx",
            'file_path' => $storedPath,
            'generated_at' => now(),
        ]);

        return $petition;
    }

    /**
     * Attach each topic's internal guidance (kept in the library) to the matching block of the document.
     *
     * @param  array<string, mixed>  $data
     * @param  Collection<int, PetitionItem>  $items
     * @return array<string, mixed>
     */
    private function withInternalNotes(array $data, Collection $items): array
    {
        $notes = $items->mapWithKeys(fn (PetitionItem $item): array => [(int) $item->topic_id => trim((string) $item->topic?->notes)]);

        foreach (['preliminares' => PetitionItem::KIND_PRELIMINAR, 'topicos' => PetitionItem::KIND_PEDIDO] as $key => $kind) {
            $blocks = $data[$key] ?? [];
            $sameKind = $items->where('kind', $kind)->values();
            $useOrder = count($blocks) === $sameKind->count();

            foreach ($blocks as $index => $block) {
                $topicId = (int) ($block['topic_id'] ?? 0);
                if (! $notes->has($topicId) && $useOrder) {
                    $topicId = (int) $sameKind[$index]->topic_id;
                }

                $data[$key][$index]['orientacao'] = $notes->get($topicId) ?: null;
            }
        }

        return $data;
    }
}
