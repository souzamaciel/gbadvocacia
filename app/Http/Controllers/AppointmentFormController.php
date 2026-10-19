<?php

namespace App\Http\Controllers;

use App\Enums\QuestionType;
use App\Http\Requests\MoveFormItemRequest;
use App\Http\Requests\SaveFormQuestionRequest;
use App\Http\Requests\SaveFormStepRequest;
use App\Models\AppointmentFormQuestion;
use App\Models\AppointmentFormStep;
use App\Services\AppointmentForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AppointmentFormController extends Controller
{
    public function index(Request $request): View
    {
        $steps = AppointmentFormStep::query()->with('questions')->orderBy('position')->get();
        $current = $steps->firstWhere('id', $request->integer('assunto')) ?? $steps->first();

        return view('admin.form.index', compact('steps', 'current'));
    }

    public function storeStep(SaveFormStepRequest $request): JsonResponse
    {
        $step = AppointmentFormStep::create([
            'title' => $request->string('title')->toString(),
            'description' => $request->input('description') ?: null,
            'icon' => $request->input('icon') ?: 'clipboard-list',
            'position' => (AppointmentFormStep::query()->max('position') ?? 0) + 1,
        ]);

        return response()->json(['id' => $step->id, 'message' => "Assunto “{$step->title}” criado."]);
    }

    public function updateStep(SaveFormStepRequest $request, AppointmentFormStep $step): JsonResponse
    {
        $step->update([
            'title' => $request->string('title')->toString(),
            'description' => $request->input('description') ?: null,
            'icon' => $request->input('icon') ?: $step->icon,
        ]);

        return response()->json(['id' => $step->id, 'message' => 'Assunto atualizado.']);
    }

    public function moveStep(MoveFormItemRequest $request, AppointmentFormStep $step): JsonResponse
    {
        $this->swap(AppointmentFormStep::query()->orderBy('position')->get(), $step, $request->string('direction')->toString());

        return response()->json(['id' => $step->id]);
    }

    public function destroyStep(AppointmentFormStep $step): JsonResponse
    {
        if ($step->questions()->where('is_system', true)->exists()) {
            return response()->json(['message' => 'Este assunto tem campos da qualificação usados na petição. Mova esses campos para outro assunto antes de excluir.'], 422);
        }

        $keys = $step->questions()->pluck('key')->all();
        $dependents = $this->dependents($keys)->reject(fn (AppointmentFormQuestion $question): bool => $question->appointment_form_step_id === $step->id);

        if ($dependents->isNotEmpty()) {
            return response()->json(['message' => 'Perguntas de outros assuntos dependem deste: '.$dependents->pluck('label')->implode('; ').'. Ajuste a condição delas antes de excluir.'], 422);
        }

        $step->delete();

        return response()->json(['message' => "Assunto “{$step->title}” excluído."]);
    }

    public function storeQuestion(SaveFormQuestionRequest $request): JsonResponse
    {
        $stepId = $request->integer('appointment_form_step_id');

        $question = AppointmentFormQuestion::create([
            ...$this->attributes($request),
            'key' => $this->uniqueKey($request->string('label')->toString(), QuestionType::from($request->string('type')->toString())),
            'appointment_form_step_id' => $stepId,
            'position' => (AppointmentFormQuestion::query()->where('appointment_form_step_id', $stepId)->max('position') ?? 0) + 1,
            'is_system' => false,
        ]);

        return response()->json(['id' => $question->id, 'step' => $stepId, 'message' => 'Pergunta adicionada.']);
    }

    public function updateQuestion(SaveFormQuestionRequest $request, AppointmentFormQuestion $question): JsonResponse
    {
        $stepId = $request->integer('appointment_form_step_id');
        $attributes = $this->attributes($request);

        if ($stepId !== $question->appointment_form_step_id) {
            $attributes['position'] = (AppointmentFormQuestion::query()->where('appointment_form_step_id', $stepId)->max('position') ?? 0) + 1;
        }

        DB::transaction(function () use ($question, $attributes, $stepId): void {
            $question->update([...$attributes, 'appointment_form_step_id' => $stepId]);
            $this->pruneConditions($question->fresh());
        });

        return response()->json(['id' => $question->id, 'step' => $stepId, 'message' => 'Pergunta atualizada.']);
    }

    public function moveQuestion(MoveFormItemRequest $request, AppointmentFormQuestion $question): JsonResponse
    {
        $this->swap(
            AppointmentFormQuestion::query()->where('appointment_form_step_id', $question->appointment_form_step_id)->orderBy('position')->get(),
            $question,
            $request->string('direction')->toString(),
        );

        return response()->json(['id' => $question->id]);
    }

    public function destroyQuestion(AppointmentFormQuestion $question): JsonResponse
    {
        if ($question->is_system) {
            return response()->json(['message' => 'Este campo é usado na qualificação da petição e não pode ser excluído. Você pode mudar o texto dele.'], 422);
        }

        $dependents = $this->dependents([$question->key]);

        if ($dependents->isNotEmpty()) {
            return response()->json(['message' => 'Estas perguntas só aparecem conforme a resposta desta: '.$dependents->pluck('label')->implode('; ').'. Ajuste ou exclua essas perguntas antes.'], 422);
        }

        $question->delete();

        return response()->json(['message' => 'Pergunta excluída.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(SaveFormQuestionRequest $request): array
    {
        $type = QuestionType::from($request->string('type')->toString());
        $options = null;

        if ($type->hasChoices()) {
            $options = ['choices' => $request->input('choices'), 'allow_other' => $request->boolean('allow_other')];
        }

        if ($type === QuestionType::Repeater) {
            $used = [];
            $options = ['columns' => array_map(function (array $column) use (&$used): array {
                $key = Str::of($column['label'])->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->limit(30, '')->toString() ?: 'coluna';
                $base = $key;

                for ($i = 2; in_array($key, $used, true); $i++) {
                    $key = $base.'_'.$i;
                }

                $used[] = $key;

                return ['key' => $key, 'label' => $column['label'], 'type' => $column['type']];
            }, $request->input('columns'))];
        }

        $answerable = $type->isAnswerable();

        return [
            'type' => $type,
            'label' => $request->string('label')->toString(),
            'help' => $answerable ? $request->input('help') : null,
            'placeholder' => $answerable ? $request->input('placeholder') : null,
            'options' => $options,
            'show_if' => $request->input('show_if_key')
                ? ['key' => $request->input('show_if_key'), 'values' => $request->input('show_if_values')]
                : null,
            'width' => $answerable ? $request->string('width')->toString() : 'full',
            'required' => $answerable && $request->boolean('required'),
            'send_to_ai' => $request->boolean('send_to_ai'),
        ];
    }

    /**
     * Keeps the conditions that depend on this question valid after its answers change.
     */
    private function pruneConditions(AppointmentFormQuestion $question): void
    {
        $valid = $question->type->canControlVisibility()
            ? [...$question->choices(), ...($question->allowsOther() ? [AppointmentForm::OTHER] : [])]
            : [];

        foreach ($this->dependents([$question->key]) as $dependent) {
            $values = array_values(array_intersect($dependent->show_if['values'] ?? [], $valid));
            $dependent->update(['show_if' => $values === [] ? null : ['key' => $question->key, 'values' => $values]]);
        }
    }

    /**
     * Questions shown only when one of the given questions has a certain answer.
     *
     * @param  list<string>  $keys
     * @return Collection<int, AppointmentFormQuestion>
     */
    private function dependents(array $keys): Collection
    {
        return AppointmentFormQuestion::query()
            ->whereNotNull('show_if')
            ->get()
            ->filter(fn (AppointmentFormQuestion $question): bool => in_array($question->show_if['key'] ?? null, $keys, true))
            ->values();
    }

    private function uniqueKey(string $label, QuestionType $type): string
    {
        $base = match ($type) {
            QuestionType::Heading => 'subtitulo',
            QuestionType::Notice => 'orientacao',
            default => Str::of(strip_tags($label))->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->limit(50, '')->trim('_')->toString(),
        };

        $base = $base === '' || ctype_digit($base[0]) ? 'pergunta_'.$base : $base;
        $base = rtrim($base, '_');
        $key = $base;

        for ($i = 2; AppointmentFormQuestion::query()->where('key', $key)->exists() || in_array($key, AppointmentFormQuestion::SYSTEM_KEYS, true); $i++) {
            $key = $base.'_'.$i;
        }

        return $key;
    }

    /**
     * Swaps the position of an item with its neighbour.
     *
     * @param  Collection<int, AppointmentFormStep|AppointmentFormQuestion>  $items
     */
    private function swap(Collection $items, AppointmentFormStep|AppointmentFormQuestion $item, string $direction): void
    {
        $items = $items->values();
        $index = $items->search(fn ($candidate): bool => $candidate->is($item));
        $neighbour = $items->get($direction === 'up' ? $index - 1 : $index + 1);

        if ($index === false || $neighbour === null) {
            return;
        }

        DB::transaction(function () use ($items): void {
            $items->each(fn ($candidate, int $position) => $candidate->updateQuietly(['position' => $position + 1]));
        });

        $itemPosition = $index + 1;
        $neighbourPosition = $direction === 'up' ? $index : $index + 2;

        $item->update(['position' => $neighbourPosition]);
        $neighbour->update(['position' => $itemPosition]);
    }
}
