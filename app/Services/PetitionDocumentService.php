<?php

namespace App\Services;

use App\Models\Petition;
use App\Models\PetitionItem;
use App\Models\PetitionLog;
use App\Services\OpenAI\PetitionGenerationService;
use Illuminate\Support\Str;

class PetitionDocumentService
{
    public function __construct(
        protected PetitionGenerationService $petitionGenerationService
    ) {}

    /**
     * Generate the Word file from the reviewed texts. The file is not kept on the
     * server in this version: it is returned for download and only the draft is saved.
     *
     * @return string path of the temporary file to download
     */
    public function generate(Petition $petition, ?string $fileName = null): string
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

        $data = InternalNotes::attach(
            $resultado['blade_data'],
            $items->mapWithKeys(fn (PetitionItem $item): array => [(int) $item->topic_id => $item->topic?->notes])->all(),
            [
                'preliminares' => array_map('intval', array_column($preliminares, 'topic_id')),
                'topicos' => array_map('intval', array_column($topics, 'topic_id')),
            ],
        );

        $data['qualificacao_html'] = $items->firstWhere('kind', PetitionItem::KIND_QUALIFICACAO)?->renderedContent();

        $temporaryPath = (new PetitionDocxGenerator)->generateFull($data);

        $baseName = Str::slug($fileName ?: $petition->name) ?: 'peticao-inicial';

        $petition->update([
            'status' => Petition::STATUS_GENERATED,
            'file_name' => "{$baseName}.docx",
            'file_path' => null,
            'generated_at' => now(),
        ]);

        return $temporaryPath;
    }
}
