<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Petition;
use App\Models\PetitionItem;
use App\Models\Topic;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PetitionDraftService
{
    /**
     * Make the petition's items match the chosen topics, keeping the review work
     * (edited text, filled fields, reviewed mark) of topics that stay selected.
     *
     * @param  array<int, int|string>  $preliminarIds
     * @param  array<int, int|string>  $pedidoIds  in document order
     */
    public function syncItems(Petition $petition, array $preliminarIds, array $pedidoIds): void
    {
        $preliminarIds = array_values(array_unique(array_map('intval', $preliminarIds)));
        $pedidoIds = array_values(array_unique(array_map('intval', $pedidoIds)));

        $topics = Topic::query()
            ->with('group')
            ->whereIn('id', [...$preliminarIds, ...$pedidoIds])
            ->get()
            ->keyBy('id');

        $preliminares = Topic::query()
            ->whereIn('id', $preliminarIds)
            ->where('group_id', Group::PRELIMINARES_ID)
            ->orderBy('sort_order')
            ->pluck('id')
            ->all();

        $pedidos = array_values(array_filter($pedidoIds, fn (int $id): bool => $topics->has($id)
            && $topics[$id]->group_id !== Group::PRELIMINARES_ID));

        DB::transaction(function () use ($petition, $topics, $preliminares, $pedidos): void {
            /** @var Collection<int, PetitionItem> $existing */
            $existing = $petition->items()->get()->keyBy(fn (PetitionItem $item): string => $item->kind.':'.$item->topic_id);
            $kept = [];

            foreach ([PetitionItem::KIND_PRELIMINAR => $preliminares, PetitionItem::KIND_PEDIDO => $pedidos] as $kind => $ids) {
                foreach ($ids as $index => $topicId) {
                    $key = $kind.':'.$topicId;
                    $item = $existing->get($key) ?? new PetitionItem([
                        'petition_id' => $petition->id,
                        'topic_id' => $topicId,
                        'kind' => $kind,
                        'title' => $topics[$topicId]->title,
                        'group_name' => $topics[$topicId]->group?->name,
                        'content' => $topics[$topicId]->content,
                    ]);

                    $item->position = $index + 1;
                    $item->save();
                    $kept[] = $item->id;
                }
            }

            PetitionItem::query()->where('petition_id', $petition->id)->whereNotIn('id', $kept)->delete();
            $petition->touch();
        });
    }

    /**
     * @return array{preliminares: array<int, int>, pedidos: array<int, int>}
     */
    public function selection(Petition $petition): array
    {
        $items = $petition->items()->get();

        return [
            'preliminares' => $items->where('kind', PetitionItem::KIND_PRELIMINAR)->pluck('topic_id')->filter()->values()->all(),
            'pedidos' => $items->where('kind', PetitionItem::KIND_PEDIDO)->pluck('topic_id')->filter()->values()->all(),
        ];
    }
}
