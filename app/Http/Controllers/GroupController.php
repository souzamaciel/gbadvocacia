<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class GroupController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('settings.topics.index');
    }

    public function store(StoreGroupRequest $request): JsonResponse
    {
        $maxOrder = Group::query()->max('sort_order') ?? 0;

        $group = Group::create([
            'name' => $request->string('name')->toString(),
            'sort_order' => $maxOrder + 1,
        ]);
        app(ActivityLogger::class)->log('group.created', "Criou o grupo {$group->name}.", $group);

        return response()->json([
            'success' => true,
            'message' => 'Grupo criado com sucesso!',
            'group' => $group,
        ], 201);
    }

    public function update(UpdateGroupRequest $request, Group $group): JsonResponse
    {
        $previous = $group->name;
        $group->update([
            'name' => $request->string('name')->toString(),
        ]);
        app(ActivityLogger::class)->log('group.updated', "Renomeou o grupo {$previous} para {$group->name}.", $group);

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

        app(ActivityLogger::class)->log('group.deleted', "Excluiu o grupo {$group->name}.", $group);
        $group->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grupo excluído com sucesso!',
        ]);
    }
}
