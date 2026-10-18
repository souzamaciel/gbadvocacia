<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Http\Requests\SaveRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()->withCount('users')->orderByDesc('is_locked')->orderBy('name')->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(Request $request): View
    {
        $source = $request->filled('from') ? Role::find($request->integer('from')) : null;

        $role = new Role([
            'name' => $source ? "{$source->name} (cópia)" : '',
            'description' => $source?->description,
            'permissions' => $source ? array_map(fn (Permission $permission): string => $permission->value, $source->grantedPermissions()) : [],
        ]);

        return view('admin.roles.form', compact('role', 'source'));
    }

    public function store(SaveRoleRequest $request): RedirectResponse
    {
        $role = Role::create($this->attributes($request));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Perfil {$role->name} criado. Agora ele pode ser escolhido no cadastro de usuários.");
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.form', ['role' => $role->loadCount('users'), 'source' => null]);
    }

    public function update(SaveRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->isAdministrator(), 403);

        $role->update($this->attributes($request));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Perfil {$role->name} atualizado. As mudanças já valem para quem usa este perfil.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->isAdministrator(), 403);

        if ($role->users()->exists()) {
            return back()->with('error', "O perfil {$role->name} está em uso. Troque o perfil desses usuários antes de excluir.");
        }

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Perfil {$role->name} excluído.");
    }

    /**
     * @return array{name: string, description: ?string, permissions: list<string>}
     */
    private function attributes(SaveRoleRequest $request): array
    {
        return [
            'name' => $request->string('name')->toString(),
            'description' => $request->filled('description') ? $request->string('description')->toString() : null,
            'permissions' => Permission::normalize($request->input('permissions', [])),
        ];
    }
}
