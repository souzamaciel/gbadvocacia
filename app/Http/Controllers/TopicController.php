<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTopicRequest;
use App\Http\Requests\SuggestTopicVariablesRequest;
use App\Http\Requests\UpdateTopicRequest;
use App\Models\Group;
use App\Models\Topic;
use App\Services\OpenAI\TopicVariableSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class TopicController extends Controller
{
    public function index(): View
    {
        $topics = Topic::query()
            ->with('group')
            ->orderByRaw('group_id = ? desc', [Group::PRELIMINARES_ID])
            ->orderedByGroup()
            ->orderBy('title')
            ->get();

        $groups = Group::query()
            ->withCount('topics')
            ->orderByRaw('id = ? desc', [Group::PRELIMINARES_ID])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('settings.topics.index', compact('topics', 'groups'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('settings.topics.index', ['novo' => 1]);
    }

    public function store(StoreTopicRequest $request): RedirectResponse
    {
        $maxOrder = Topic::query()
            ->where('group_id', $request->integer('group_id'))
            ->max('sort_order') ?? 0;

        Topic::create([
            'group_id' => $request->integer('group_id'),
            'title' => $request->string('title')->toString(),
            'content' => $request->string('content')->toString(),
            'notes' => $request->filled('notes') ? $request->string('notes')->trim()->toString() : null,
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('settings.topics.index')
            ->with('status', 'Tópico criado com sucesso!');
    }

    public function edit(Topic $topic): RedirectResponse
    {
        return redirect()->route('settings.topics.index', ['editar' => $topic->id]);
    }

    public function update(UpdateTopicRequest $request, Topic $topic): RedirectResponse
    {
        $topic->update([
            'group_id' => $request->integer('group_id'),
            'title' => $request->string('title')->toString(),
            'content' => $request->string('content')->toString(),
            'notes' => $request->filled('notes') ? $request->string('notes')->trim()->toString() : null,
        ]);

        return redirect()
            ->route('settings.topics.index')
            ->with('status', 'Tópico atualizado com sucesso!');
    }

    /**
     * Ask the AI which passages of a pasted text should become variables. Nothing is saved.
     */
    public function suggestVariables(SuggestTopicVariablesRequest $request, TopicVariableSuggestionService $suggestions): JsonResponse
    {
        $groups = Group::query()->orderBy('sort_order')->pluck('name', 'id');

        try {
            $result = $suggestions->suggest($request->string('content')->toString(), $groups->values()->all());
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Não foi possível analisar o texto agora. Tente novamente ou crie as variáveis manualmente.',
            ], 502);
        }

        return response()->json([
            'suggestions' => $result['suggestions'],
            'title' => $result['title'],
            'group_id' => $result['group'] === null ? null : $groups->search($result['group']),
        ]);
    }

    public function destroy(Topic $topic): JsonResponse
    {
        $topic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tópico excluído com sucesso!',
        ]);
    }
}
