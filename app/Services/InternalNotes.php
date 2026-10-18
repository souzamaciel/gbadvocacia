<?php

namespace App\Services;

/**
 * Puts each topic's internal guidance (kept in the library) on the matching block
 * of the AI's document data, so it is printed in green under the topic title.
 */
class InternalNotes
{
    /**
     * @param  array<string, mixed>  $data  document data returned by the AI
     * @param  array<int, string|null>  $notesByTopicId
     * @param  array{preliminares: array<int, int>, topicos: array<int, int>}  $selectedIds  topic ids sent to the AI, in order
     * @return array<string, mixed>
     */
    public static function attach(array $data, array $notesByTopicId, array $selectedIds): array
    {
        foreach (['preliminares', 'topicos'] as $key) {
            $blocks = $data[$key] ?? [];
            $sentIds = array_values($selectedIds[$key] ?? []);
            $useOrder = count($blocks) === count($sentIds);

            foreach (array_values($blocks) as $index => $block) {
                $topicId = (int) ($block['topic_id'] ?? 0);

                if (! in_array($topicId, $sentIds, true) && $useOrder) {
                    $topicId = (int) $sentIds[$index];
                }

                $note = trim((string) ($notesByTopicId[$topicId] ?? ''));
                $data[$key][$index]['orientacao'] = $note === '' ? null : $note;
            }
        }

        return $data;
    }
}
