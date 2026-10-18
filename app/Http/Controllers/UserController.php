<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()->with('role')->orderByDesc('is_active')->orderBy('name')->get();

        return view('admin.users.index', compact('users'));
    }

    public function create(Request $request): View
    {
        return view('admin.users.form', [
            'user' => new User(['is_active' => true]),
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'role_id' => $request->integer('role_id'),
            'password' => Hash::make($request->string('password')->toString()),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Usuário {$user->name} cadastrado. Informe o e-mail e a senha provisória; a senha deverá ser trocada no primeiro acesso.");
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($request->user()->canManageUser($user), 403);

        return view('admin.users.form', [
            'user' => $user->load('role'),
            'roles' => $this->assignableRoles($request->user()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Dados de {$user->name} atualizados.");
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
            'must_change_password' => true,
        ])->setRememberToken(Str::random(60));
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Senha provisória de {$user->name} definida. Informe a senha a ele; a troca será pedida no próximo acesso.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageUser($user), 403);

        if ($user->is($request->user())) {
            return back()->with('error', 'Você não pode desativar o seu próprio acesso.');
        }

        if ($user->is_active && $user->isAdministrator() && ! $user->hasAnotherActiveAdministrator()) {
            return back()->with('error', 'É preciso manter pelo menos 1 administrador ativo.');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->setRememberToken(Str::random(60));
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', $user->is_active
                ? "Acesso de {$user->name} reativado."
                : "Acesso de {$user->name} desativado. As petições feitas por ele continuam no sistema.");
    }

    /**
     * Profiles the current user may give to someone. Only administrators grant Administrador.
     *
     * @return Collection<int, Role>
     */
    private function assignableRoles(User $actor): Collection
    {
        return Role::query()
            ->orderByDesc('is_locked')
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role): bool => $actor->isAdministrator() || ! $role->isAdministrator())
            ->values();
    }
}
