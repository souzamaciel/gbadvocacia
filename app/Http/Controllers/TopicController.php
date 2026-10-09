<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTopicRequest;
use App\Http\Requests\UpdateTopicRequest;
use App\Models\Group;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function index(): View
    {
        $topics = Topic::query()
            ->with('group')
            ->orderBy('group_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('settings.topics.index', compact('topics'));
    }

    public function create(): View
    {
        $groups = Group::query()->orderBy('name')->get();

        return view('settings.topics.form', [
            'topic' => null,
            'groups' => $groups,
        ]);
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
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('settings.topics.index')
            ->with('status', 'Tópico criado com sucesso!');
    }

    public function edit(Topic $topic): View
    {
        $groups = Group::query()->orderBy('name')->get();

        return view('settings.topics.form', compact('topic', 'groups'));
    }

    public function update(UpdateTopicRequest $request, Topic $topic): RedirectResponse
    {
        $topic->update([
            'group_id' => $request->integer('group_id'),
            'title' => $request->string('title')->toString(),
            'content' => $request->string('content')->toString(),
        ]);

        return redirect()
            ->route('settings.topics.index')
            ->with('status', 'Tópico atualizado com sucesso!');
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
