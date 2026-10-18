<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Petition;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    private function role(string $name): Role
    {
        return Role::query()->where('name', $name)->firstOrFail();
    }

    public function test_admin_sees_every_user(): void
    {
        User::factory()->create(['name' => 'Carlos Lima']);
        User::factory()->inactive()->create(['name' => 'Beatriz Antiga']);

        $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Carlos Lima')
            ->assertSee('Beatriz Antiga')
            ->assertSee('Desativados · 1');
    }

    public function test_admin_creates_a_user_with_a_temporary_password(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Carlos Lima',
            'email' => 'Carlos@Exemplo.com.br',
            'role_id' => $this->role('Assistente')->id,
            'password' => 'provisoria1',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'carlos@exemplo.com.br')->firstOrFail();
        $this->assertTrue($user->must_change_password);
        $this->assertTrue($user->is_active);
        $this->assertSame('Assistente', $user->role->name);
        $this->assertTrue(Hash::check('provisoria1', $user->password));
    }

    public function test_new_user_needs_unique_email_profile_and_long_password(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'name' => 'Repetido',
            'email' => $this->admin->email,
            'role_id' => '',
            'password' => 'curta',
        ])->assertSessionHasErrors([
            'email' => 'Este e-mail já é usado por outro usuário.',
            'role_id' => 'Escolha o perfil de permissão.',
            'password' => 'A senha provisória deve ter pelo menos 8 caracteres.',
        ]);
    }

    public function test_admin_changes_another_users_profile(): void
    {
        $user = User::factory()->create(['role_id' => $this->role('Assistente')->id]);

        $this->actingAs($this->admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $this->role('Advogado')->id,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame('Advogado', $user->fresh()->role->name);
    }

    public function test_admin_cannot_change_their_own_profile(): void
    {
        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => $this->role('Assistente')->id,
        ])->assertSessionHasErrors(['role_id' => 'Você não pode alterar o seu próprio perfil.']);
    }

    public function test_only_administrators_manage_other_administrators(): void
    {
        $manager = User::factory()->withPermissions([Permission::UsersManage])->create();

        $this->actingAs($manager)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => $this->role('Advogado')->id,
        ])->assertForbidden();
        $this->actingAs($manager)->put(route('admin.users.status', $this->admin))->assertForbidden();
        $this->actingAs($manager)->put(route('admin.users.password', $this->admin), ['password' => 'nova-provisoria'])->assertForbidden();

        $other = User::factory()->create();
        $this->actingAs($other)->put(route('admin.users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role_id' => $this->role('Advogado')->id,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame('Advogado', $this->admin->fresh()->role->name);
    }

    public function test_only_administrators_grant_the_administrator_profile(): void
    {
        $manager = User::factory()->withPermissions([Permission::UsersManage])->create();

        $this->actingAs($manager)->post(route('admin.users.store'), [
            'name' => 'Novo Admin',
            'email' => 'novo@exemplo.com.br',
            'role_id' => Role::administrator()->id,
            'password' => 'provisoria1',
        ])->assertSessionHasErrors(['role_id' => 'Só administradores podem dar o perfil Administrador.']);

        $this->actingAs($manager)->get(route('admin.users.create'))
            ->assertOk()
            ->assertDontSee('<option value="'.Role::administrator()->id.'"', false);
    }

    public function test_admin_resets_a_password_and_the_user_must_change_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin)->put(route('admin.users.password', $user), ['password' => 'nova-provisoria'])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('nova-provisoria', $user->fresh()->password));
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_admin_cannot_reset_their_own_password_here(): void
    {
        $this->actingAs($this->admin)->put(route('admin.users.password', $this->admin), ['password' => 'nova-provisoria'])
            ->assertSessionHasErrors(['password' => 'Para trocar a sua própria senha, use “Meu perfil”.']);
    }

    public function test_admin_deactivates_and_reactivates_a_user_keeping_their_petitions(): void
    {
        $user = User::factory()->create();
        $petition = Petition::factory()->create(['user_id' => $user->id]);

        $this->actingAs($this->admin)->put(route('admin.users.status', $user))->assertRedirect(route('admin.users.index'));
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame($user->id, $petition->fresh()->user_id);

        $this->actingAs($this->admin)->put(route('admin.users.status', $user))->assertRedirect(route('admin.users.index'));
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $this->actingAs($this->admin)->from(route('admin.users.index'))->put(route('admin.users.status', $this->admin))
            ->assertSessionHas('error', 'Você não pode desativar o seu próprio acesso.');

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_users_without_permission_cannot_manage_users(): void
    {
        $lawyer = User::factory()->create(['role_id' => $this->role('Advogado')->id]);

        $this->actingAs($lawyer)->get(route('admin.users.index'))->assertForbidden()->assertSee('Seu perfil não tem permissão para esta tela');
        $this->actingAs($lawyer)->post(route('admin.users.store'), [])->assertForbidden();
    }
}
