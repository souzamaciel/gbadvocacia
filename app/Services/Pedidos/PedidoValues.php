<?php

namespace App\Services\Pedidos;

use App\Models\Petition;
use App\Models\PetitionItem;
use App\Services\MoneyInWords;
use App\Services\OpenAI\PedidoClassificationService;
use App\Services\PetitionFormSerializer;

/**
 * The "Valores dos pedidos" step: builds the pedidos with estimated amounts, keeps the
 * amounts the lawyer confirms and hands them to the Word.
 */
class PedidoValues
{
    public function __construct(
        protected PedidoClassificationService $classifier
    ) {}

    /**
     * Changes when the pedido topics (or their order) or the ficha change, so the
     * amounts are calculated again. Editing a topic's text keeps the amounts.
     */
    public function signature(Petition $petition): string
    {
        $topicIds = $petition->items()->where('kind', PetitionItem::KIND_PEDIDO)->pluck('topic_id')->all();

        return md5(json_encode([$topicIds, $petition->appointment?->answers ?? []]));
    }

    public function isStale(Petition $petition): bool
    {
        return $petition->pedidos === null || $petition->pedidos_signature !== $this->signature($petition);
    }

    /**
     * Asks the AI for the pedidos and their quantities, does the math and saves the
     * result as suggestions (nothing is confirmed yet).
     */
    public function calculate(Petition $petition): void
    {
        $answers = $petition->appointment?->answers ?? [];
        $topics = $petition->items()->where('kind', PetitionItem::KIND_PEDIDO)->get()->values()
            ->map(fn (PetitionItem $item, int $index): array => [
                'sort_order' => $index + 1,
                'topic_id' => $item->topic_id,
                'title' => $item->title,
                'content' => $item->renderedContent(),
            ])->all();

        $classified = $this->classifier->classify(PetitionFormSerializer::normalizeFormData($answers, $topics));
        $calculated = (new PedidoCalculator(CaseFacts::fromAnswers($answers, $classified['divisor'])))->calculate($classified['pedidos']);

        $withSuggestion = fn (array $line): array => [...$line, 'sugerido' => $line['valor']];

        $petition->update([
            'pedidos' => array_map(fn (array $pedido): array => [
                ...$withSuggestion($pedido),
                'subitens' => array_map($withSuggestion, $pedido['subitens']),
            ], $calculated),
            'pedidos_signature' => $this->signature($petition),
            'pedidos_confirmed_at' => null,
        ]);
    }

    /**
     * @param  array<int, array{valor?: ?string, subitens?: array<int, array{valor?: ?string}>}>  $amounts  typed amounts by pedido index
     */
    public function confirm(Petition $petition, array $amounts): void
    {
        $pedidos = $petition->pedidos ?? [];

        foreach ($pedidos as $index => $pedido) {
            $pedidos[$index]['valor'] = MoneyInWords::parseCents($amounts[$index]['valor'] ?? null);

            foreach ($pedido['subitens'] as $subIndex => $subitem) {
                $pedidos[$index]['subitens'][$subIndex]['valor'] = MoneyInWords::parseCents($amounts[$index]['subitens'][$subIndex]['valor'] ?? null);
            }
        }

        $petition->update(['pedidos' => $pedidos, 'pedidos_confirmed_at' => now()]);

        if ($petition->isGenerated()) {
            $petition->update(['status' => Petition::STATUS_DRAFT]);
        }
    }

    /**
     * Sum of every amount filled in, in cents.
     *
     * @param  list<array<string, mixed>>  $pedidos
     */
    public static function total(array $pedidos): int
    {
        $total = 0;

        foreach ($pedidos as $pedido) {
            $total += (int) ($pedido['valor'] ?? 0);

            foreach ($pedido['subitens'] ?? [] as $subitem) {
                $total += (int) ($subitem['valor'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * The pedidos in the shape the Word generator reads ("R$ 1.234,56" or empty when left blank).
     *
     * @param  list<array<string, mixed>>  $pedidos
     * @return list<array{descricao: string, valor: string, subitens: list<array{descricao: string, valor: string}>}>
     */
    public static function forDocument(array $pedidos): array
    {
        $format = fn (mixed $cents): string => is_int($cents) ? MoneyInWords::formatCents($cents) : '';

        return array_map(fn (array $pedido): array => [
            'descricao' => (string) $pedido['descricao'],
            'valor' => $format($pedido['valor'] ?? null),
            'subitens' => array_map(fn (array $subitem): array => [
                'descricao' => (string) $subitem['descricao'],
                'valor' => $format($subitem['valor'] ?? null),
            ], $pedido['subitens'] ?? []),
        ], $pedidos);
    }
}
