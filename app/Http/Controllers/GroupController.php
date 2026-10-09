<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(): View
    {
        $groups = Group::query()
            ->withCount('topics')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('settings.groups.index', compact('groups'));
    }

    public function store(StoreGroupRequest $request): JsonResponse
    {
        $maxOrder = Group::query()->max('sort_order') ?? 0;

        $group = Group::create([
            'name' => $request->string('name')->toString(),
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo criado com sucesso!',
            'group' => $group,
        ], 201);
    }

    public function update(UpdateGroupRequest $request, Group $group): JsonResponse
    {
        $group->update([
            'name' => $request->string('name')->toString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Grupo atualizado com sucesso!',
            'group' => $group,
        ]);
    }

    public function destroy(Group $group): JsonResponse
    {
        $topicCount = $group->topics()->count();

        if ($topicCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Não é possível excluir o grupo \"{$group->name}\" pois ele possui {$topicCount} tópico(s) vinculado(s).",
            ], 422);
        }

        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grupo excluído com sucesso!',
        ]);
    }
}
