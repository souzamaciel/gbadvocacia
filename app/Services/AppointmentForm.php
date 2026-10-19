<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\AppointmentFormQuestion;
use App\Models\AppointmentFormStep;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Reads the configurable attendance form ("Ficha de atendimento") and turns the
 * submitted answers into what the petition, the AI and the printed form need.
 */
class AppointmentForm
{
    public const OTHER = 'Outros';

    /** @var Collection<int, AppointmentFormStep>|null */
    private ?Collection $steps = null;

    /**
     * @return Collection<int, AppointmentFormStep>
     */
    public function steps(): Collection
    {
        return $this->steps ??= AppointmentFormStep::query()->with('questions')->orderBy('position')->get();
    }

    /**
     * @return Collection<string, AppointmentFormQuestion>
     */
    public function questions(): Collection
    {
        return $this->steps()->flatMap(fn (AppointmentFormStep $step) => $step->questions)->keyBy('key');
    }

    /**
     * Answers grouped by step, in form order. Hidden questions (whose condition is not
     * met) and empty answers are left out; headings are kept to group the answers.
     *
     * @param  array<string, mixed>  $raw
     * @return list<array{title: string, items: list<array{heading: bool, label: string, value: string|list<string>|null}>}>
     */
    public function answers(array $raw, bool $forAi = false): array
    {
        $visible = $this->visibility($raw);
        $result = [];

        foreach ($this->steps() as $step) {
            $items = [];
            $pendingHeading = null;

            foreach ($step->questions as $question) {
                if (! $visible[$question->key]) {
                    continue;
                }

                if ($question->type === QuestionType::Heading) {
                    $pendingHeading = ['heading' => true, 'label' => $question->label, 'value' => null];

                    continue;
                }

                if (! $question->type->isAnswerable() || ($forAi && ! $question->send_to_ai)) {
                    continue;
                }

                $value = $this->value($question, $raw);

                if ($value === null || $value === [] || $value === '') {
                    continue;
                }

                if ($pendingHeading !== null) {
                    $items[] = $pendingHeading;
                    $pendingHeading = null;
                }

                $items[] = ['heading' => false, 'label' => $question->label, 'value' => $value];
            }

            if (array_filter($items, fn (array $item): bool => ! $item['heading']) !== []) {
                $result[] = ['title' => $step->title, 'items' => $items];
            }
        }

        return $result;
    }

    /**
     * Answers as readable question/answer pairs for the AI prompt (private answers removed).
     *
     * @param  array<string, mixed>  $raw
     * @return list<array{assunto: string, pergunta: string, resposta: string|list<string>}>
     */
    public function answersForAi(array $raw): array
    {
        $pairs = [];

        foreach ($this->answers($raw, true) as $step) {
            foreach ($step['items'] as $item) {
                if (! $item['heading']) {
                    $pairs[] = ['assunto' => $step['title'], 'pergunta' => $item['label'], 'resposta' => $item['value']];
                }
            }
        }

        return $pairs;
    }

    /**
     * Raw answers worth keeping in a draft: only known questions, without private
     * answers (marked "não enviar à IA", such as the GOV password) and without empty ones.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function draftAnswers(array $raw): array
    {
        $answers = [];

        foreach ($this->questions() as $key => $question) {
            if (! $question->type->isAnswerable() || ! $question->send_to_ai) {
                continue;
            }

            foreach ([$key, $key.'_outro'] as $field) {
                $value = $raw[$field] ?? null;

                if (is_array($value)) {
                    $value = array_values(array_filter($value, fn (mixed $item): bool => is_array($item)
                        ? array_filter($item, fn (mixed $cell): bool => is_scalar($cell) && trim((string) $cell) !== '') !== []
                        : is_scalar($item) && trim((string) $item) !== ''));
                } elseif (is_scalar($value)) {
                    $value = trim((string) $value);
                }

                if ($value !== null && $value !== '' && $value !== []) {
                    $answers[$field] = $value;
                }
            }
        }

        return $answers;
    }

    /**
     * Values of the qualification fields used by the petition, keyed by their fixed keys.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, string|null>
     */
    public function qualification(array $raw): array
    {
        $values = [];

        foreach (AppointmentFormQuestion::SYSTEM_KEYS as $key) {
            $values[$key] = self::text($raw[$key] ?? null);
        }

        return $values;
    }

    /**
     * Labels of the required questions that are visible and still unanswered.
     *
     * @param  array<string, mixed>  $raw
     * @return list<array{key: string, label: string, step: int}>
     */
    public function missingRequired(array $raw): array
    {
        $visible = $this->visibility($raw);
        $missing = [];

        foreach ($this->steps()->values() as $index => $step) {
            foreach ($step->questions as $question) {
                if (! $question->required || ! $question->type->isAnswerable() || ! $visible[$question->key]) {
                    continue;
                }

                $value = $this->value($question, $raw);

                if ($value === null || $value === [] || $value === '') {
                    $missing[] = ['key' => $question->key, 'label' => $question->label, 'step' => $index + 1];
                }
            }
        }

        return $missing;
    }

    /**
     * Whether each question is shown, following its "show only when" condition. A question
     * that depends on a hidden one is hidden too.
     *
     * @param  array<string, mixed>  $raw
     * @return array<string, bool>
     */
    public function visibility(array $raw): array
    {
        $questions = $this->questions();
        $visible = [];

        $resolve = function (string $key, array $path = []) use (&$resolve, &$visible, $questions, $raw): bool {
            if (isset($visible[$key])) {
                return $visible[$key];
            }

            $question = $questions->get($key);
            $condition = $question?->show_if;

            if ($question === null || empty($condition['key']) || in_array($key, $path, true)) {
                return $visible[$key] = $question !== null;
            }

            $controller = $questions->get($condition['key']);

            if ($controller === null) {
                return $visible[$key] = true;
            }

            $answers = (array) ($raw[$controller->key] ?? []);

            return $visible[$key] = $resolve($controller->key, [...$path, $key])
                && array_intersect(array_map('strval', $answers), (array) ($condition['values'] ?? [])) !== [];
        };

        foreach ($questions->keys() as $key) {
            $resolve($key);
        }

        return $visible;
    }

    /**
     * Readable answer of one question: text, list of texts or null when empty.
     *
     * @param  array<string, mixed>  $raw
     * @return string|list<string>|null
     */
    public function value(AppointmentFormQuestion $question, array $raw): string|array|null
    {
        $answer = $raw[$question->key] ?? null;
        $other = self::text($raw[$question->key.'_outro'] ?? null);

        return match ($question->type) {
            QuestionType::MultipleChoice => array_values(array_filter(array_map(
                fn (mixed $choice): ?string => self::choiceText($choice, $other),
                is_array($answer) ? $answer : [],
            ))),
            QuestionType::SingleChoice, QuestionType::YesNo => self::choiceText($answer, $other),
            QuestionType::Date => self::date($answer),
            QuestionType::Repeater => $this->rows($question, $answer),
            default => self::text($answer),
        };
    }

    /**
     * @return list<string>
     */
    private function rows(AppointmentFormQuestion $question, mixed $rows): array
    {
        $lines = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $parts = [];

            foreach ($question->columns() as $column) {
                $value = $column['type'] === QuestionType::Date->value
                    ? self::date($row[$column['key']] ?? null)
                    : self::text($row[$column['key']] ?? null);

                if ($value !== null) {
                    $parts[] = $column['label'].': '.$value;
                }
            }

            if ($parts !== []) {
                $lines[] = implode(' | ', $parts);
            }
        }

        return $lines;
    }

    private static function choiceText(mixed $choice, ?string $other): ?string
    {
        $choice = self::text($choice);

        if ($choice === self::OTHER && $other !== null) {
            return self::OTHER.': '.$other;
        }

        return $choice;
    }

    private static function text(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function date(mixed $value): ?string
    {
        $text = self::text($value);

        if ($text === null || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
            return $text;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $text)->format('d/m/Y');
        } catch (Throwable) {
            return $text;
        }
    }
}
